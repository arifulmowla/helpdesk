<template>
  <div class="flex flex-col h-full" @click="closeMoreMenu">
    <!-- Header - Fixed at top -->
    <div class="p-4 border-b bg-card shrink-0">
      <div class="flex items-start justify-between mb-3">
        <div>
          <h1 class="text-xl font-semibold mb-1">{{ conversation.subject }}</h1>
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <span>{{ conversation.contact.name }}</span>
            <span>•</span>
            <span>{{ conversation.contact.email }}</span>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 text-xs font-medium rounded-md bg-status-open text-foreground">
            #{{ conversation.id.substring(0, 8) }}
          </span>

          <!-- More Actions Dropdown -->
          <div class="relative">
            <button
              @click.stop="toggleMoreMenu"
              class="p-2 hover:bg-gray-100 rounded-md transition-colors"
              title="More actions"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01" />
              </svg>
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="showMoreMenu"
              @click.stop
              class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50"
            >
              <div class="py-1">
                <button
                  @click="markAsUnread"
                  class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  Mark as unread
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Status and Priority Tags -->
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <span class="text-sm text-muted-foreground">Status:</span>
          <Tag :value="conversation.status" />
        </div>

        <div class="flex items-center gap-2">
          <span class="text-sm text-muted-foreground">Priority:</span>
          <Tag :value="conversation.priority" />
        </div>
      </div>
    </div>

    <!-- Scrollable Messages Container -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 max-h-[calc(100vh-250px)]">
      <div v-for="message in messages" :key="message.id" class="mb-4 last:mb-0">
        <CustomerBubble
          v-if="message.type === 'customer'"
          :message="message as CustomerMessage"
        />
        <AgentBubble
          v-else-if="message.type === 'agent'"
          :message="message as AgentMessage"
        />
        <InternalNoteBubble
          v-else-if="message.type === 'internal'"
          :message="message as InternalMessage"
        />
      </div>
    </div>

    <!-- Reply Section - Fixed at bottom -->
    <div class="border-t bg-card p-4 shrink-0 sticky bottom-0 z-10">

      <!-- Compact Form (Single Line) -->
      <div v-if="!isFormExpanded" class="flex items-center gap-2">
        <div class="flex-1">
          <input
            :placeholder="activeTab === 'reply' ? 'Type a reply...' : 'Type an internal note...'"
            :value="activeTab === 'reply' ? replyContent : internalNote"
            @input="handleInputChange"
            class="w-full border-2 rounded-md px-3 py-2 text-sm focus:outline-none"
            :class="activeTab === 'reply' ? 'focus:border-primary/50' : 'border-secondary/40 focus:border-secondary/60'"
          />
        </div>
        <div class="flex items-center gap-1">
          <button
            :class="`flex items-center gap-1 transition-all px-2 py-1 rounded-md text-xs ${
              activeTab === 'reply'
                ? 'bg-primary text-primary-foreground shadow-sm'
                : 'bg-muted/50 hover:bg-muted text-muted-foreground'
            }`"
            @click="setActiveTab('reply')"
          >
            Reply
          </button>
          <button
            :class="`flex items-center gap-1 transition-all px-2 py-1 rounded-md text-xs ${
              activeTab === 'internal'
                ? 'bg-primary text-primary-foreground shadow-sm'
                : 'bg-muted/50 hover:bg-muted text-muted-foreground'
            }`"
            @click="setActiveTab('internal')"
          >
            Note
          </button>
          <button
            @click="activeTab === 'reply' ? handleSendReply() : handleSendInternalNote()"
            :disabled="activeTab === 'reply' ? !replyContent.trim() : !internalNote.trim()"
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Reply to Customer
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
            placeholder="Type your reply to the customer..."
            @update:isEmpty="isReplyEmpty = $event"
            :onSubmit="handleSendReply"
          />
          <div class="flex justify-end">
            <button
              @click="handleSendReply"
              :disabled="isReplyEmpty"
              class="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md disabled:opacity-50"
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
            @update:isEmpty="isInternalNoteEmpty = $event"
            :onSubmit="handleSendInternalNote"
          />
          <div class="flex justify-end">
            <button
              @click="handleSendInternalNote"
              :disabled="isInternalNoteEmpty"
              class="flex items-center gap-2 px-4 py-2 border border-secondary/60 text-secondary-foreground rounded-md disabled:opacity-50"
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
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConversationCollapseState } from '@/composables/useConversationCollapseState';
import CustomerBubble from './bubbles/CustomerBubble.vue';
import AgentBubble from './bubbles/AgentBubble.vue';
import InternalNoteBubble from './bubbles/InternalNoteBubble.vue';
import TiptapEditor from '../TiptapEditor.vue';
import { Tag } from '@/components/ui/tag';
// Import generated types
import '@types/generated.d';

// Define message type interfaces with more specific types than the generated ones
interface CustomerMessage extends App.Data.MessageData {
  type: 'customer';
  message_owner_name?: string;
}

interface AgentMessage extends App.Data.MessageData {
  type: 'agent';
  agent_name?: string;
}

interface InternalMessage extends App.Data.MessageData {
  type: 'internal';
  agent_name?: string;
}

// Define props
const props = defineProps<{
  conversation: App.Data.ConversationData;
  messages: Array<App.Data.MessageData & {
    message_owner_name?: string;
    agent_name?: string;
  }>;
}>();

// Initialize collapse state for this specific conversation
const { replyFormCollapsed, toggleReplyFormCollapse, persistState } = useConversationCollapseState(props.conversation.id);

// Define emits
const emit = defineEmits<{
  (e: 'message-sent', data: { type: 'customer' | 'agent' | 'internal'; content: string; conversation_id: string }): void;
  (e: 'status-updated', data: { status: string; conversation_id: string }): void;
  (e: 'priority-updated', data: { priority: string; conversation_id: string }): void;
  (e: 'conversation-updated', data: { conversation: App.Data.ConversationData }): void;
}>();

// State
const messagesContainer = ref<HTMLElement | null>(null);
const activeTab = ref<'reply' | 'internal'>('reply');
const replyContent = ref('');
const internalNote = ref('');
const status = ref(props.conversation.status);
const priority = ref(props.conversation.priority);
const showMoreMenu = ref(false);
const replyEditor = ref<InstanceType<typeof TiptapEditor> | null>(null);
const internalEditor = ref<InstanceType<typeof TiptapEditor> | null>(null);
const isReplyEmpty = ref(true);
const isInternalNoteEmpty = ref(true);
// Use persistent collapse state instead of local ref
const isFormExpanded = computed(() => !replyFormCollapsed.value);

// Methods
function getMessageComponent(type: string) {
  switch (type) {
    case 'customer':
      return CustomerBubble;
    case 'internal':
      return InternalNoteBubble;
    default:
      return AgentBubble;
  }
}

function setActiveTab(tab: 'reply' | 'internal') {
  activeTab.value = tab;
}

function toggleFormExpanded() {
  toggleReplyFormCollapse();
}

function handleInputChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (activeTab.value === 'reply') {
    replyContent.value = target.value;
  } else {
    internalNote.value = target.value;
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
}

// Status and priority update functions removed since they're now handled by tags
// These functions are no longer needed as status/priority are now display-only structured data

function handleSendReply() {
  if (isReplyEmpty.value || !replyContent.value.trim()) return;

  router.post(`/helpdesk/${props.conversation.id}/messages`, {
    type: 'agent',
    content: replyContent.value
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('message-sent', {
        type: 'agent',
        content: replyContent.value,
        conversation_id: props.conversation.id
      });
      replyContent.value = '';
      replyEditor.value?.clearContent();
    },
    onError: (errors) => {
      console.error('Error sending reply:', errors);
    }
  });
}

function handleSendInternalNote() {
  if (isInternalNoteEmpty.value || !internalNote.value.trim()) return;

  router.post(`/helpdesk/${props.conversation.id}/messages`, {
    type: 'internal',
    content: internalNote.value
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('message-sent', {
        type: 'internal',
        content: internalNote.value,
        conversation_id: props.conversation.id
      });
      internalNote.value = '';
      internalEditor.value?.clearContent();
    },
    onError: (errors) => {
      console.error('Error sending internal note:', errors);
    }
  });
}

// More menu methods
function toggleMoreMenu() {
  showMoreMenu.value = !showMoreMenu.value;
}

function closeMoreMenu() {
  showMoreMenu.value = false;
}

function markAsUnread() {
  router.post(`/conversations/${props.conversation.id}/unread`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Update local state to show unread badge immediately
      emit('conversation-updated', {
        conversation: {
          ...props.conversation,
          unread: true,
          read_at: null
        }
      });
      closeMoreMenu();
    },
    onError: (errors) => {
      console.error('Error marking conversation as unread:', errors);
    }
  });
}

// Scroll to bottom when messages change
watch(() => props.messages.length, () => {
  scrollToBottom();
});

// Scroll to bottom on initial mount
onMounted(() => {
  scrollToBottom();
});
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
