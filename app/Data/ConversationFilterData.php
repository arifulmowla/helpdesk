<?php

namespace App\Data;

use App\Filters\ConversationFilter;
use App\Models\Conversation;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ConversationFilterData extends Data
{
    public function __construct(
        public array $statuses,
        public array $priorities,
        public ConversationStatsData $stats,
    ) {
    }

    public static function create(): self
    {
        return new self(
            statuses: ConversationFilter::getAvailableStatuses(),
            priorities: ConversationFilter::getAvailablePriorities(),
            stats: ConversationStatsData::create(),
        );
    }
}

#[TypeScript]
class ConversationStatsData extends Data
{
    public function __construct(
        public int $total,
        public int $unread,
        public array $by_status,
        public array $by_priority,
    ) {
    }

    public static function create(): self
    {
        $stats = [
            'total' => Conversation::count(),
            'unread' => Conversation::unread()->count(),
            'by_status' => [],
            'by_priority' => [],
        ];

        // Group by status
        $statusCounts = Conversation::select('status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        foreach (ConversationFilter::getAvailableStatuses() as $status) {
            $stats['by_status'][$status] = $statusCounts[$status] ?? 0;
        }

        // Group by priority
        $priorityCounts = Conversation::select('priority')
            ->selectRaw('count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
        
        foreach (ConversationFilter::getAvailablePriorities() as $priority) {
            $stats['by_priority'][$priority] = $priorityCounts[$priority] ?? 0;
        }

        return new self(
            total: $stats['total'],
            unread: $stats['unread'],
            by_status: $stats['by_status'],
            by_priority: $stats['by_priority'],
        );
    }
}
