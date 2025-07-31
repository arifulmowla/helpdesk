<template>
  <div class="flex flex-col h-full" @click="closeMoreMenu">
    <!-- Header Component - Fixed at top -->
    <ConversationHeader 
      :conversation="conversation" 
      :users="users" 
      :statusOptions="statusOptions"
      :priorityOptions="priorityOptions"
      :isGeneratingAI="isGeneratingAI"
      @generate-ai="generateAIResponse"
      @status-updated="handleStatusUpdated"
      @priority-updated="handlePriorityUpdated"
    />

    <!-- Messages Container Component -->
    <MessagesContainer :messages="messages" />

    <!-- Reply Section Component - Fixed at bottom -->
    <ReplySection 
      :conversation="conversation" 
      ref="replySection"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import ConversationHeader from './conversation/ConversationHeader.vue';
import MessagesContainer from './conversation/MessagesContainer.vue';
import ReplySection from './conversation/ReplySection.vue';

// Import the generated ConversationData type
import '../../../types/generated.d';

// Use the generated ConversationData type from the backend
type ConversationDataType = App.Data.ConversationData;

// Define props
const props = defineProps<{
  conversation: ConversationDataType;
  messages: Array<{
    id: string;
    type: string;
    content: string;
    created_at: string;
    message_owner_name?: string;
    agent_name?: string;
  }>;
  users: Array<{
    id: string;
    name: string;
  }>;
  statusOptions: Array<{
    value: string;
    name: string;
  }>;
  priorityOptions: Array<{
    value: string;
    name: string;
  }>;
}>();

// Refs
const showMoreMenu = ref(false);
const isGeneratingAI = ref(false);
const replySection = ref<InstanceType<typeof ReplySection> | null>(null);

// Methods
function closeMoreMenu() {
  showMoreMenu.value = false;
}

function handleStatusUpdated(status: string) {
  // Handle status update if needed
  console.log('Status updated to:', status);
}

function handlePriorityUpdated(priority: string) {
  // Handle priority update if needed
  console.log('Priority updated to:', priority);
}

async function generateAIResponse() {
  if (isGeneratingAI.value) return;
  
  // Get the latest customer message
  const customerMessages = props.messages.filter(m => m.type === 'customer');
  if (customerMessages.length === 0) {
    return;
  }
  
  const latestMessage = customerMessages[customerMessages.length - 1];
  // Strip HTML tags and get plain text
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = latestMessage.content;
  const query = tempDiv.textContent || tempDiv.innerText || '';
  
  if (!query.trim()) {
    return;
  }
  
  // Ensure reply tab is active and form is expanded
  if (replySection.value) {
    replySection.value.setActiveTab('reply');
    replySection.value.expandForm();
  }
  
  isGeneratingAI.value = true;
  
  try {
    const requestData = {
      query: query.trim(),
      conversation_id: props.conversation.id,
      conversation_context: {
        subject: props.conversation.subject,
        contact: props.conversation.contact
      },
      messages: props.messages
    };
    
    const response = await fetch(route('ai.answer.generate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(requestData)
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const data = await response.json();
    console.log('[AI Debug] Full response structure:', data);
    
    // Handle the nested data structure correctly
    if (data.success && data.data && data.data.answer) {
      console.log('[AI Debug] Response received successfully:', data.data.answer.substring(0, 50) + '...');
      
      // Format the content for TipTap
      const content = data.data.answer;
      const formattedContent = content.replace(/\n/g, '<br>');
      
      // Set the content in the reply section
      if (replySection.value) {
        replySection.value.setReplyContent(formattedContent);
      }
    } else {
      throw new Error(data.error || 'Failed to generate AI response');
    }
  } catch (error) {
    console.log('[AI Debug] Error generating response:', error);
    // You could show a toast notification here for the error
  } finally {
    isGeneratingAI.value = false;
  }
}
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
