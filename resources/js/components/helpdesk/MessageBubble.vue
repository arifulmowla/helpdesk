<template>
  <div 
    class="flex"
    :class="[
      message.type === 'customer' ? 'justify-start' : 'justify-end'
    ]"
  >
    <div 
      class="max-w-3/4 rounded-lg p-4"
      :class="[
        message.type === 'customer' ? 'bg-gray-100 text-gray-900' : 
        message.type === 'support' ? 'bg-amber-50 text-amber-900' : 
        'bg-blue-50 text-blue-900'
      ]"
    >
      <!-- Message header -->
      <div class="flex items-center mb-1">
        <span 
          class="text-xs font-medium"
          :class="[
            message.type === 'customer' ? 'text-gray-600' : 
            message.type === 'support' ? 'text-amber-600' : 
            'text-blue-600'
          ]"
        >
          {{ messageTypeLabels[message.type] }}
        </span>
        <span class="mx-1 text-xs">•</span>
        <span class="text-xs text-gray-500">{{ formatDate(message.created_at) }}</span>
      </div>
      
      <!-- Message content -->
      <div class="whitespace-pre-wrap">{{ message.content }}</div>
      
      <!-- Internal note indicator -->
      <div v-if="message.type === 'internal'" class="mt-2 text-xs text-blue-600 italic">
        Only visible to staff
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';

// Define props
const props = defineProps<{
  message: {
    id: string;
    conversation_id: string;
    type: 'customer' | 'support' | 'internal';
    content: string;
    created_at: string;
  };
}>();

// Message type labels with agent info
const messageTypeLabels = {
  customer: 'Customer',
  support: 'Support (You)',
  internal: 'Internal Note (You)'
};

// In a real app, this would come from auth().user() or similar
const currentAgent = {
  name: 'You',
  id: '1'
};

// Format date to relative time (e.g., "2 hours ago")
function formatDate(dateString: string): string {
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
