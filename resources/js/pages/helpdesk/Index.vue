<template>
  <AppLayout>
    <div class="flex h-full">
      <!-- Sidebar with conversation list -->
      <div class="w-80 border-r border-gray-200 h-full overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-200">
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
      <div class="flex-1 h-full flex flex-col overflow-hidden">
        <div v-if="!activeConversation" class="flex items-center justify-center h-full text-gray-500">
          Select a conversation to view details
        </div>
        <template v-else>
          <!-- Conversation header -->
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold">{{ activeConversation.subject }}</h2>
                <div class="flex items-center text-sm text-gray-500 mt-1">
                  <span>{{ activeConversation.contact.name }}</span>
                  <span class="mx-2">•</span>
                  <span>{{ activeConversation.contact.email }}</span>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <StatusBadge :status="activeConversation.status" />
                <PriorityBadge :priority="activeConversation.priority" />
              </div>
            </div>
          </div>
          
          <!-- Messages -->
          <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <MessageBubble 
              v-for="message in activeConversationMessages" 
              :key="message.id"
              :message="message"
            />
          </div>
          
          <!-- Message input -->
          <MessageForm 
            :conversation-id="activeConversation.id" 
            @sent="handleMessageSent"
          />
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppShell from '@/components/AppShell.vue';
import ConversationListItem from '@/components/helpdesk/ConversationListItem.vue';
import MessageBubble from '@/components/helpdesk/MessageBubble.vue';
import StatusBadge from '@/components/helpdesk/StatusBadge.vue';
import PriorityBadge from '@/components/helpdesk/PriorityBadge.vue';
import MessageForm from '@/components/helpdesk/forms/MessageForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';

// Define props
const props = defineProps<{
  conversations: {
    data: Array<{
      id: string;
      subject: string;
      status: 'open' | 'pending' | 'closed';
      priority: 'low' | 'medium' | 'high';
      contact: {
        id: string;
        name: string;
        email: string;
        company: string;
      };
      last_activity_at: string;
      created_at: string;
    }>;
  };
  messages: {
    [conversationId: string]: Array<{
      id: string;
      conversation_id: string;
      type: 'customer' | 'support' | 'internal';
      content: string;
      created_at: string;
    }>;
  };
}>();

// State
const activeConversation = ref(props.conversations.data[0] || null);

// Computed
const activeConversationMessages = computed(() => {
  if (!activeConversation.value) return [];
  return props.messages[activeConversation.value.id] || [];
});

// Methods
function selectConversation(conversation: any) {
  activeConversation.value = conversation;
}

// Handle message submission
function handleMessageSent(messageData: {
  type: 'customer' | 'support' | 'internal';
  content: string;
  conversation_id: string;
}) {
  // The API call is already handled in the MessageForm component
  // Here we just need to update the UI with the new message
  console.log('Message sent:', messageData);
  
  // Create a new message object
  const newMessage = {
    id: `temp-${Date.now()}`, // This will be replaced by the actual ID from the API response
    conversation_id: messageData.conversation_id,
    type: messageData.type,
    content: messageData.content,
    created_at: new Date().toISOString(),
  };
  
  // Add to messages array
  if (!props.messages[messageData.conversation_id]) {
    props.messages[messageData.conversation_id] = [];
  }
  
  props.messages[messageData.conversation_id].push(newMessage);
  
  // Update the active conversation's last activity timestamp
  if (activeConversation.value && activeConversation.value.id === messageData.conversation_id) {
    activeConversation.value.last_activity_at = new Date().toISOString();
  }
  
  // In a production app, we would also update the conversation list to show the latest activity
}
</script>
