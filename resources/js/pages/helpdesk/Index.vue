<template>
  <AppLayout>
    <div class="flex h-full">
      <!-- Sidebar with conversation list -->
      <div class="w-80 border-r border-gray-200 h-full flex flex-col overflow-hidden">
        <div class="p-4 border-b border-gray-200 shrink-0">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Conversations</h2>
            <!-- Collapse controls moved here -->
            <div class="flex items-center gap-2">
              <button 
                @click="toggleFilterCollapse"
                class="p-2 rounded-md hover:bg-gray-100 transition-colors"
                :title="filterCollapsed ? 'Show Filters' : 'Hide Filters'"
              >
                <svg v-if="filterCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.553.894l-4 2A1 1 0 019 20v-6.586a1 1 0 00-.293-.707L2.293 7.293A1 1 0 012 6.586V4z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <!-- Reply Form collapse toggle - only show if conversation is selected -->
              <button 
                v-if="activeConversation"
                @click="toggleReplyFormCollapse"
                class="p-2 rounded-md hover:bg-gray-100 transition-colors"
                :title="replyFormCollapsed ? 'Expand Reply Form' : 'Collapse Reply Form'"
              >
                <svg v-if="replyFormCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        <!-- Filter section - now collapsible from sidebar header -->
        <div v-if="!filterCollapsed" class="border-b border-gray-200 shrink-0">
          <ConversationFilter 
            :currentFilters="{}"
            :filterOptions="{ statuses: [], priorities: [], stats: { total: conversations.data.length, unread: 0, by_status: {}, by_priority: {} } }"
            :filteredCount="conversations.data.length"
            :hideCollapseButton="true"
          />
        </div>
        <!-- Conversation list - now gets full remaining space -->
        <div class="overflow-y-auto flex-1">
          <div v-if="conversations.data.length === 0" class="p-4 text-center text-gray-500">
            No conversations yet
          </div>
          <div v-else>
            <ConversationListItem 
              v-for="conversation in conversations.data" 
              :key="conversation.id"
              :conversation="conversation"
              :is-active="activeConversation?.id === conversation.id"
              @click="selectConversation(conversation)"
            />
            
            <!-- Infinite Scroll Trigger -->
            <div
              v-if="hasMorePages"
              ref="loadTrigger"
              class="flex items-center justify-center p-4"
            >
              <div v-if="isLoadingMore" class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-600"></div>
                <span class="text-sm text-gray-500">Loading more conversations...</span>
              </div>
              <div v-else class="text-xs text-gray-400">
                Scroll to load more...
              </div>
            </div>
            <div
              v-else-if="conversations.data.length > 0"
              class="text-center py-6 text-gray-600"
            >
              You've reached the end!
            </div>
          </div>
        </div>
      </div>
      
      <!-- Main content area with conversation details -->
      <div class="flex-1 h-full overflow-hidden">
        <div v-if="!activeConversation" class="flex items-center justify-center h-full text-gray-500">
          Select a conversation to view details
        </div>
        <template v-else>
          <ConversationView 
            class="h-full"
            :conversation="activeConversation" 
            :messages="activeConversationMessages" 
            @message-sent="fetchMessages"
            @status-updated="handleStatusChanged"
            @priority-updated="handlePriorityChanged"
          />
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onMounted, onUnmounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ConversationListItem from '@/components/helpdesk/ConversationListItem.vue';
import ConversationView from '@/components/helpdesk/ConversationView.vue';
import ConversationFilter from '@/components/helpdesk/ConversationFilter.vue';
import { navigateToConversation } from '@/utils/inertiaNavigation';
import { useConversationCollapseState } from '@/composables/useConversationCollapseState';

const page = usePage();
const loadTrigger = ref<HTMLElement | null>(null);
const isLoadingMore = ref(false);

// Define conversation type
interface Conversation {
  id: string;
  subject: string;
  status: string; 
  priority: string; 
  contact: {
    id: string;
    name: string;
    email: string;
    company: string | null;
  };
  last_activity_at: string;
  created_at: string;
}

interface Message {
  id: string;
  conversation_id: string;
  type: 'customer' | 'agent' | 'internal';
  content: string;
  created_at: string;
}

// Define props
const props = defineProps<{
  conversations: {
    data: Array<Conversation>;
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
    first_page_url?: string;
    last_page_url?: string;
    next_page_url?: string;
    prev_page_url?: string;
    path?: string;
    links?: any;
  };
}>();

// Initialize collapse state
const { filterCollapsed, toggleFilterCollapse, replyFormCollapsed, toggleReplyFormCollapse } = useConversationCollapseState();

// Computed property to check if there are more pages
const hasMorePages = computed(() => {
  return props.conversations.current_page < props.conversations.last_page;
});

// Load more conversations function for Index page
const loadMoreConversations = () => {
  if (isLoadingMore.value || !hasMorePages.value) return;
  
  isLoadingMore.value = true;
  const nextPage = props.conversations.current_page + 1;
  
  // Loading next page of conversations for Index
  
  router.get('/helpdesk', {
    page: nextPage
  }, {
    only: ['conversations'],
    preserveState: true,
    preserveScroll: true,
    preserveUrl: true, // This prevents URL updates
    onSuccess: () => {
      isLoadingMore.value = false;
      // Successfully loaded next page for Index
    },
    onError: (error) => {
      isLoadingMore.value = false;
      // Handle error loading more conversations
      // Consider adding a user-visible error notification here
    }
  });
};

// Watch for changes in conversations data and merge new pages
watch(() => page.props.conversations, (newConversations, oldConversations) => {
  if (!newConversations || !oldConversations) return;
  
  // Check if this is an infinite scroll load (new page > old page)
  if (newConversations.current_page > oldConversations.current_page) {
    // Merge conversations from new page with existing conversations in Index
    
    // Merge the new conversations with the existing ones
    const existingIds = new Set((oldConversations.data || []).map(c => c.id));
    const newData = (newConversations.data || []).filter(c => !existingIds.has(c.id));
    
    if (newData.length > 0) {
      // Update the conversations data by merging
      const mergedConversations = {
        ...newConversations,
        data: [...(oldConversations.data || []), ...newData]
      };
      
      // Update the page props directly to trigger reactivity
      Object.assign(page.props.conversations, mergedConversations);
    }
  }
}, { deep: true });

// Intersection Observer for infinite scroll
let observer: IntersectionObserver | null = null;

// Local state
const activeConversation = ref<Conversation | null>(null);
const activeConversationMessages = ref<Message[]>([]);

// Methods
const selectConversation = (conversation: Conversation) => {
  activeConversation.value = conversation;
  fetchMessages();
};

const fetchMessages = () => {
  if (!activeConversation.value) return;
  
  // Use the navigation utility to maintain collapse states
  navigateToConversation(activeConversation.value.id);
};

const scrollToBottom = async () => {
  await nextTick();
  const messagesContainer = document.querySelector('.messages-container');
  if (messagesContainer) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
};

// Handle status change
const handleStatusChanged = (data: {
  status: string;
  conversation_id: string;
}) => {
  // Status changed event received
  
  // Update the active conversation's status
  if (activeConversation.value && activeConversation.value.id === data.conversation_id) {
    activeConversation.value.status = data.status;
    activeConversation.value.last_activity_at = new Date().toISOString();
  }
  
  // Update the conversation in the list
  const conversationIndex = props.conversations.data.findIndex(c => c.id === data.conversation_id);
  if (conversationIndex !== -1) {
    props.conversations.data[conversationIndex].status = data.status;
    props.conversations.data[conversationIndex].last_activity_at = new Date().toISOString();
  }
};

// Handle priority change
const handlePriorityChanged = (data: {
  priority: string;
  conversation_id: string;
}) => {
  // Priority changed event received
  
  // Update the active conversation's priority
  if (activeConversation.value && activeConversation.value.id === data.conversation_id) {
    activeConversation.value.priority = data.priority;
    activeConversation.value.last_activity_at = new Date().toISOString();
  }
  
  // Update the conversation in the list
  const conversationIndex = props.conversations.data.findIndex(c => c.id === data.conversation_id);
  if (conversationIndex !== -1) {
    props.conversations.data[conversationIndex].priority = data.priority;
    props.conversations.data[conversationIndex].last_activity_at = new Date().toISOString();
  }
};

// Watchers
watch(() => activeConversation.value, (newVal) => {
  if (newVal) fetchMessages();
});

watch(() => activeConversationMessages.value, () => {
  scrollToBottom();
}, { deep: true });

// Lifecycle hooks
onMounted(() => {
  // If there are conversations, select the first one by default
  if (props.conversations.data.length > 0) {
    selectConversation(props.conversations.data[0]);
  }
  
  // Set up intersection observer for infinite scroll
  if (loadTrigger.value) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && hasMorePages.value && !isLoadingMore.value) {
            loadMoreConversations();
          }
        });
      },
      {
        rootMargin: '100px', // Load when 100px away from trigger
        threshold: 0.1
      }
    );
    
    observer.observe(loadTrigger.value);
  }
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
  }
});

// Watch for loadTrigger changes to re-observe
watch(loadTrigger, (newTrigger, oldTrigger) => {
  if (observer) {
    if (oldTrigger) observer.unobserve(oldTrigger);
    if (newTrigger) observer.observe(newTrigger);
  }
});
</script>
