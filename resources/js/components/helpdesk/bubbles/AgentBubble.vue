<template>
  <div class="flex flex-col items-end">
    <div class="rounded-lg p-4 max-w-2xl" 
         style="background-color: hsl(var(--primary-bg, 200 100% 97%)); border: 1px solid hsl(var(--primary-border, 200 70% 90%)); color: hsl(var(--primary-fg, 200 50% 25%));">
      <!-- Message Header -->
      <div class="flex items-center gap-3 mb-3">
        <div class="flex items-center gap-2">
          <!-- Agent icon -->
          <div class="h-6 w-6 rounded-full flex items-center justify-center" 
               style="background-color: hsl(var(--primary-bg, 200 100% 97%) / 0.7); color: hsl(var(--primary-fg, 200 50% 25%));">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <span class="font-semibold text-sm">{{ message.agent_name || currentAgent.name }}</span>
          <span class="text-xs px-1.5 py-0.5 rounded-md border" 
                style="background-color: hsl(var(--primary-bg, 200 100% 97%) / 0.2); color: hsl(var(--primary-fg, 200 50% 25%)); border-color: hsl(var(--primary-border, 200 70% 90%) / 0.3);">
            Agent
          </span>
        </div>
        <span class="text-xs opacity-60 ml-auto font-medium">{{ formatDate(message.created_at) }}</span>
      </div>

      <!-- Message Content -->
      <div class="text-sm leading-relaxed whitespace-pre-wrap">
        {{ message.content }}
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
    type: 'agent';
    content: string;
    created_at: string;
    agent_name?: string; // Optional agent name property
  };
}>();

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
