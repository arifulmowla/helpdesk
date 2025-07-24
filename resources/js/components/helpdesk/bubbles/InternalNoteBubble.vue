<template>
  <div class="flex flex-col items-end">
    <div class="rounded-lg p-4 max-w-2xl" 
         style="background-color: hsl(var(--secondary-bg, 45 100% 96%)); border: 1px solid hsl(var(--secondary-border, 45 70% 85%)); color: hsl(var(--secondary-fg, 45 60% 30%));">
      <!-- Message Header -->
      <div class="flex items-center gap-3 mb-3">
        <div class="flex items-center gap-2">
          <!-- Note icon -->
          <div class="h-6 w-6 rounded-full flex items-center justify-center" 
               style="background-color: hsl(var(--secondary-bg, 45 100% 96%) / 0.7); color: hsl(var(--secondary-fg, 45 60% 30%));">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <span class="font-semibold text-sm">{{ message.agent_name || currentAgent.name }}</span>
          <span class="text-xs px-1.5 py-0.5 rounded-md border" 
                style="background-color: hsl(var(--secondary-bg, 45 100% 96%) / 0.2); color: hsl(var(--secondary-fg, 45 60% 30%)); border-color: hsl(var(--secondary-border, 45 70% 85%) / 0.3);">
            Internal Note
          </span>
        </div>
        <span class="text-xs opacity-60 ml-auto font-medium">{{ formatDate(message.created_at) }}</span>
      </div>

      <!-- Message Content -->
      <div class="text-sm leading-relaxed" v-html="message.content"></div>
      
      <!-- Internal note indicator -->
      <div class="mt-2 text-xs italic flex items-center" style="color: hsl(var(--secondary-fg, 45 60% 30%) / 0.8);">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
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
    type: 'internal';
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
