<template>
  <div
    class="border-b border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors"
    :class="{
      'bg-blue-50': isActive,
      'font-semibold': conversation.unread && !isActive
    }"
    @click="$emit('click')"
  >
    <div class="p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2 flex-1 min-w-0">
          <!-- Unread indicator -->
          <div
            v-if="conversation.unread"
            class="w-2 h-2 bg-blue-500 rounded-full shrink-0"
            title="Unread conversation"
          ></div>
          <h3 class="font-medium truncate" :class="{
            'text-blue-600': isActive,
            'font-bold': conversation.unread && !isActive
          }">
            {{ conversation.subject }}
          </h3>
        </div>
        <div class="flex items-center space-x-2 text-xs text-gray-500 ml-2">
          <span class="px-2 py-1 rounded bg-gray-100 font-mono">
            #{{ conversation.case_number }}
          </span>
          <span>
            {{ formatDate(conversation.last_activity_at) }}
          </span>
        </div>
      </div>

      <div class="flex items-center text-sm text-gray-600 mt-1">
        <span class="truncate">{{ conversation.contact.name }}</span>
        <span class="mx-1">•</span>
        <span class="truncate text-gray-500">{{ conversation.contact.company?.name || 'No company' }}</span>
        <template v-if="conversation.assigned_to">
          <span class="mx-1">•</span>
          <span class="truncate text-blue-600 text-xs">Assigned to {{ conversation.assigned_to.name }}</span>
        </template>
      </div>

      <div class="flex items-center mt-2 space-x-2">
        <Tag :value="conversation.status" />
        <Tag :value="conversation.priority" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import { Tag } from '@/components/ui/tag';
// Import generated types
import '../../../types/generated.d';

// Define props
const props = defineProps<{
  conversation: App.Data.ConversationData;
  isActive?: boolean;
}>();

// Define emits
defineEmits<{
  (e: 'click'): void;
}>();

// Format date to relative time (e.g., "2 hours ago")
function formatDate(dateString: string | null): string {
  if (!dateString) return 'No activity';

  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

  if (diffInSeconds < 60) {
    return 'just now';
  }

  const diffInMinutes = Math.floor(diffInSeconds / 60);
  if (diffInMinutes < 60) {
    return `${diffInMinutes}m ago`;
  }

  const diffInHours = Math.floor(diffInMinutes / 60);
  if (diffInHours < 24) {
    return `${diffInHours}h ago`;
  }

  const diffInDays = Math.floor(diffInHours / 24);
  if (diffInDays < 7) {
    return `${diffInDays}d ago`;
  }

  // Format as MM/DD/YYYY for older dates
  return date.toLocaleDateString();
}
</script>
