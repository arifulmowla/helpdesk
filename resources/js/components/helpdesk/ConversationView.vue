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
            #{{ conversation.case_number }}
          </span>

        </div>
      </div>

      <!-- Toolbar -->
      <div class="flex items-center gap-2 mb-4">
        <button
          @click="markAsUnread"
          class="px-3 py-1 bg-muted text-muted-foreground rounded-md text-xs hover:bg-muted/50"
        >
          Mark as Unread
        </button>
        <select
          v-model="selectedUser"
          @change="assignToUser"
          class="px-3 py-1 bg-muted text-muted-foreground rounded-md text-xs"
        >
          <option value="">
            {{ conversation.assigned_to ? 'Remove Assignee' : 'Assign to...' }}
          </option>
          <option
            v-for="user in users"
            :key="user.id"
            :value="user.id"
          >
            {{ user.name }}
          </option>
        </select>
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
import TiptapEditor from '@/components/TiptapEditor.vue';
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
  users: Array<{
    id: string;
    name: string;
    email: string;
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
const isGeneratingAI = ref(false);
const replyEditor = ref<InstanceType<typeof TiptapEditor> | null>(null);
const internalEditor = ref<InstanceType<typeof TiptapEditor> | null>(null);
const isReplyEmpty = ref(true);
const isInternalNoteEmpty = ref(true);
const selectedUser = ref(props.conversation.assigned_to?.id || '');
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
      emit('error', {
        message: 'Failed to send reply',
        details: errors
      });
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
      emit('error', {
        message: 'Failed to send internal note',
        details: errors
      });
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
  router.post(`/helpdesk/conversations/${props.conversation.id}/unread`, {}, {
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
    onError: () => {
      // Handle error silently or add a toast notification here if needed
    }
  });
}

function assignToUser() {
  router.post(`/helpdesk/conversations/${props.conversation.id}/assign`, {
    user_id: selectedUser.value || null
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Reset the select to show updated state
      selectedUser.value = selectedUser.value;
    },
    onError: () => {
      // Reset the select on error
      selectedUser.value = props.conversation.assigned_to?.id || '';
    }
  });
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
  activeTab.value = 'reply';
  if (!isFormExpanded.value) {
    toggleFormExpanded();
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
      
      // DIRECT APPROACH: Set the v-model value first
      replyContent.value = data.data.answer;
      
      // Force a small delay to ensure Vue reactivity has updated
      await new Promise(resolve => setTimeout(resolve, 10));
      
      // DIRECT APPROACH: Format the content for TipTap
      // Convert line breaks to HTML paragraphs
      const content = data.data.answer;
      const formattedContent = content.replace(/\n/g, '<br>');
      
      console.log('[AI Debug] Formatted content (first 50 chars):', formattedContent.substring(0, 50));
      
      // SIMPLIFIED APPROACH: First update the model, then set editor content
      // 1. Clear the editor first
      if (replyEditor.value) {
        replyEditor.value.clearContent();
      }
      
      // 2. Set the content with a delay to ensure editor is ready
      setTimeout(() => {
        try {
          if (replyEditor.value?.editor) {
            console.log('[AI Debug] Setting content via editor commands');
            // Use HTML content with <br> tags for line breaks
            replyEditor.value.editor.commands.setContent(formattedContent);
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
