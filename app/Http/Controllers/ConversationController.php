<?php

namespace App\Http\Controllers;

use App\Data\ConversationData;
use App\Data\ConversationFilterData;
use App\Data\MessageData;
use App\Enums\Priority;
use App\Enums\Status;
use App\Filters\ConversationFilter;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class ConversationController extends Controller
{
    /**
     * Display the helpdesk with conversations and messages.
     */
    public function index(Request $request, Conversation $conversation = null)
    {
        $filters = $request->only(['status', 'priority', 'unread', 'search', 'contact_id']);
        $conversations = $this->getPaginatedConversations($filters, $request->input('page', 1), 10);
        
        // Get conversation data if one is selected
        $conversation = $conversation ?: Conversation::orderBy('last_activity_at', 'desc')->first();
        [$conversationData, $messages] = $this->prepareConversationData($conversation);
        
        $conversationsData = $this->formatConversationsData($conversations);
        
        // Handle partial requests for infinite scroll
        if ($this->isPartialConversationsRequest($request)) {
            return Inertia::render('helpdesk/Show', ['conversations' => $conversationsData]);
        }

        return Inertia::render('helpdesk/Show', [
            'conversation' => $conversationData,
            'messages' => $messages,
            'conversations' => $conversationsData,
            'filters' => [
                'current' => $filters,
                'options' => ConversationFilterData::create(),
            ],
            'users' => User::select(['id', 'name', 'email'])->get()->toArray(),
            'statusOptions' => array_map(function ($status) {
                return [
                    'value' => $status->value,
                    'name' => $status->name()
                ];
            }, Status::cases()),
            'priorityOptions' => array_map(function ($priority) {
                return [
                    'value' => $priority->value,
                    'name' => $priority->name()
                ];
            }, Priority::cases()),
        ]);
    }

    /**
     * Get paginated conversations
     */
    protected function getPaginatedConversations(array $filters, int $currentPage, int $perPage)
    {
        return Conversation::with(['contact.company', 'assignedTo'])
            ->filter($filters)
            ->orderBy('last_activity_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);
    }
    
    /**
     * Prepare conversation data and messages.
     */
    private function prepareConversationData(?Conversation $conversation): array
    {
        if (!$conversation) {
            return [null, []];
        }
        
        $conversation->markAsRead();
        $conversation->load(['contact.company', 'assignedTo', 'messages.conversation.contact.company']);
        
        return [
            ConversationData::from($conversation),
            MessageData::collect($conversation->messages)
        ];
    }
    
    /**
     * Format conversations data for response.
     */
    private function formatConversationsData($conversations): array
    {
        return [
            'data' => ConversationData::collect($conversations->items()),
            'current_page' => $conversations->currentPage(),
            'last_page' => $conversations->lastPage(),
            'per_page' => $conversations->perPage(),
            'total' => $conversations->total(),
            'from' => $conversations->firstItem(),
            'to' => $conversations->lastItem(),
        ];
    }
    
    /**
     * Check if this is a partial request for conversations only.
     */
    private function isPartialConversationsRequest(Request $request): bool
    {
        $partialData = $request->header('X-Inertia-Partial-Data');
        return $partialData && in_array('conversations', explode(',', $partialData));
    }

    /**
     * Mark a conversation as read
     */
    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $conversation->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read',
            'conversation' => ConversationData::from($conversation->fresh(['contact']))
        ]);
    }

    /**
     * Mark a conversation as unread
     */
    public function markAsUnread(Conversation $conversation)
    {
        $conversation->markAsUnread();

        return redirect()->back()->with('success', 'Conversation marked as unread');
    }

    /**
     * Assign a conversation to a user
     */
    public function assign(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'string', 'exists:users,id']
        ]);

        $conversation->update([
            'assigned_to' => $validated['user_id']
        ]);

        $conversation->load(['contact', 'assignedTo']);

        return redirect()->back()->with('success',
            $validated['user_id']
                ? 'Conversation assigned to ' . $conversation->assignedTo->name
                : 'Conversation assignment removed'
        );
    }
}
