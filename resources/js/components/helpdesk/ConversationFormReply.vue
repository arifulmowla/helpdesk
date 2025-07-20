<template>
  <form @submit.prevent="submitForm">
    <div class="mb-4">
      <textarea
        v-model="content"
        class="w-full border-2 border-muted rounded-md p-3 min-h-[120px] focus:border-primary/40 focus:ring-0 transition-colors"
        placeholder="Type your reply here..."
      ></textarea>
    </div>
    <div class="flex justify-end">
      <button
        type="submit"
        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        :disabled="isSubmitting || !content.trim()"
      >
        Send Reply
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Define props
const props = defineProps<{
  conversationId: string;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'message-sent'): void;
}>();

// Local state
const content = ref('');
const isSubmitting = ref(false);

// Methods
const submitForm = () => {
  if (!content.value.trim() || isSubmitting.value) {
    return;
  }

  isSubmitting.value = true;

  router.post(`/helpdesk/conversations/${props.conversationId}/messages`, {
    content: content.value,
    type: 'support'
  }, {
    preserveScroll: true,
    onSuccess: () => {
      content.value = '';
      isSubmitting.value = false;
      emit('message-sent');
    },
    onError: () => {
      isSubmitting.value = false;
    }
  });
};
</script>
