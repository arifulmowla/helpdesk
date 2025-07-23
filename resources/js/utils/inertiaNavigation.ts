import { router } from '@inertiajs/vue3';
import { useConversationCollapseState } from '@/composables/useConversationCollapseState';

/**
 * Navigate to a conversation while preserving collapse state
 * @param conversationId - The ID of the conversation to navigate to
 * @param options - Additional Inertia options
 */
export function navigateToConversation(conversationId: string, options: any = {}) {
  // Ensure we persist the current state before navigating
  const { persistState } = useConversationCollapseState();
  persistState();

  router.visit(`/helpdesk/${conversationId}`, {
    preserveState: true,
    preserveScroll: true,
    ...options,
  });
}

/**
 * Navigate with filters while preserving collapse state
 * @param route - The route to navigate to
 * @param filters - Filter parameters
 * @param options - Additional Inertia options
 */
export function navigateWithFilters(route: string, filters: Record<string, any>, options: any = {}) {
  // Ensure we persist the current state before navigating
  const { persistState } = useConversationCollapseState();
  persistState();

  // Clean undefined values from filters
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([_, v]) => v !== undefined && v !== '' && (Array.isArray(v) ? v.length > 0 : true))
  );

  router.get(route, cleanFilters, {
    preserveState: true,
    preserveScroll: true,
    ...options,
  });
}

/**
 * Generic Inertia router wrapper that always preserves state and scroll
 * @param method - HTTP method
 * @param url - URL to navigate to
 * @param data - Data to send
 * @param options - Additional Inertia options
 */
export function inertiaRequest(
  method: 'get' | 'post' | 'put' | 'patch' | 'delete' | 'visit',
  url: string,
  data: any = {},
  options: any = {}
) {
  // Ensure we persist the current state before making the request
  const { persistState } = useConversationCollapseState();
  persistState();

  const defaultOptions = {
    preserveState: true,
    preserveScroll: true,
    ...options,
  };

  if (method === 'visit') {
    return router.visit(url, defaultOptions);
  }

  return (router as any)[method](url, data, defaultOptions);
}

/**
 * Mark a conversation as read
 * @param conversationId - The ID of the conversation to mark as read
 * @param options - Additional options for the request
 */
export function markConversationAsRead(
  conversationId: string,
  options: { 
    onSuccess?: (data: any) => void;
    onError?: (error: any) => void;
  } = {}
) {
  return inertiaRequest('post', `/helpdesk/conversations/${conversationId}/read`, {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: options.onSuccess || (() => {}),
    onError: options.onError || (() => {}),
  });
}
