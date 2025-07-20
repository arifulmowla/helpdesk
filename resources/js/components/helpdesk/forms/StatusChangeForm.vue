<template>
  <div class="flex items-center space-x-2">
    <Button 
      size="sm" 
      variant="outline" 
      class="text-xs"
      :class="{ 'bg-green-50 border-green-300 text-green-800': status === 'open' }"
      @click="changeStatus('open')"
    >
      Open
    </Button>
    <Button 
      size="sm" 
      variant="outline" 
      class="text-xs"
      :class="{ 'bg-yellow-50 border-yellow-300 text-yellow-800': status === 'pending' }"
      @click="changeStatus('pending')"
    >
      Pending
    </Button>
    <Button 
      size="sm" 
      variant="outline" 
      class="text-xs"
      :class="{ 'bg-gray-50 border-gray-300 text-gray-800': status === 'closed' }"
      @click="changeStatus('closed')"
    >
      Closed
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

// Define props
const props = defineProps<{
  conversationId: string;
  initialStatus: 'open' | 'pending' | 'closed';
}>();

// Define emits
const emit = defineEmits<{
  (e: 'statusChanged', data: { status: 'open' | 'pending' | 'closed'; conversation_id: string }): void;
}>();

// State
const status = ref<'open' | 'pending' | 'closed'>(props.initialStatus);

// Methods
async function changeStatus(newStatus: 'open' | 'pending' | 'closed') {
  if (status.value === newStatus) return;
  
  try {
    // Use Inertia router instead of fetch
    router.patch(`/helpdesk/${props.conversationId}/status`, {
      status: newStatus,
    }, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        // Update local state
        status.value = newStatus;
        
        // Emit event with the updated status
        emit('statusChanged', {
          status: newStatus,
          conversation_id: props.conversationId
        });
      },
      onError: (errors) => {
        console.error('Error updating status:', errors);
        // You could add error handling UI here
      }
    });
  } catch (error) {
    console.error('Error updating status:', error);
    // You could add error handling UI here
  }
}
</script>
