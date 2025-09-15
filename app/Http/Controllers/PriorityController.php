<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class PriorityController extends Controller
{
    /**
     * Update the priority of a conversation.
     */
    public function update(Request $request, Conversation $conversation)
    {

        // Validate the request
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent'
        ]);


        // Update the conversation priority
        $conversation->priority = $validated['priority'];
        $conversation->last_activity_at = now();
        $conversation->save();

        // For Inertia requests, return a redirect response
        if ($request->wantsJson()) {
            return redirect()->back()
                ->with('success', 'Priority updated successfully');
        }

        return back()->with('success', 'Priority updated successfully');
    }
}
