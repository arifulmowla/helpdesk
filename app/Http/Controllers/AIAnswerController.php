<?php

namespace App\Http\Controllers;

use App\Data\AIAnswerData;
use App\Services\AIAnswerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AIAnswerController extends Controller
{
    public function __construct(
        private AIAnswerService $aiAnswerService
    ) {}

    public function generate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:3|max:1000',
                'conversation_id' => 'nullable|exists:conversations,id',
                'conversation_context' => 'nullable|array',
                'conversation_context.subject' => 'nullable|string',
                'conversation_context.contact' => 'nullable|array',
                'messages' => 'nullable|array',
            ]);

            $answer = $this->aiAnswerService->generateAnswer(
                $validated['query'],
                $request->input('conversation_context'),
                $request->input('messages', [])
            );
            
            $sources = $this->aiAnswerService->getArticleSources($validated['query']);

            $responseData = AIAnswerData::from([
                'answer' => $answer,
                'sources' => $sources,
                'query' => $validated['query'],
                'timestamp' => now()->toISOString(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid input',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('AI answer generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate answer. Please try again later.',
            ], 500);
        }
    }

    public function sources(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:3|max:1000',
            ]);

            $sources = $this->aiAnswerService->getArticleSources($validated['query']);

            return response()->json([
                'success' => true,
                'sources' => $sources,
                'query' => $validated['query'],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid input',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('AI sources retrieval failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve sources.',
            ], 500);
        }
    }
}
