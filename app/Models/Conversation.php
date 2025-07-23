<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\Status;
use App\Filters\ConversationFilter;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory, HasUlids;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_id',
        'subject',
        'status',
        'priority',
        'last_activity_at',
        'unread',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime',
        'unread' => 'boolean',
        'status' => Status::class,
        'priority' => Priority::class,
    ];

    /**
     * Get the contact that owns the conversation.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'unread' => false,
            'read_at' => now()
        ]);
    }

    /**
     * Mark conversation as unread
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'unread' => true,
            'read_at' => null
        ]);
    }

    /**
     * Check if conversation is unread
     */
    public function isUnread(): bool
    {
        return $this->unread;
    }

    /**
     * Scope to get only unread conversations
     */
    public function scopeUnread($query)
    {
        return $query->where('unread', true);
    }

    /**
     * Scope to get only read conversations
     */
    public function scopeRead($query)
    {
        return $query->where('unread', false);
    }

    /**
     * Scope to filter conversations
     */
    public function scopeFilter($query, array $filters)
    {
        return (new ConversationFilter($query, $filters))->apply();
    }

    /**
     * Scope to get conversations with related data
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['contact', 'messages' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }]);
    }
}
