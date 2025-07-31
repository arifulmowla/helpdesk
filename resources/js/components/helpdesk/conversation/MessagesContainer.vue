<template>
  <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 max-h-[calc(100vh-250px)]">
    <div v-for="message in messages" :key="message.id" class="mb-4 last:mb-0">
      <CustomerBubble
        v-if="message.type === 'customer'"
        :message="message as CustomerMessage"
      />
      <AgentBubble
        v-else-if="message.type === 'agent'"
        :message="message as AgentMessage"
      />
      <InternalNoteBubble
        v-else-if="message.type === 'internal'"
        :message="message as InternalMessage"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue';
import CustomerBubble from '@/components/helpdesk/bubbles/CustomerBubble.vue';
import AgentBubble from '@/components/helpdesk/bubbles/AgentBubble.vue';
import InternalNoteBubble from '@/components/helpdesk/bubbles/InternalNoteBubble.vue';

// Define message type interfaces with more specific types than the generated ones
interface CustomerMessage extends App.Data.MessageData {
  type: 'customer';
}

interface AgentMessage extends App.Data.MessageData {
  type: 'agent';
  agent_name?: string;
}

interface InternalMessage extends App.Data.MessageData {
  type: 'internal';
  message_owner_name?: string;
}

// Define props
const props = defineProps<{
  messages: Array<App.Data.MessageData & {
    message_owner_name?: string;
    agent_name?: string;
  }>;
}>();

// Refs
const messagesContainer = ref<HTMLElement | null>(null);

// Methods
function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
}

// Scroll to bottom when messages change
watch(() => props.messages.length, () => {
  scrollToBottom();
});

// Scroll to bottom on initial mount
onMounted(() => {
  scrollToBottom();
});
</script>
