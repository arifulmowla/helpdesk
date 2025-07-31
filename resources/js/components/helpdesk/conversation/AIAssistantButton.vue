<template>
  <button
    @click="generateAIResponse"
    :disabled="isGeneratingAI"
    class="flex items-center gap-1 px-3 py-1 bg-primary text-primary-foreground rounded-md text-xs hover:bg-primary/90 disabled:opacity-50"
  >
    <svg v-if="isGeneratingAI" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
    </svg>
    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
    </svg>
    {{ isGeneratingAI ? 'Generating...' : 'AI Assistant' }}
  </button>
</template>

<script setup lang="ts">
import { ref } from 'vue';

// Define props
const props = defineProps<{
  conversation: App.Data.ConversationData;
  messages: Array<App.Data.MessageData & {
    message_owner_name?: string;
    agent_name?: string;
  }>;
  replyEditor: any;
}>();

// Define emits
const emit = defineEmits(['toggle-form-expanded', 'set-active-tab']);

// Local state
const isGeneratingAI = ref(false);

// Methods
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
  emit('set-active-tab', 'reply');
  emit('toggle-form-expanded');
  
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
      
      // Force a small delay to ensure Vue reactivity has updated
      await new Promise(resolve => setTimeout(resolve, 10));
      
      // Format the content for TipTap
      const content = data.data.answer;
      const formattedContent = content.replace(/\n/g, '<br>');
      
      console.log('[AI Debug] Formatted content (first 50 chars):', formattedContent.substring(0, 50));
      
      // SIMPLIFIED APPROACH: First update the model, then set editor content
      // 1. Clear the editor first
      if (props.replyEditor) {
        props.replyEditor.clearContent();
      }
      
      // 2. Set the content with a delay to ensure editor is ready
      setTimeout(() => {
        try {
          if (props.replyEditor?.editor) {
            console.log('[AI Debug] Setting content via editor commands');
            // Use HTML content with <br> tags for line breaks
            props.replyEditor.editor.commands.setContent(formattedContent);
            console.log('[AI Debug] Content set successfully');
          } else {
            console.log('[AI Debug] Editor not available in setTimeout');
          }
        } catch (err) {
          console.log('[AI Debug] Error setting content:', err);
        }
      }, 50);
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
