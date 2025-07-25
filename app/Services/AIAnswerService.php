<?php

namespace App\Services;

use App\Data\AIAnswerData;
use App\Data\ArticleSourceData;
use Illuminate\Support\Facades\Http;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class AIAnswerService
{
    public function __construct(
        protected PineconeService $pinecone,
        protected EmbeddingService $embeddingService
    ) {}

    public function generateAnswer(string $query, ?array $conversationContext = null, ?array $messages = []): string
    {
        try {
            $context = $this->retrieveRelevantContext($query);
            $systemPrompt = $this->buildSystemPrompt($context, $conversationContext, $messages);
            $userPrompt = "Customer's question: {$query}";

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
                
                Log::error('OpenAI API failed: ' . $errorMessage);

                if (str_contains($errorMessage, 'quota')) {
                    return "I apologize, but the AI assistant is temporarily unavailable. Please contact support directly for assistance.";
                }

                throw new Exception('AI service unavailable: ' . $errorMessage);
            }

            return $response->json()['choices'][0]['message']['content'] ?? '';
        } catch (Exception $e) {
            Log::error('AI answer generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function retrieveRelevantContext(string $query): Collection
    {
        try {
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);

            if (!$queryEmbedding) {
                Log::warning('Failed to generate embeddings for query');
                return collect();
            }

            $matches = collect($this->pinecone->query(
                $queryEmbedding,
                config('ai.rag.max_context_articles', 5),
                ['is_published' => true]
            ));

            if (empty($matches)) {
                return collect();
            }
            
            $matches = $matches->filter(function ($match) {
                return ($match['score'] ?? 0) >= config('ai.rag.similarity_threshold', 0.4);
            });

            $articleIds = $matches->unique('metadata.article_id')->pluck('metadata.article_id');

            if ($articleIds->isEmpty()) {
                return collect();
            }

            return KnowledgeBaseArticle::whereIn('id', $articleIds)
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
            Log::error('Failed to retrieve relevant context: ' . $e->getMessage());
            return collect();
        }
    }

    protected function buildSystemPrompt(Collection $context, ?array $conversationContext = null, ?array $messages = []): string
    {
        $prompt = "You are a professional customer service specialist. Provide helpful, accurate, and empathetic responses.\n\n";

        if ($conversationContext && isset($conversationContext['subject'])) {
            $prompt .= "Conversation: {$conversationContext['subject']}\n";
            if (isset($conversationContext['contact']['name'])) {
                $prompt .= "Customer: {$conversationContext['contact']['name']}\n";
            }
            $prompt .= "\n";
        }

        if (!empty($messages)) {
            $prompt .= "Recent messages:\n";
            $recentMessages = array_slice($messages, -3); 
            foreach ($recentMessages as $message) {
                $sender = $message['type'] === 'customer' ? 'Customer' : 'Agent';
                $content = strip_tags($message['content'] ?? '');
                $content = substr($content, 0, 150) . (strlen($content) > 150 ? '...' : '');
                $prompt .= "- {$sender}: {$content}\n";
            }
            $prompt .= "\n";
        }

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



    public function getArticleSources(string $query): Collection
    {
        return $this->retrieveRelevantContext($query)
            ->map(function ($article) {
                return ArticleSourceData::from([
                    'id' => $article['id'],
                    'title' => $article['title'],
                    'url' => $article['url'],
                    'excerpt' => $article['excerpt']
                ]);
            });
    }
}

