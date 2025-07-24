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
     * Generate an AI-powered answer with streaming support
     */
    public function generateAnswerStream(string $query, ?array $conversationContext = null, ?array $messages = [], ?callable $onChunk = null): \Generator
    {
        try {
            // Get relevant context from knowledge base
            $context = $this->retrieveRelevantContext($query);
            
            // Build messages for the conversation
            $aiMessages = $this->buildMessages($query, $context, $conversationContext, $messages);
            
            // Stream the response using direct OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('ai.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('ai.openai.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $aiMessages['system']
                    ],
                    [
                        'role' => 'user', 
                        'content' => $aiMessages['user']
                    ]
                ],
                'max_tokens' => config('ai.openai.max_tokens', 1000),
                'temperature' => config('ai.openai.temperature', 0.7),
                'stream' => true,
            ]);

            if (!$response->successful()) {
                throw new Exception('OpenAI API request failed: ' . $response->body());
            }

            // For now, just return the complete response as a single chunk
            // Full streaming implementation would require parsing SSE format
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            
            if ($onChunk) {
                $onChunk($content);
            }
            yield $content; // Generator must yield for proper return type
        } catch (Exception $e) {
            Log::error('AI answer generation failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a complete AI-powered answer (non-streaming)
     */
    public function generateAnswer(string $query, ?array $conversationContext = null, ?array $messages = []): string
    {
        try {
            // Get relevant context from knowledge base
            $context = $this->retrieveRelevantContext($query);
            
            // Build messages for the conversation
            $aiMessages = $this->buildMessages($query, $context, $conversationContext, $messages);
            
            // Generate response using direct OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('ai.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('ai.openai.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $aiMessages['system']
                    ],
                    [
                        'role' => 'user', 
                        'content' => $aiMessages['user']
                    ]
                ],
                'max_tokens' => config('ai.openai.max_tokens', 1000),
                'temperature' => config('ai.openai.temperature', 0.7),
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown OpenAI API error';
                $errorCode = $errorData['error']['code'] ?? null;
                
                Log::error('OpenAI API request failed', [
                    'status' => $response->status(),
                    'error_message' => $errorMessage,
                    'error_code' => $errorCode,
                    'full_response' => $errorBody,
                    'query' => substr($query, 0, 100) . '...'
                ]);
                
                // Provide a helpful fallback for quota exceeded errors
                if ($errorCode === 'insufficient_quota') {
                    return "I apologize, but the AI assistant is temporarily unavailable due to API quota limits. In the meantime, I'd be happy to help you manually. Could you please provide more details about your issue so I can assist you directly?";
                }
                
                throw new Exception('OpenAI API Error: ' . $errorMessage);
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';
        } catch (Exception $e) {
            Log::error('AI answer generation failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            $matches = $this->pinecone->query(
                $queryEmbedding,
                config('ai.rag.max_context_articles', 5),
                ['is_published' => true] // Only include published articles
            );

            if (empty($matches)) {
                Log::info('No matching articles found in Pinecone', ['query' => $query]);
                return collect();
            }

            // Filter by similarity threshold
            $relevantMatches = array_filter($matches, function ($match) {
                return ($match['score'] ?? 0) >= config('ai.rag.similarity_threshold', 0.7);
            });

            // Get article IDs from the matches
            $articleIds = array_map(function ($match) {
                return $match['metadata']['article_id'] ?? null;
            }, $relevantMatches);

            $articleIds = array_filter($articleIds);

            if (empty($articleIds)) {
                return collect();
            }

            // Fetch the actual articles from database
            return KnowledgeBaseArticle::whereIn('id', $articleIds)
                ->where('is_published', true)
                ->get(['id', 'title', 'body', 'excerpt', 'slug'])
                ->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'content' => strip_tags($article->body),
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
     * Build system and user messages for the AI conversation
     */
    protected function buildMessages(string $query, Collection $context, ?array $conversationContext = null, ?array $messages = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($context, $conversationContext, $messages);
        $userPrompt = $this->buildUserPrompt($query, $context, $conversationContext, $messages);

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt
        ];
    }

    /**
     * Build the system prompt with context
     */
    protected function buildSystemPrompt(Collection $context, ?array $conversationContext = null, ?array $messages = []): string
    {
        $basePrompt = "You are a professional Customer Service specialist AI assistant for a helpdesk system. "
            . "Your role is to provide accurate, helpful, and empathetic responses to customer inquiries based on the knowledge base content and conversation context provided. "
            . "Always be concise, clear, professional, and customer-focused in your responses.\n\n";

        // Add conversation context if available
        if ($conversationContext && isset($conversationContext['subject'])) {
            $basePrompt .= "Current conversation context:\n";
            $basePrompt .= "- Subject: {$conversationContext['subject']}\n";
            if (isset($conversationContext['contact']['name'])) {
                $basePrompt .= "- Customer: {$conversationContext['contact']['name']}\n";
            }
            if (isset($conversationContext['contact']['email'])) {
                $basePrompt .= "- Email: {$conversationContext['contact']['email']}\n";
            }
            $basePrompt .= "\n";
        }

        // Add conversation history if available
        if (!empty($messages)) {
            $basePrompt .= "Recent conversation history:\n";
            $messageCount = min(5, count($messages)); // Limit to last 5 messages
            $recentMessages = array_slice($messages, -$messageCount);
            
            foreach ($recentMessages as $message) {
                $sender = $message['type'] === 'customer' ? 'Customer' : 'Agent';
                $content = strip_tags($message['content'] ?? '');
                $content = substr($content, 0, 200) . (strlen($content) > 200 ? '...' : '');
                $basePrompt .= "- {$sender}: {$content}\n";
            }
            $basePrompt .= "\n";
        }

        if ($context->isEmpty()) {
            return $basePrompt . "No relevant knowledge base articles were found for this query. "
                . "Please provide a helpful general response and suggest that the user might want to "
                . "contact support for more specific assistance.";
        }

        $contextText = "Here are the relevant knowledge base articles to help answer the user's question:\n\n";
        
        foreach ($context as $index => $article) {
            $contextText .= "Article " . ($index + 1) . " - {$article['title']}:\n";
            $contextText .= substr($article['content'], 0, config('ai.rag.max_context_length', 4000));
            $contextText .= "\n\n";
        }

        $contextText .= "Guidelines:\n";
        $contextText .= "- Base your answer primarily on the provided articles\n";
        $contextText .= "- If the articles don't fully answer the question, say so clearly\n";
        $contextText .= "- You can mention relevant article titles when helpful\n";
        $contextText .= "- Keep responses concise but comprehensive\n";
        $contextText .= "- If you reference specific steps or procedures, be precise\n";

        return $basePrompt . $contextText;
    }

    /**
     * Build the user prompt
     */
    protected function buildUserPrompt(string $query, Collection $context, ?array $conversationContext = null, ?array $messages = []): string
    {
        // For helpdesk responses, we want to focus on the latest customer message
        // but also provide context about what they're asking
        $userPrompt = "Customer's latest message/question: {$query}\n\n";
        
        $userPrompt .= "Please provide a helpful, professional response that addresses the customer's question. ";
        $userPrompt .= "Use the knowledge base articles and conversation context to provide accurate information. ";
        $userPrompt .= "If you need to reference specific steps or procedures, be precise and clear.";
        
        return $userPrompt;
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

