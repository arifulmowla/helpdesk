<template>
  <div class="border-t bg-card p-4">
    <!-- Tab Buttons -->
    <div class="flex gap-1 mb-4 p-1 bg-muted/50 rounded-lg">
      <button
        class="flex-1 py-1.5 px-3 rounded-md text-sm font-medium transition-colors"
        :class="activeTab === 'reply' ? 'bg-white shadow-sm' : 'hover:bg-muted/80'"
        @click="activeTab = 'reply'"
      >
        Reply
      </button>
      <button
        class="flex-1 py-1.5 px-3 rounded-md text-sm font-medium transition-colors"
        :class="activeTab === 'note' ? 'bg-white shadow-sm' : 'hover:bg-muted/80'"
        @click="activeTab = 'note'"
      >
        Internal Note
      </button>
    </div>

    <!-- Form Content -->
    <ConversationFormReply
      v-if="activeTab === 'reply'"
      :conversation-id="conversationId"
      @message-sent="handleMessageSent"
    />
    <ConversationFormInternalNote
      v-else
      :conversation-id="conversationId"
      @message-sent="handleMessageSent"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import ConversationFormReply from './ConversationFormReply.vue';
import ConversationFormInternalNote from './ConversationFormInternalNote.vue';

// Define props
const props = defineProps<{
  conversationId: string;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'message-sent'): void;
}>();

// Local state
const activeTab = ref<'reply' | 'note'>('reply');

// Methods
const handleMessageSent = () => {
  emit('message-sent');
};
</script>
