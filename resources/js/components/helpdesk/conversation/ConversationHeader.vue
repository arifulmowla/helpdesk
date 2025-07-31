<template>
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
        @click="$emit('generate-ai')"
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
        <select
          :class="`px-2 py-1 text-xs font-medium rounded-md ${getStatusClass(status)}`"
          v-model="status"
          @change="(e) => updateStatus((e.target as HTMLSelectElement).value)"
        >
          <option 
            v-for="option in statusOptions" 
            :key="option.value" 
            :value="option.value"
            :class="getStatusClass(option.value)"
          >
            {{ option.name }}
          </option>
        </select>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-sm text-muted-foreground">Priority:</span>
        <select
          :class="`px-2 py-1 text-xs font-medium rounded-md ${getPriorityClass(priority)}`"
          v-model="priority"
          @change="(e) => updatePriority((e.target as HTMLSelectElement).value)"
        >
          <option 
            v-for="option in priorityOptions" 
            :key="option.value" 
            :value="option.value"
            :class="getPriorityClass(option.value)"
          >
            {{ option.name }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

// Import the generated ConversationData type
import '../../../../types/generated.d';

// Use the generated ConversationData type from the backend
type ConversationDataType = App.Data.ConversationData;

// Define props
const props = defineProps<{
  conversation: ConversationDataType;
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
  isGeneratingAI: boolean;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'status-updated', status: string): void;
  (e: 'priority-updated', priority: string): void;
  (e: 'generate-ai'): void;
}>();

// Refs
// Handle assigned_to as possibly an array or an object with id
const assignedTo = Array.isArray(props.conversation.assigned_to) 
  ? props.conversation.assigned_to[0] 
  : props.conversation.assigned_to;
const selectedUser = ref(assignedTo?.id || '');

// Handle status and priority as arrays
const statusValue = Array.isArray(props.conversation.status) && props.conversation.status.length > 0
  ? props.conversation.status[0]?.value || ''
  : '';
const priorityValue = Array.isArray(props.conversation.priority) && props.conversation.priority.length > 0
  ? props.conversation.priority[0]?.value || ''
  : '';
  
const status = ref<string>(statusValue);
const priority = ref<string>(priorityValue);

// Methods
function markAsUnread() {
  // Ensure conversation.id is treated as a string or number, not an array
  const conversationId = String(props.conversation.id);
  router.post(route('conversations.mark-as-unread', { conversation: conversationId }), {}, {
    preserveScroll: true
  });
}

function assignToUser() {
  // Ensure conversation.id is treated as a string or number, not an array
  const conversationId = String(props.conversation.id);
  router.post(route('conversations.assign', { conversation: conversationId }), {
    user_id: selectedUser.value || null
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Reset the select to show updated state
      selectedUser.value = selectedUser.value;
    },
    onError: () => {
      // Reset the select on error using the same logic as initialization
      const assignedTo = Array.isArray(props.conversation.assigned_to) 
        ? props.conversation.assigned_to[0] 
        : props.conversation.assigned_to;
      selectedUser.value = assignedTo?.id || '';
    }
  });
}

function updateStatus(newStatus: string) {
  // Use strict equality check with proper type handling
  if (String(status.value) === String(newStatus)) return;
  
  status.value = newStatus;
  
  // Ensure conversation.id is treated as a string or number, not an array
  const conversationId = String(props.conversation.id);
  router.post(route('conversations.update-status', { conversation: conversationId }), {
    status: newStatus
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('status-updated', newStatus);
    },
    onError: () => {
      // Reset on error using the same logic as initialization
      const statusValue = Array.isArray(props.conversation.status) && props.conversation.status.length > 0
        ? props.conversation.status[0]?.value || ''
        : '';
      status.value = statusValue;
    }
  });
}

function updatePriority(newPriority: string) {
  // Use strict equality check with proper type handling
  if (String(priority.value) === String(newPriority)) return;
  
  priority.value = newPriority;
  
  // Ensure conversation.id is treated as a string or number, not an array
  const conversationId = String(props.conversation.id);
  router.post(route('conversations.update-priority', { conversation: conversationId }), {
    priority: newPriority
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('priority-updated', newPriority);
    },
    onError: () => {
      // Reset on error using the same logic as initialization
      const priorityValue = Array.isArray(props.conversation.priority) && props.conversation.priority.length > 0
        ? props.conversation.priority[0]?.value || ''
        : '';
      priority.value = priorityValue;
    }
  });
}

function generateAIResponse() {
  emit('generate-ai');
}

function getStatusClass(statusValue: string): string {
  switch (statusValue) {
    case 'open':
      return 'bg-green-100 text-green-800';
    case 'closed':
      return 'bg-gray-100 text-gray-800';
    case 'awaiting_customer':
      return 'bg-yellow-100 text-yellow-800';
    case 'awaiting_agent':
      return 'bg-blue-100 text-blue-800';
    case 'resolved':
      return 'bg-purple-100 text-purple-800';
    case 'cancelled':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-muted text-muted-foreground';
  }
}

function getPriorityClass(priorityValue: string): string {
  switch (priorityValue) {
    case 'low':
      return 'bg-blue-100 text-blue-800';
    case 'medium':
      return 'bg-yellow-100 text-yellow-800';
    case 'high':
      return 'bg-orange-100 text-orange-800';
    case 'urgent':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-muted text-muted-foreground';
  }
}
</script>
