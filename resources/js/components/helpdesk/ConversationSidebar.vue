<template>
  <div class="w-80 border-r border-gray-200 h-full grid grid-rows-[auto_1fr]">
    <div class="p-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold">Conversations</h2>
    </div>
    <div class="overflow-y-auto">
      <div v-if="conversations.length === 0" class="p-4 text-center text-gray-500">
        No conversations yet
      </div>
      <ConversationSidebarItem
        v-for="conversation in conversations"
        :key="conversation.id"
        :conversation="conversation"
        :is-active="activeConversationId === conversation.id"
        @click="$emit('select-conversation', conversation)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import ConversationSidebarItem from './ConversationSidebarItem.vue';

// Define props
const props = defineProps<{
  conversations: Array<{
    id: string;
    subject: string;
    status: 'open' | 'pending' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    contact: {
      id: string;
      name: string;
      email: string;
      company: string | null;
    };
    last_activity_at: string;
    created_at: string;
  }>;
  activeConversationId: string | null;
}>();

// Define emits
defineEmits<{
  (e: 'select-conversation', conversation: any): void;
}>();
</script>
