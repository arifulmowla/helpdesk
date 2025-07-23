<template>
  <!-- Use the appropriate bubble component based on message type -->
  <CustomerBubble v-if="message.type === 'customer'" :message="messageAsCustomer" />
  <AgentBubble v-else-if="message.type === 'agent'" :message="messageAsAgent" />
  <InternalNoteBubble v-else-if="message.type === 'internal'" :message="messageAsInternal" />
</template>

<script setup lang="ts">
import { defineProps, computed } from 'vue';
import CustomerBubble from './bubbles/CustomerBubble.vue';
import AgentBubble from './bubbles/AgentBubble.vue';
import InternalNoteBubble from './bubbles/InternalNoteBubble.vue';

// Define props
const props = defineProps<{
  message: {
    id: string;
    conversation_id: string;
    type: 'customer' | 'agent' | 'internal';
    content: string;
    created_at: string;
  };
}>();

// Type-safe computed properties for each message type
const messageAsCustomer = computed(() => {
  return {
    id: props.message.id,
    conversation_id: props.message.conversation_id,
    type: 'customer' as const,
    content: props.message.content,
    created_at: props.message.created_at
  };
});

const messageAsAgent = computed(() => {
  return {
    id: props.message.id,
    conversation_id: props.message.conversation_id,
    type: 'agent' as const,
    content: props.message.content,
    created_at: props.message.created_at
  };
});

const messageAsInternal = computed(() => {
  return {
    id: props.message.id,
    conversation_id: props.message.conversation_id,
    type: 'internal' as const,
    content: props.message.content,
    created_at: props.message.created_at
  };
});
</script>
