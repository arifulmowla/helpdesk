<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationReadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Contact $contact;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->contact = Contact::factory()->create();
        $this->conversation = Conversation::factory()->create([
            'contact_id' => $this->contact->id,
            'unread' => true,
            'read_at' => null,
        ]);
    }

    public function test_can_mark_conversation_as_read()
    {
        // Ensure conversation is initially unread
        $this->assertTrue($this->conversation->unread);
        $this->assertNull($this->conversation->read_at);

        // Make authenticated request to mark as read
        $response = $this->actingAs($this->user)
            ->post("/helpdesk/conversations/{$this->conversation->id}/read");

        // Assert successful response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conversation marked as read',
            ]);

        // Refresh model from database
        $this->conversation->refresh();

        // Assert conversation is now marked as read
        $this->assertFalse($this->conversation->unread);
        $this->assertNotNull($this->conversation->read_at);
    }

    public function test_conversation_read_endpoint_requires_authentication()
    {
        // Make unauthenticated request
        $response = $this->post("/helpdesk/conversations/{$this->conversation->id}/read");

        // Should redirect to login
        $response->assertRedirect('/login');
    }

    public function test_conversation_read_endpoint_returns_404_for_nonexistent_conversation()
    {
        // Make authenticated request with non-existent conversation ID
        $response = $this->actingAs($this->user)
            ->post("/helpdesk/conversations/nonexistent-id/read");

        // Should return 404
        $response->assertStatus(404);
    }

    public function test_can_mark_already_read_conversation_as_read_again()
    {
        // Mark conversation as read first
        $this->conversation->markAsRead();
        $this->conversation->refresh();

        $originalReadAt = $this->conversation->read_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Mark as read again
        $response = $this->actingAs($this->user)
            ->post("/helpdesk/conversations/{$this->conversation->id}/read");

        $response->assertStatus(200);

        // Refresh model from database
        $this->conversation->refresh();

        // Assert conversation is still read with updated timestamp
        $this->assertFalse($this->conversation->unread);
        $this->assertNotNull($this->conversation->read_at);
        $this->assertNotEquals($originalReadAt, $this->conversation->read_at);
    }
}
