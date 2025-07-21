<template>
  <div 
    class="border-b border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors"
    :class="{ 'bg-blue-50': isActive }"
    @click="$emit('click')"
  >
    <div class="p-4">
      <div class="flex items-center justify-between">
        <h3 class="font-medium truncate" :class="{ 'text-blue-600': isActive }">
          {{ conversation.subject }}
        </h3>
        <span class="text-xs text-gray-500">
          {{ formatDate(conversation.last_activity_at) }}
        </span>
      </div>
      
      <div class="flex items-center text-sm text-gray-600 mt-1">
        <span class="truncate">{{ conversation.contact.name }}</span>
        <span class="mx-1">•</span>
        <span class="truncate text-gray-500">{{ conversation.contact.company || 'No company' }}</span>
      </div>
      
      <div class="flex items-center mt-2 space-x-2">
        <StatusBadge :status="conversation.status" size="sm" />
        <PriorityBadge :priority="conversation.priority" size="sm" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import StatusBadge from './StatusBadge.vue';
import PriorityBadge from './PriorityBadge.vue';

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
