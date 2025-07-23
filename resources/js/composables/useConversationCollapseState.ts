import { reactive, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { ConversationCollapseState } from '@/types';

// Global reactive store for collapse states keyed by conversation ID
const collapseStates = reactive<Record<string, ConversationCollapseState>>({});

// Default collapse state
const defaultCollapseState: ConversationCollapseState = {
  filterCollapsed: true, // Filters start collapsed by default
  replyFormCollapsed: false, // Reply form starts expanded by default
};

export function useConversationCollapseState(conversationId?: string) {
  const page = usePage();
  
  // If no conversation ID provided, use a global key for shared components
  const key = conversationId || 'global';
  
  // Initialize state for this conversation if it doesn't exist
  if (!collapseStates[key]) {
    // Try to restore from Inertia page props first
    const savedState = page.props.collapseStates?.[key] as ConversationCollapseState;
    collapseStates[key] = savedState ? { ...savedState } : { ...defaultCollapseState };
  }
  
  // Computed properties for easy access
  const filterCollapsed = computed({
    get: () => collapseStates[key].filterCollapsed,
    set: (value: boolean) => {
      collapseStates[key].filterCollapsed = value;
      persistState();
    }
  });
  
  const replyFormCollapsed = computed({
    get: () => collapseStates[key].replyFormCollapsed,
    set: (value: boolean) => {
      collapseStates[key].replyFormCollapsed = value;
      persistState();
    }
  });
  
  // Toggle functions
  const toggleFilterCollapse = () => {
    filterCollapsed.value = !filterCollapsed.value;
  };
  
  const toggleReplyFormCollapse = () => {
    replyFormCollapsed.value = !replyFormCollapsed.value;
  };
  
  // Persist state to Inertia page props for next navigation
  const persistState = () => {
    // Update the page props to include our collapse states
    if (!page.props.collapseStates) {
      page.props.collapseStates = {};
    }
    page.props.collapseStates[key] = { ...collapseStates[key] };
  };
  
  // Get state for a specific conversation (useful for bulk operations)
  const getCollapseState = (targetConversationId: string): ConversationCollapseState => {
    return collapseStates[targetConversationId] || { ...defaultCollapseState };
  };
  
  // Set state for a specific conversation
  const setCollapseState = (targetConversationId: string, state: Partial<ConversationCollapseState>) => {
    if (!collapseStates[targetConversationId]) {
      collapseStates[targetConversationId] = { ...defaultCollapseState };
    }
    Object.assign(collapseStates[targetConversationId], state);
    persistState();
  };
  
  return {
    // State
    filterCollapsed,
    replyFormCollapsed,
    
    // Actions
    toggleFilterCollapse,
    toggleReplyFormCollapse,
    
    // Utils
    getCollapseState,
    setCollapseState,
    persistState
  };
}
