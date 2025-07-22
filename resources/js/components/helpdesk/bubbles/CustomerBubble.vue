<template>
  <div class="flex flex-col items-start">
    <div class="rounded-lg p-4 max-w-2xl" 
         style="background-color: hsl(var(--neutral-bg, 240 10% 96%)); border: 1px solid hsl(var(--neutral-border, 240 10% 90%)); color: hsl(var(--neutral-fg, 240 9% 9%));">
      <!-- Message Header -->
      <div class="flex items-center gap-3 mb-3">
        <div class="flex items-center gap-2">
          <!-- Customer icon -->
          <div class="h-6 w-6 rounded-full flex items-center justify-center" 
               style="background-color: hsl(var(--neutral-bg, 240 10% 96%) / 0.7); color: hsl(var(--neutral-fg, 240 9% 9%));">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <span class="font-semibold text-sm">{{ message.customer_name || 'Customer' }}</span>
          <span class="text-xs px-1.5 py-0.5 rounded-md border" 
                style="background-color: hsl(var(--neutral-bg, 240 10% 96%) / 0.2); color: hsl(var(--neutral-fg, 240 9% 9%)); border-color: hsl(var(--neutral-border, 240 10% 90%) / 0.3);">
            Customer
          </span>
        </div>
        <span class="text-xs opacity-60 ml-auto font-medium">{{ formatDate(message.created_at) }}</span>
      </div>

      <!-- Message Content -->
      <div class="text-sm leading-relaxed" v-html="message.content"></div>
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
    type: 'customer';
    content: string;
    created_at: string;
    customer_name?: string; // Optional customer name property
  };
}>();

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
