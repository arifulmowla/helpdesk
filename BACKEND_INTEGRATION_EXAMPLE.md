# Backend Integration for Persistent Collapse State

## Controller Updates Required

To support the persistent collapse state feature, you'll need to update your Laravel controllers to handle and pass collapse state data through Inertia.

### 1. Update your Helpdesk Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpdeskController extends Controller
{
    public function index(Request $request)
    {
        // Get conversation filters, pagination, etc.
        $conversations = $this->getConversations($request);
        
        // Get or initialize collapse states from session/request
        $collapseStates = $this->getCollapseStates($request);
        
        return Inertia::render('helpdesk/Index', [
            'conversations' => $conversations,
            'collapseStates' => $collapseStates,
            // ... other props
        ]);
    }
    
    public function show(Request $request, $conversationId)
    {
        $conversation = $this->getConversation($conversationId);
        $messages = $this->getMessages($conversationId);
        $conversations = $this->getConversations($request);
        
        // Get or initialize collapse states
        $collapseStates = $this->getCollapseStates($request);
        
        return Inertia::render('helpdesk/Show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'conversations' => $conversations,
            'collapseStates' => $collapseStates,
            'filters' => [
                'current' => $request->only(['search', 'status', 'priority', 'unread']),
                'options' => $this->getFilterOptions(),
            ],
        ]);
    }
    
    /**
     * Get collapse states from request or initialize defaults
     */
    private function getCollapseStates(Request $request): array
    {
        // Check if collapse states are provided in the request (from preserveState)
        if ($request->has('collapseStates')) {
            return $request->input('collapseStates', []);
        }
        
        // Check session for stored collapse states
        $sessionStates = session('helpdesk.collapseStates', []);
        
        // Return stored states or empty array for new initialization
        return $sessionStates;
    }
    
    /**
     * Store collapse states in session for persistence across non-Inertia requests
     */
    private function storeCollapseStates(Request $request): void
    {
        if ($request->has('collapseStates')) {
            session(['helpdesk.collapseStates' => $request->input('collapseStates')]);
        }
    }
}
```

### 2. Middleware for Collapse State Persistence (Optional)

Create a middleware to automatically handle collapse state persistence:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PersistHelpdeskCollapseState
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Store collapse states in session if provided
        if ($request->has('collapseStates')) {
            session(['helpdesk.collapseStates' => $request->input('collapseStates')]);
        }
        
        return $response;
    }
}
```

### 3. Alternative: Database Storage

For more persistent storage across devices/sessions, you can store collapse states in the database:

```php
// Migration
Schema::create('user_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('key'); // e.g., 'helpdesk.collapseStates'
    $table->json('value');
    $table->timestamps();
    $table->unique(['user_id', 'key']);
});

// Model
class UserPreference extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];
    protected $casts = ['value' => 'array'];
}

// Usage in Controller
private function getCollapseStates(Request $request): array
{
    // Check request first (from preserveState)
    if ($request->has('collapseStates')) {
        return $request->input('collapseStates', []);
    }
    
    // Then check database
    $preference = UserPreference::where('user_id', auth()->id())
        ->where('key', 'helpdesk.collapseStates')
        ->first();
    
    return $preference ? $preference->value : [];
}
```

### 4. Frontend Integration Notes

The frontend implementation automatically handles:

- ✅ Storing collapse state in reactive store keyed by conversation ID
- ✅ Persisting state through Inertia's preserveState mechanism
- ✅ Restoring state on page loads and navigation
- ✅ Consistent behavior across different conversations

### 5. Usage Examples

```javascript
// In any component, get collapse state for current conversation
const { filterCollapsed, replyFormCollapsed, toggleFilterCollapse } = useConversationCollapseState(conversationId);

// Navigation that preserves state
navigateToConversation('conversation-123');

// Filter navigation that preserves state
navigateWithFilters('/helpdesk', { status: ['open'], priority: ['urgent'] });
```

## Benefits of This Implementation

1. **Per-conversation state**: Each conversation maintains its own collapse preferences
2. **Persistent across navigation**: State survives when switching between conversations
3. **Automatic synchronization**: State is automatically synced between frontend and backend
4. **Seamless UX**: Users maintain their preferred UI layout as they work
5. **Performance optimized**: Uses Inertia's preserveState to minimize server round-trips

## Testing the Feature

1. Expand/collapse filter section in conversation A
2. Navigate to conversation B
3. Collapse reply form in conversation B
4. Navigate back to conversation A
5. Verify filter section is still expanded, reply form is still expanded (default)
6. Navigate back to conversation B
7. Verify reply form is still collapsed
