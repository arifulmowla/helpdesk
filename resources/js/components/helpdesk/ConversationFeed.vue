<template>
  <div class="grid grid-rows-[auto_1fr_auto] h-full">
    <!-- Header - Fixed at top -->
    <div class="p-4 border-b bg-card">
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
    <div ref="messagesContainer" class="overflow-y-auto p-4">
      <div v-for="message in messages" :key="message.id" class="mb-4 last:mb-0">
        <CustomerBubble 
          v-if="message.type === 'customer'" 
          :message="message" 
        />
        <AgentBubble 
          v-else-if="message.type === 'support'" 
          :message="message" 
        />
        <InternalNoteBubble 
          v-else-if="message.type === 'internal'" 
          :message="message" 
        />
      </div>
    </div>

    <!-- Form Container -->
    <ConversationForm 
      :conversation-id="conversation.id"
      @message-sent="$emit('message-sent')"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import CustomerBubble from './bubbles/CustomerBubble.vue';
import AgentBubble from './bubbles/AgentBubble.vue';
import InternalNoteBubble from './bubbles/InternalNoteBubble.vue';
import ConversationForm from './ConversationForm.vue';

// Define types
interface CustomerMessage {
  id: string;
  conversation_id: string;
  type: 'customer';
  content: string;
  created_at: string;
  contact: {
    id: string;
    name: string;
    email: string;
  };
}

interface AgentMessage {
  id: string;
  conversation_id: string;
  type: 'support';
  content: string;
  created_at: string;
  user: {
    id: string;
    name: string;
  };
}

interface InternalMessage {
  id: string;
  conversation_id: string;
  type: 'internal';
  content: string;
  created_at: string;
  user: {
    id: string;
    name: string;
  };
}

type Message = CustomerMessage | AgentMessage | InternalMessage;

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
  messages: Message[];
}>();

// Define emits
const emit = defineEmits<{
  (e: 'message-sent'): void;
  (e: 'status-updated', status: string): void;
  (e: 'priority-updated', priority: string): void;
}>();

// Local state
const status = ref(props.conversation.status);
const priority = ref(props.conversation.priority);
const messagesContainer = ref<HTMLElement | null>(null);

// Methods
const scrollToBottom = async () => {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const onUpdateStatus = () => {
  router.put(`/helpdesk/conversations/${props.conversation.id}/status`, {
    status: status.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Emit event to update parent component
      emit('status-updated', status.value);
    }
  });
};

const onUpdatePriority = () => {
  router.put(`/helpdesk/conversations/${props.conversation.id}/priority`, {
    priority: priority.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Emit event to update parent component
      emit('priority-updated', priority.value);
    }
  });
};

// Watchers
watch(() => props.messages, () => {
  scrollToBottom();
}, { deep: true });

watch(() => props.conversation, () => {
  status.value = props.conversation.status;
  priority.value = props.conversation.priority;
});

// Lifecycle hooks
onMounted(() => {
  scrollToBottom();
});
</script>
