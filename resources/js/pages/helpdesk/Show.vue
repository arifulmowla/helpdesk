<template>
  <AppLayout title="Conversation">
    <template #header>
      <div class="flex justify-between items-center">
        <Breadcrumb :items="[
          { label: 'Helpdesk', href: '/helpdesk' },
          { label: conversation.subject, href: '#' }
        ]" />
      </div>
    </template>

    <div class="flex h-[calc(100vh-64px)]">
      <!-- Sidebar with conversation list -->
      <div class="w-80 border-r border-gray-200 h-full flex flex-col overflow-hidden">
        <div class="p-4 border-b border-gray-200 shrink-0">
          <h2 class="text-lg font-semibold">Conversations</h2>
        </div>
        <div class="overflow-y-auto flex-1 max-h-[calc(100vh-120px)]">
          <div v-if="conversations.data.length === 0" class="p-4 text-center text-gray-500">
            No conversations yet
          </div>
          <div v-else>
            <ConversationListItem 
              v-for="conv in conversations.data" 
              :key="conv.id"
              :conversation="conv"
              :is-active="conversation.id === conv.id"
              @click="navigateToConversation(conv)"
            />
          </div>
        </div>
      </div>
      
      <!-- Main content area with conversation details -->
      <div class="flex-1 h-full overflow-hidden">
        <ConversationView 
          class="h-full"
          :conversation="conversation" 
          :messages="messages" 
          @message-sent="handleMessageSent"
          @status-updated="handleStatusUpdated"
          @priority-updated="handlePriorityUpdated"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import ConversationView from '@/components/helpdesk/ConversationView.vue';
import ConversationListItem from '@/components/helpdesk/ConversationListItem.vue';

// Define props
const props = defineProps<{
  conversation: {
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
  };
  messages: Array<{
    id: string;
    conversation_id: string;
    type: 'customer' | 'support' | 'internal';
    content: string;
    created_at: string;
  }>;
  conversations: {
    data: Array<{
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
    }>;
    links: any;
    meta: {
      current_page: number;
      from: number;
      last_page: number;
      per_page: number;
      to: number;
      total: number;
    };
  };
}>();

// Function to navigate to a conversation
function navigateToConversation(conversation: any) {
  router.visit(`/helpdesk/${conversation.id}`);
}

// Event handlers
function handleMessageSent(data: { type: string; content: string; conversation_id: string }) {
  console.log('Message sent:', data);
  // You can add additional logic here if needed
}

function handleStatusUpdated(data: { status: string; conversation_id: string }) {
  console.log('Status updated:', data);
  // You can add additional logic here if needed
}

function handlePriorityUpdated(data: { priority: string; conversation_id: string }) {
  console.log('Priority updated:', data);
  // You can add additional logic here if needed
}
</script>
