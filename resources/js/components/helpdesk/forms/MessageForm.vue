<template>
  <div class="border-t border-gray-200 p-4">
    <div class="flex items-center space-x-2 mb-2">
      <Button 
        size="sm" 
        variant="outline" 
        class="text-xs"
        :class="{ 'bg-amber-100 border-amber-200 hover:bg-amber-100': messageType === 'internal' }"
        @click="setMessageType('internal')"
      >
        Internal Note
      </Button>
      <Button 
        size="sm" 
        variant="outline" 
        class="text-xs"
        :class="{ 'bg-zinc-50 border-zinc-300': messageType === 'agent' }"
        @click="setMessageType('agent')"
      >
        Agent Reply
      </Button>
    </div>
    
    <form @submit.prevent="submitMessage">
      <TiptapEditor 
        ref="editorRef" 
        v-model="content" 
        @update:isEmpty="onEditorEmptyChange"
        :placeholder="placeholderText"
        :onSubmit="submitMessage" 
      />
      
      <div class="flex justify-between mt-2">
        <div class="text-sm text-gray-500">
          <span v-if="messageType === 'internal'" class="text-indigo-600">Internal note (only visible to staff)</span>
          <span v-else>Sending as agent</span>
        </div>
        <Button type="submit" :disabled="isEmpty">Send</Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import TiptapEditor from '@/components/TiptapEditor.vue';

// Define props
const props = defineProps<{
  conversationId: string;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'sent', data: { type: 'customer' | 'agent' | 'internal'; content: string; conversation_id: string }): void;
}>();

// State
const messageType = ref<'customer' | 'agent' | 'internal'>('agent');
const editorRef = ref(null);
const content = ref('');
const isEmpty = ref(true);

// Computed
const placeholderText = computed(() => {
  switch (messageType.value) {
    case 'customer':
      return 'Type customer reply...';
    case 'internal':
      return 'Type internal note (only visible to staff)...';
    default:
return 'Type agent reply...';
  }
});

// Methods
function setMessageType(type: 'customer' | 'agent' | 'internal') {
  messageType.value = type;
}

function onEditorEmptyChange(isEditorEmpty: boolean) {
  isEmpty.value = isEditorEmpty;
}

async function submitMessage() {
  if (!editorRef.value) return;
  
  // Get HTML content from Tiptap editor
  const htmlContent = editorRef.value.getHTML();
  
  if (!htmlContent || htmlContent.trim() === '<p></p>' || !htmlContent.replace(/<[^>]*>/g, '').trim()) return;
  
  try {
    // Use Inertia router instead of fetch
    router.post(`/helpdesk/${props.conversationId}/messages`, {
      type: messageType.value,
      content: htmlContent,
    }, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: (response) => {
        // Emit event with the created message
        emit('sent', {
          type: messageType.value,
          content: htmlContent,
          conversation_id: props.conversationId
        });
        
        // Reset form - clear the editor
        editorRef.value.clearContent();
      },
      onError: (errors) => {
        // Handle errors through UI feedback
        emit('sent', {
          type: 'error',
          content: 'Failed to send message. Please try again.',
          conversation_id: props.conversationId
        });
      }
    });
  } catch (error) {
    // Handle unexpected errors through UI feedback
    emit('sent', {
      type: 'error',
      content: 'An unexpected error occurred. Please try again.',
      conversation_id: props.conversationId
    });
  }
}
</script>
