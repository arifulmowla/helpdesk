<template>
  <div class="border-t border-gray-200 p-4">
    <div class="flex items-center space-x-2 mb-2">
      <Button 
        size="sm" 
        variant="outline" 
        class="text-xs"
        :class="{ 'bg-blue-50 border-blue-300': messageType === 'customer' }"
        @click="setMessageType('customer')"
      >
        Customer Reply
      </Button>
      <Button 
        size="sm" 
        variant="outline" 
        class="text-xs"
        :class="{ 'bg-blue-50 border-blue-300': messageType === 'internal' }"
        @click="setMessageType('internal')"
      >
        Internal Note
      </Button>
      <Button 
        size="sm" 
        variant="outline" 
        class="text-xs"
        :class="{ 'bg-blue-50 border-blue-300': messageType === 'support' }"
        @click="setMessageType('support')"
      >
        Support Reply
      </Button>
    </div>
    
    <form @submit.prevent="submitMessage">
      <textarea
        v-model="content"
        class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        rows="3"
        :placeholder="placeholderText"
      ></textarea>
      
      <div class="flex justify-between mt-2">
        <div class="text-sm text-gray-500">
          <span v-if="messageType === 'customer'">Sending as customer</span>
          <span v-else-if="messageType === 'internal'" class="text-blue-600">Internal note (only visible to staff)</span>
          <span v-else>Sending as support</span>
        </div>
        <Button type="submit" :disabled="!content.trim()">Send</Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

// Define props
const props = defineProps<{
  conversationId: string;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'sent', data: { type: 'customer' | 'support' | 'internal'; content: string; conversation_id: string }): void;
}>();

// State
const messageType = ref<'customer' | 'support' | 'internal'>('support');
const content = ref('');

// Computed
const placeholderText = computed(() => {
  switch (messageType.value) {
    case 'customer':
      return 'Type customer reply...';
    case 'internal':
      return 'Type internal note (only visible to staff)...';
    default:
      return 'Type support reply...';
  }
});

// Methods
function setMessageType(type: 'customer' | 'support' | 'internal') {
  messageType.value = type;
}

async function submitMessage() {
  if (!content.value.trim()) return;
  
  try {
    // Use Inertia router instead of fetch
    router.post(`/helpdesk/${props.conversationId}/messages`, {
      type: messageType.value,
      content: content.value,
    }, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: (response) => {
        // Emit event with the created message
        emit('sent', {
          type: messageType.value,
          content: content.value,
          conversation_id: props.conversationId
        });
        
        // Reset form
        content.value = '';
      },
      onError: (errors) => {
        console.error('Error sending message:', errors);
        // You could add error handling UI here
      }
    });
  } catch (error) {
    console.error('Error sending message:', error);
    // You could add error handling UI here
  }
}
</script>
