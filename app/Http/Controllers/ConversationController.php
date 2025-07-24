<?php

namespace App\Http\Controllers;

use App\Data\ConversationData;
use App\Data\ConversationFilterData;
use App\Data\MessageData;
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
     * If no conversation ID is provided, it shows the first conversation or an empty state.
     */
    public function index(Request $request, Conversation $conversation = null)
    {
        // Get filter parameters
        $filters = $request->only(['status', 'priority', 'unread', 'search', 'contact_id']);
        $currentPage = $request->input('page', 1);
        $perPage = 10;

        // Get conversations with proper pagination handling
        $allConversations = $this->getPaginatedConversations($filters, $currentPage, $perPage);

        // If no specific conversation was requested, try to get the first one
        if ($conversation === null || !$conversation->exists) {
            $conversation = Conversation::orderBy('last_activity_at', 'desc')->first();
        }

        // Prepare the conversation data and messages if a conversation exists
        $conversationData = null;
        $messages = [];

        if ($conversation) {
            $conversation->markAsRead();
            $conversation->refresh();
            $conversation->load(['contact.company', 'assignedTo', 'messages.conversation.contact.company']);
            $conversationData = ConversationData::from($conversation);
            $messages = MessageData::collect($conversation->messages);
        }

        // Handle infinite scroll - if this is a partial request for conversations only
        if ($request->header('X-Inertia-Partial-Data') && in_array('conversations', explode(',', $request->header('X-Inertia-Partial-Data')))) {
            // For infinite scroll, we need to maintain the existing data structure
            // but only return the current page's data
            return Inertia::render('helpdesk/Show', [
                'conversations' => [
                    'data' => ConversationData::collect($allConversations->items()),
                    'current_page' => $allConversations->currentPage(),
                    'last_page' => $allConversations->lastPage(),
                    'per_page' => $allConversations->perPage(),
                    'total' => $allConversations->total(),
                    'from' => $allConversations->firstItem(),
                    'to' => $allConversations->lastItem(),
                ],
            ]);
        }

        return Inertia::render('helpdesk/Show', [
            'conversation' => $conversationData,
            'messages' => $messages,
            'conversations' => [
                'data' => ConversationData::collect($allConversations->items()),
                'current_page' => $allConversations->currentPage(),
                'last_page' => $allConversations->lastPage(),
                'per_page' => $allConversations->perPage(),
                'total' => $allConversations->total(),
                'from' => $allConversations->firstItem(),
                'to' => $allConversations->lastItem(),
            ],
            'filters' => [
                'current' => $filters,
                'options' => ConversationFilterData::create(),
            ],
            'users' => User::select(['id', 'name', 'email'])->get()->toArray(),
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
