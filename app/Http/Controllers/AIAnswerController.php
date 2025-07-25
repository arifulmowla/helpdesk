<?php

namespace App\Http\Controllers;

use App\Services\AIAnswerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AIAnswerController extends Controller
{
    protected AIAnswerService $aiAnswerService;

    public function __construct(AIAnswerService $aiAnswerService)
    {
        $this->aiAnswerService = $aiAnswerService;
    }

    /**
     * Generate an AI answer (non-streaming)
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:3|max:1000',
                'conversation_id' => 'nullable|exists:conversations,id',
                'conversation_context' => 'nullable|array',
                'conversation_context.subject' => 'nullable|string',
                'conversation_context.contact' => 'nullable|array',
                'messages' => 'nullable|array',
            ]);

            $query = $request->input('query');
            $conversationId = $request->input('conversation_id');
            $conversationContext = $request->input('conversation_context');
            $messages = $request->input('messages', []);

            Log::info('AI answer generation requested', ['query' => substr($query, 0, 100) . '...']);

            $answer = $this->aiAnswerService->generateAnswer($query, $conversationContext, $messages);
            $sources = $this->aiAnswerService->getArticleSources($query);

            return response()->json([
                'success' => true,
                'answer' => $answer,
                'sources' => $sources->toArray(),
                'query' => $query,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid input',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('AI answer generation failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate answer. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get sources for a query without generating an answer
     */
    public function sources(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:3|max:1000',
            ]);

            $query = $request->input('query');
            $sources = $this->aiAnswerService->getArticleSources($query);

            return response()->json([
                'success' => true,
                'sources' => $sources->toArray(),
                'query' => $query,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid input',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('AI sources retrieval failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve sources.',
            ], 500);
        }
    }
}
