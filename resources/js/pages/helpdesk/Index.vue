<template>
  <AppLayout>
    <div class="flex h-full">
      <!-- Sidebar with conversation list -->
      <div class="w-80 border-r border-gray-200 h-full flex flex-col overflow-hidden">
        <div class="p-4 border-b border-gray-200 shrink-0">
          <h2 class="text-lg font-semibold">Conversations</h2>
        </div>
        <div class="overflow-y-auto flex-1">
          <div v-if="conversations.data.length === 0" class="p-4 text-center text-gray-500">
            No conversations yet
          </div>
          <div v-else>
            <ConversationListItem 
              v-for="conversation in conversations.data" 
              :key="conversation.id"
              :conversation="conversation"
              :is-active="activeConversation?.id === conversation.id"
              @click="selectConversation(conversation)"
            />
          </div>
        </div>
      </div>
      
      <!-- Main content area with conversation details -->
      <div class="flex-1 h-full overflow-hidden">
        <div v-if="!activeConversation" class="flex items-center justify-center h-full text-gray-500">
          Select a conversation to view details
        </div>
        <template v-else>
          <ConversationView 
            class="h-full"
            :conversation="activeConversation" 
            :messages="activeConversationMessages" 
            @message-sent="fetchMessages"
            @status-updated="handleStatusChanged"
            @priority-updated="handlePriorityChanged"
          />
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ConversationListItem from '@/components/helpdesk/ConversationListItem.vue';
import ConversationView from '@/components/helpdesk/ConversationView.vue';
import { router } from '@inertiajs/vue3';

// Define conversation type
interface Conversation {
  id: string;
  subject: string;
  status: string; 
  priority: string; 
  contact: {
    id: string;
    name: string;
    email: string;
    company: string | null;
  };
  last_activity_at: string;
  created_at: string;
}

interface Message {
  id: string;
  conversation_id: string;
  type: 'customer' | 'support' | 'internal';
  content: string;
  created_at: string;
}

// Define props
const props = defineProps<{
  conversations: {
    data: Array<Conversation>;
    meta: any;
    links: any;
  };
}>();

// Local state
const activeConversation = ref<Conversation | null>(null);
const activeConversationMessages = ref<Message[]>([]);

// Methods
const selectConversation = (conversation: Conversation) => {
  activeConversation.value = conversation;
  fetchMessages();
};

const fetchMessages = () => {
  if (!activeConversation.value) return;
  
  // Use the show route instead of the removed messages route
  router.get(`/helpdesk/${activeConversation.value.id}`, {}, {
    preserveState: true,
    preserveScroll: true,
    only: ['messages'],
    onSuccess: (page: any) => {
      activeConversationMessages.value = page.props.messages || [];
      scrollToBottom();
    }
  });
};

const scrollToBottom = async () => {
  await nextTick();
  const messagesContainer = document.querySelector('.messages-container');
  if (messagesContainer) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
};

// Handle status change
const handleStatusChanged = (data: {
  status: string;
  conversation_id: string;
}) => {
  console.log('Status changed:', data);
  
  // Update the active conversation's status
  if (activeConversation.value && activeConversation.value.id === data.conversation_id) {
    activeConversation.value.status = data.status;
    activeConversation.value.last_activity_at = new Date().toISOString();
  }
  
  // Update the conversation in the list
  const conversationIndex = props.conversations.data.findIndex(c => c.id === data.conversation_id);
  if (conversationIndex !== -1) {
    props.conversations.data[conversationIndex].status = data.status;
    props.conversations.data[conversationIndex].last_activity_at = new Date().toISOString();
  }
};

// Handle priority change
const handlePriorityChanged = (data: {
  priority: string;
  conversation_id: string;
}) => {
  console.log('Priority changed:', data);
  
  // Update the active conversation's priority
  if (activeConversation.value && activeConversation.value.id === data.conversation_id) {
    activeConversation.value.priority = data.priority;
    activeConversation.value.last_activity_at = new Date().toISOString();
  }
  
  // Update the conversation in the list
  const conversationIndex = props.conversations.data.findIndex(c => c.id === data.conversation_id);
  if (conversationIndex !== -1) {
    props.conversations.data[conversationIndex].priority = data.priority;
    props.conversations.data[conversationIndex].last_activity_at = new Date().toISOString();
  }
};

// Watchers
watch(() => activeConversation.value, (newVal) => {
  if (newVal) fetchMessages();
});

watch(() => activeConversationMessages.value, () => {
  scrollToBottom();
}, { deep: true });

// Lifecycle hooks
onMounted(() => {
  // If there are conversations, select the first one by default
  if (props.conversations.data.length > 0) {
    selectConversation(props.conversations.data[0]);
  }
});
</script>
