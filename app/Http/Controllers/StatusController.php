<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Update the status of a conversation.
     */
    public function update(Request $request, Conversation $conversation)
    {
        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:open,pending,closed',
        ]);
return $request;

        // Update the conversation status
        $conversation->status = $validated['status'];
        $conversation->last_activity_at = now();
        $conversation->save();

        // For Inertia requests, return a redirect response
        if ($request->wantsJson()) {
            return redirect()->back()
                ->with('success', 'Status updated successfully');
        }

        return back()->with('success', 'Status updated successfully');
    }
}
