<template>
  <div class="border-t bg-card p-4 shrink-0 sticky bottom-0 z-10">
    <!-- Compact Form (Single Line) -->
    <div v-if="!isFormExpanded" class="flex items-center gap-2">
      <div class="flex-1">
        <input
          :placeholder="activeTab === 'reply' ? 'Type a reply...' : 'Type an internal note...'"
          @click="toggleFormExpanded"
          @focus="toggleFormExpanded"
          class="w-full border-2 rounded-md px-3 py-2 text-sm focus:outline-none"
          :class="activeTab === 'reply' ? 'focus:border-primary/50' : 'border-secondary/40 focus:border-secondary/60'"
        />
      </div>
      <div class="flex items-center gap-1">
        <button
          :class="`flex items-center gap-1 transition-all px-2 py-1 rounded-md text-xs ${
            activeTab === 'reply'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'bg-muted text-muted-foreground hover:bg-muted/50'
          }`"
          @click="setActiveTab('reply')"
        >
          Reply
        </button>
        <button
          :class="`flex items-center gap-1 transition-all px-2 py-1 rounded-md text-xs ${
            activeTab === 'internal'
              ? 'bg-secondary text-secondary-foreground shadow-sm'
              : 'bg-muted text-muted-foreground hover:bg-muted/50'
          }`"
          @click="setActiveTab('internal')"
        >
          Internal Note
        </button>
        <button
          @click="toggleFormExpanded"
          class="flex items-center px-3 py-1 bg-primary text-primary-foreground rounded-md text-xs disabled:opacity-50"
        >
          Send
        </button>
      </div>
    </div>

    <!-- Expanded Form -->
    <div v-if="isFormExpanded">
      <!-- Tab Buttons -->
      <div class="flex gap-1 mb-4 p-1 bg-muted/50 rounded-lg">
        <button
          :class="`flex items-center gap-2 flex-1 transition-all px-3 py-2 rounded-md text-sm ${
            activeTab === 'reply'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'hover:bg-muted text-muted-foreground'
          }`"
          @click="setActiveTab('reply')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
          </svg>
          Reply to Contact
        </button>
        <button
          :class="`flex items-center gap-2 flex-1 transition-all px-3 py-2 rounded-md text-sm ${
            activeTab === 'internal'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'hover:bg-muted text-muted-foreground'
          }`"
          @click="setActiveTab('internal')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Internal Note
        </button>
      </div>

      <!-- Reply Form -->
      <div v-if="activeTab === 'reply'" class="space-y-3">
        <TiptapEditor
          ref="replyEditor"
          v-model="replyContent"
          placeholder="Type your reply to the contact..."
        />
        <div class="flex justify-end">
          <button
            @click="handleSendReply"
            :disabled="isReplyEmpty"
            class="flex items-center gap-1 px-3 py-1 bg-primary text-primary-foreground rounded-md text-sm disabled:opacity-50"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            Send Reply
          </button>
        </div>
      </div>

      <!-- Internal Note Form -->
      <div v-if="activeTab === 'internal'" class="space-y-3">
        <TiptapEditor
          ref="internalEditor"
          v-model="internalNote"
          placeholder="Add an internal note (only visible to your team)..."
        />
        <div class="flex justify-end">
          <button
            @click="handleSendInternalNote"
            :disabled="isInternalNoteEmpty"
            class="flex items-center gap-1 px-3 py-1 bg-secondary text-secondary-foreground rounded-md text-sm disabled:opacity-50"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Add Internal Note
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConversationCollapseState } from '@/composables/useConversationCollapseState';
import TiptapEditor from '@/components/TiptapEditor.vue';

// Define props
const props = defineProps<{
  conversation: App.Data.ConversationData;
}>();

// Define methods that will be exposed to the parent component
defineExpose({
  setActiveTab,
  expandForm,
  setReplyContent
});

// Use persistent collapse state
const { replyFormCollapsed, toggleReplyFormCollapse } = useConversationCollapseState();

// Alias for consistency with our function naming
const toggleReplyFormCollapsed = toggleReplyFormCollapse;

// Refs
const replyContent = ref('');
const internalNote = ref('');
const activeTab = ref<'reply' | 'internal'>('reply');
const replyEditor = ref<InstanceType<typeof TiptapEditor> | null>(null);
const internalEditor = ref<InstanceType<typeof TiptapEditor> | null>(null);
const isReplyEmpty = ref(true);
const isInternalNoteEmpty = ref(true);

// Computed
const isFormExpanded = computed(() => !replyFormCollapsed.value);

// Methods
function setActiveTab(tab: 'reply' | 'internal') {
  activeTab.value = tab;
}

function expandForm() {
  replyFormCollapsed.value = false;
}

function toggleFormExpanded() {
  toggleReplyFormCollapsed();
}

/**
 * Sets the content of the reply editor
 * Used by the parent component when AI generates a response
 */
function setReplyContent(content: string) {
  // Set the active tab to reply
  activeTab.value = 'reply';
  
  // Expand the form if it's collapsed
  if (replyFormCollapsed.value) {
    replyFormCollapsed.value = false;
  }
  
  // Set the content value
  replyContent.value = content;
  
  // Update the editor content
  setTimeout(() => {
    if (replyEditor.value?.editor) {
      // Use any type assertion to bypass TypeScript error
      const editor = replyEditor.value.editor as any;
      if (editor && editor.commands) {
        editor.commands.setContent(content);
        isReplyEmpty.value = false;
      }
    }
  }, 50);
}

function handleSendReply() {
  if (isReplyEmpty.value) return;
  
  router.post(route('conversations.reply', { conversation: props.conversation.id }), {
    content: replyContent.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Clear the form
      replyContent.value = '';
      if (replyEditor.value) {
        replyEditor.value.clearContent();
      }
      isReplyEmpty.value = true;
      
      // Optionally collapse the form
      replyFormCollapsed.value = true;
    },
    onError: (errors) => {
      // Handle errors
      console.error('Failed to send reply:', errors);
    }
  });
}

function handleSendInternalNote() {
  if (isInternalNoteEmpty.value) return;
  
  router.post(route('conversations.internal-note', { conversation: props.conversation.id }), {
    content: internalNote.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Clear the form
      internalNote.value = '';
      if (internalEditor.value) {
        internalEditor.value.clearContent();
      }
      isInternalNoteEmpty.value = true;
      
      // Optionally collapse the form
      replyFormCollapsed.value = true;
    },
    onError: (errors) => {
      // Handle errors
      console.error('Failed to add internal note:', errors);
    }
  });
}
</script>
