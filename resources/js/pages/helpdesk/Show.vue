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

    <div class="h-full">
      <ConversationView 
        :conversation="conversation" 
        :messages="messages" 
        @message-sent="handleMessageSent"
        @status-updated="handleStatusUpdated"
        @priority-updated="handlePriorityUpdated"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import ConversationView from '@/components/helpdesk/ConversationView.vue';

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
}>();

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
