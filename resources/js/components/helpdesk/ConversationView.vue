<template>
  <div class="flex flex-col h-full">
    <!-- Header - Fixed at top -->
    <div class="p-4 border-b bg-card shrink-0">
      <div class="flex items-start justify-between mb-3">
        <div>
          <h1 class="text-xl font-semibold mb-1">{{ conversation.subject }}</h1>
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <span>{{ conversation.contact.name }}</span>
            <span>•</span>
            <span>{{ conversation.contact.email }}</span>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 text-xs font-medium rounded-md bg-status-open text-foreground">
            #{{ conversation.id.substring(0, 8) }}
          </span>
        </div>
      </div>

      <!-- Status Controls -->
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium">Status:</span>
          <select 
            v-model="status" 
            @change="onUpdateStatus"
            class="w-36 border-2 hover:border-primary/40 transition-colors rounded-md px-3 py-1 text-sm"
          >
            <option value="open" class="font-medium">Open</option>
            <option value="pending" class="font-medium">Pending</option>
            <option value="resolved" class="font-medium">Resolved</option>
            <option value="closed" class="font-medium">Closed</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-sm font-medium">Priority:</span>
          <select 
            v-model="priority" 
            @change="onUpdatePriority"
            class="w-36 border-2 hover:border-primary/40 transition-colors rounded-md px-3 py-1 text-sm"
          >
            <option value="low" class="font-medium">Low</option>
            <option value="medium" class="font-medium">Medium</option>
            <option value="high" class="font-medium">High</option>
            <option value="urgent" class="font-medium">Urgent</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Scrollable Messages Container -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4">
      <div v-for="message in messages" :key="message.id" class="mb-4 last:mb-0">
        <CustomerBubble 
          v-if="message.type === 'customer'" 
          :message="message as CustomerMessage" 
        />
        <AgentBubble 
          v-else-if="message.type === 'support'" 
          :message="message as AgentMessage" 
        />
        <InternalNoteBubble 
          v-else-if="message.type === 'internal'" 
          :message="message as InternalMessage" 
        />
      </div>
    </div>

    <!-- Reply Section - Fixed at bottom -->
    <div class="border-t bg-card p-4 shrink-0 sticky bottom-0">
      <!-- Tab Buttons -->
      <div class="flex gap-1 mb-4 p-1 bg-muted/50 rounded-lg">
        <button
          :class="`flex items-center gap-2 flex-1 transition-all px-3 py-2 rounded-md text-sm ${
            activeTab === 'reply' 
              ? 'bg-primary text-primary-foreground shadow-sm' 
              : 'hover:bg-muted text-muted-foreground'
          }`"
          @click="setActiveTab('reply')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          Reply to Customer
        </button>
        <button
          :class="`flex items-center gap-2 flex-1 transition-all px-3 py-2 rounded-md text-sm ${
            activeTab === 'internal' 
              ? 'bg-primary text-primary-foreground shadow-sm' 
              : 'hover:bg-muted text-muted-foreground'
          }`"
          @click="setActiveTab('internal')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Internal Note
        </button>
      </div>

      <!-- Reply Form -->
      <div v-if="activeTab === 'reply'" class="space-y-3">
        <textarea
          v-model="replyContent"
          placeholder="Type your reply to the customer..."
          rows="4"
          class="w-full resize-none border-2 focus:border-primary/50 transition-colors rounded-md p-3"
        ></textarea>
        <div class="flex justify-end">
          <button 
            @click="handleSendReply"
            :disabled="!replyContent.trim()"
            class="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md disabled:opacity-50"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            Send Reply
          </button>
        </div>
      </div>

      <!-- Internal Note Form -->
      <div v-if="activeTab === 'internal'" class="space-y-3">
        <textarea
          v-model="internalNote"
          placeholder="Add an internal note (only visible to your team)..."
          rows="4"
          class="w-full resize-none border-2 border-secondary/40 focus:border-secondary/60 transition-colors rounded-md p-3"
        ></textarea>
        <div class="flex justify-end">
          <button 
            @click="handleSendInternalNote"
            :disabled="!internalNote.trim()"
            class="flex items-center gap-2 px-4 py-2 border border-secondary/60 text-secondary-foreground rounded-md disabled:opacity-50"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Add Internal Note
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import CustomerBubble from './bubbles/CustomerBubble.vue';
import AgentBubble from './bubbles/AgentBubble.vue';
import InternalNoteBubble from './bubbles/InternalNoteBubble.vue';

// Define message type interfaces
interface BaseMessage {
  id: string;
  conversation_id: string;
  content: string;
  created_at: string;
}

interface CustomerMessage extends BaseMessage {
  type: 'customer';
  customer_name?: string;
}

interface AgentMessage extends BaseMessage {
  type: 'support';
  agent_name?: string;
}

interface InternalMessage extends BaseMessage {
  type: 'internal';
  agent_name?: string;
}

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
  messages: Array<{
    id: string;
    conversation_id: string;
    type: 'customer' | 'support' | 'internal';
    content: string;
    created_at: string;
    customer_name?: string;
    agent_name?: string;
  }>;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'message-sent', data: { type: 'customer' | 'support' | 'internal'; content: string; conversation_id: string }): void;
  (e: 'status-updated', data: { status: string; conversation_id: string }): void;
  (e: 'priority-updated', data: { priority: string; conversation_id: string }): void;
}>();

// State
const messagesContainer = ref<HTMLElement | null>(null);
const activeTab = ref<'reply' | 'internal'>('reply');
const replyContent = ref('');
const internalNote = ref('');
const status = ref(props.conversation.status);
const priority = ref(props.conversation.priority);

// Methods
function getMessageComponent(type: string) {
  switch (type) {
    case 'customer':
      return CustomerBubble;
    case 'internal':
      return InternalNoteBubble;
    default:
      return AgentBubble;
  }
}

function setActiveTab(tab: 'reply' | 'internal') {
  activeTab.value = tab;
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
}

function onUpdateStatus() {
  if (status.value === props.conversation.status) return;
  
  router.patch(`/helpdesk/${props.conversation.id}/status`, {
    status: status.value
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('status-updated', {
        status: status.value,
        conversation_id: props.conversation.id
      });
    },
    onError: (errors) => {
      console.error('Error updating status:', errors);
      status.value = props.conversation.status;
    }
  });
}

function onUpdatePriority() {
  if (priority.value === props.conversation.priority) return;
  
  router.post(`/helpdesk/${props.conversation.id}/priority`, {
    priority: priority.value
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('priority-updated', {
        priority: priority.value,
        conversation_id: props.conversation.id
      });
    },
    onError: (errors) => {
      console.error('Error updating priority:', errors);
      priority.value = props.conversation.priority;
    }
  });
}

function handleSendReply() {
  if (!replyContent.value.trim()) return;
  
  router.post(`/helpdesk/${props.conversation.id}/messages`, {
    type: 'support',
    content: replyContent.value
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('message-sent', {
        type: 'support',
        content: replyContent.value,
        conversation_id: props.conversation.id
      });
      replyContent.value = '';
    },
    onError: (errors) => {
      console.error('Error sending reply:', errors);
    }
  });
}

function handleSendInternalNote() {
  if (!internalNote.value.trim()) return;
  
  router.post(`/helpdesk/${props.conversation.id}/messages`, {
    type: 'internal',
    content: internalNote.value
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('message-sent', {
        type: 'internal',
        content: internalNote.value,
        conversation_id: props.conversation.id
      });
      internalNote.value = '';
    },
    onError: (errors) => {
      console.error('Error sending internal note:', errors);
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

<style scoped>
/* Add any component-specific styles here */
</style>
