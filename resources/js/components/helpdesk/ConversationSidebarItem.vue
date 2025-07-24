<template>
  <div 
    class="border-b border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors"
    :class="{ 'bg-zinc-100': isActive }"
    @click="$emit('click')"
  >
    <div class="p-4">
      <div class="flex items-center justify-between">
        <h3 class="font-medium truncate" :class="{ 'text-primary': isActive }">
          {{ conversation.subject }}
        </h3>
        <span class="text-xs text-muted-foreground">
          {{ formatDate(conversation.last_activity_at) }}
        </span>
      </div>
      
      <div class="flex items-center text-sm text-muted-foreground mt-1">
        <span class="truncate">{{ conversation.contact.name }}</span>
        <span class="mx-1">•</span>
        <span class="truncate text-muted-foreground">{{ conversation.contact.company || 'No company' }}</span>
      </div>
      
      <div class="flex items-center mt-2 space-x-2">
        <Tag :value="conversation.status" size="sm" />
        <Tag :value="conversation.priority" size="sm" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import { Tag } from '@/components/ui/tag';

// Define props
const props = defineProps<{
  conversation: {
    id: string;
    subject: string;
    status: string; // Using string instead of enum to avoid type errors
    priority: string; // Using string instead of enum to avoid type errors
    contact: {
      id: string;
      name: string;
      email: string;
      company: string | null;
    };
    last_activity_at: string;
    created_at: string;
  };
  isActive: boolean;
}>();

// Define emits
defineEmits<{
  (e: 'click'): void;
}>();

// Format date helper function
const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffDays = Math.floor((now.getTime() - date.getTime()) / 86400000); // 24*60*60*1000
  
  if (diffDays === 0) return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  if (diffDays === 1) return 'Yesterday';
  if (diffDays < 7) return date.toLocaleDateString([], { weekday: 'short' });
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
};
</script>
