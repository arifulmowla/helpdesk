<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class AIAnswerService
{
    protected PineconeService $pinecone;
    protected EmbeddingService $embeddingService;

    public function __construct(PineconeService $pinecone, EmbeddingService $embeddingService)
    {
        $this->pinecone = $pinecone;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Generate a complete AI-powered answer (non-streaming)
     */
    public function generateAnswer(string $query, ?array $conversationContext = null, ?array $messages = []): string
    {
        try {
            // Get relevant context from knowledge base
            $context = $this->retrieveRelevantContext($query);

            // Build system and user prompts
            $systemPrompt = $this->buildSystemPrompt($context, $conversationContext, $messages);
            $userPrompt = "Customer's question: {$query}";

            // Generate response using OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('ai.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('ai.openai.model', 'gpt-4'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => config('ai.openai.max_tokens', 1000),
                'temperature' => config('ai.openai.temperature', 0.7),
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'OpenAI API error';
                
                Log::error('OpenAI API failed', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'query' => substr($query, 0, 100)
                ]);

                if (str_contains($errorMessage, 'quota')) {
                    return "I apologize, but the AI assistant is temporarily unavailable. Please contact support directly for assistance.";
                }

                throw new Exception('AI service unavailable: ' . $errorMessage);
            }

            return $response->json()['choices'][0]['message']['content'] ?? '';
        } catch (Exception $e) {
            Log::error('AI answer generation failed', ['query' => $query, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve relevant context from knowledge base using vector similarity
     */
    protected function retrieveRelevantContext(string $query): Collection
    {
        try {
            // Generate embeddings for the query
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);

            if (!$queryEmbedding) {
                Log::warning('Failed to generate embeddings for query', ['query' => $query]);
                return collect();
            }

            // Query Pinecone for similar vectors
            $matches = collect($this->pinecone->query(
                $queryEmbedding,
                config('ai.rag.max_context_articles', 5),
                ['is_published' => true] // Only include published articles
            ));

            if (empty($matches)) {
                Log::info('No matching articles found in Pinecone', ['query' => $query]);
                return collect();
            }
            $matches = $matches->filter(function ($match) {
                return ($match['score'] ?? 0) >= config('ai.rag.similarity_threshold', 0.4);
            });

            $articleIds = $matches->unique('metadata.article_id')->pluck('metadata.article_id');

            if ($articleIds->isEmpty()) {
                return collect();
            }

            // Fetch the actual articles from database
            return KnowledgeBaseArticle::whereIn('id', $articleIds)
                //->where('is_published', true)
                ->get(['id', 'title', 'body', 'excerpt', 'slug', 'raw_body'])
                ->map(function (KnowledgeBaseArticle $article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'content' => $article->raw_body,
                        'excerpt' => $article->excerpt,
                        'url' => route('knowledge-base.show', $article->slug)
                    ];
                });

        } catch (Exception $e) {
            Log::error('Failed to retrieve relevant context', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Build the system prompt with context
     */
    protected function buildSystemPrompt(Collection $context, ?array $conversationContext = null, ?array $messages = []): string
    {
        $prompt = "You are a professional customer service specialist. Provide helpful, accurate, and empathetic responses.\n\n";

        // Add conversation context
        if ($conversationContext && isset($conversationContext['subject'])) {
            $prompt .= "Conversation: {$conversationContext['subject']}\n";
            if (isset($conversationContext['contact']['name'])) {
                $prompt .= "Customer: {$conversationContext['contact']['name']}\n";
            }
            $prompt .= "\n";
        }

        // Add recent messages
        if (!empty($messages)) {
            $prompt .= "Recent messages:\n";
            $recentMessages = array_slice($messages, -3); // Last 3 messages only
            foreach ($recentMessages as $message) {
                $sender = $message['type'] === 'customer' ? 'Customer' : 'Agent';
                $content = strip_tags($message['content'] ?? '');
                $content = substr($content, 0, 150) . (strlen($content) > 150 ? '...' : '');
                $prompt .= "- {$sender}: {$content}\n";
            }
            $prompt .= "\n";
        }

        // Add knowledge base context
        if ($context->isEmpty()) {
            $prompt .= "No relevant articles found. Provide general help and suggest contacting support.";
        } else {
            $prompt .= "Knowledge base articles:\n\n";
            foreach ($context as $index => $article) {
                $prompt .= "Article " . ($index + 1) . ": {$article['title']}\n";
                $prompt .= substr($article['content'], 0, 2000) . "\n\n";
            }
            $prompt .= "Base your answer on these articles. Be concise and mention article titles when helpful.";
        }

        return $prompt;
    }



    /**
     * Get article sources used in the response
     */
    public function getArticleSources(string $query): Collection
    {
        return $this->retrieveRelevantContext($query)
            ->map(function ($article) {
                return [
                    'id' => $article['id'],
                    'title' => $article['title'],
                    'url' => $article['url'],
                    'excerpt' => $article['excerpt']
                ];
            });
    }
}

