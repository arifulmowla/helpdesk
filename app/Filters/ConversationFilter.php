<?php

namespace App\Filters;

use App\Enums\Priority;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;

class ConversationFilter
{
    protected Builder $builder;
    protected array $filters;

    public function __construct(Builder $builder, array $filters = [])
    {
        $this->builder = $builder;
        $this->filters = $filters;
    }

    public function apply(): Builder
    {
        foreach ($this->filters as $filter => $value) {
            if ($value !== null && $value !== '') {
                $method = 'filter' . ucfirst($filter);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        return $this->builder;
    }

    protected function filterStatus($status): void
    {
        if (is_array($status)) {
            $this->builder->whereIn('status', $status);
        } else {
            $this->builder->where('status', $status);
        }
    }

    protected function filterPriority($priority): void
    {
        if (is_array($priority)) {
            $this->builder->whereIn('priority', $priority);
        } else {
            $this->builder->where('priority', $priority);
        }
    }

    protected function filterUnread($unread): void
    {
        // Convert to boolean if string
        if (is_string($unread)) {
            $unread = filter_var($unread, FILTER_VALIDATE_BOOLEAN);
        }
        
        $this->builder->where('unread', $unread);
    }

    protected function filterSearch($search): void
    {
        $this->builder->where(function (Builder $query) use ($search) {
            $query->where('subject', 'like', "%{$search}%")
                  ->orWhereHas('contact', function (Builder $contactQuery) use ($search) {
                      $contactQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('messages', function (Builder $messageQuery) use ($search) {
                      $messageQuery->where('content', 'like', "%{$search}%");
                  });
        });
    }

    protected function filterContactId($contactId): void
    {
        if (is_array($contactId)) {
            $this->builder->whereIn('contact_id', $contactId);
        } else {
            $this->builder->where('contact_id', $contactId);
        }
    }

    /**
     * Get all available status values with labels and colors
     */
    public static function getAvailableStatuses(): array
    {
        return array_map(function (Status $status) {
            return $status->toArray();
        }, Status::cases());
    }

    /**
     * Get all available priority values with labels and colors
     */
    public static function getAvailablePriorities(): array
    {
        return array_map(function (Priority $priority) {
            return $priority->toArray();
        }, Priority::cases());
    }
}
