<template>
  <div class="tiptap-editor">
    <!-- Toolbar -->
    <div class="toolbar border-b border-gray-200 p-1 flex gap-1 flex-wrap">
      <button
        type="button"
        @click="editor?.chain().focus().toggleBold().run()"
        :class="{ 'is-active': editor?.isActive('bold') }"
        class="toolbar-btn"
        title="Bold"
      >
        <strong>B</strong>
      </button>
      
      <button
        type="button"
        @click="editor?.chain().focus().toggleItalic().run()"
        :class="{ 'is-active': editor?.isActive('italic') }"
        class="toolbar-btn"
        title="Italic"
      >
        <em>I</em>
      </button>
      
      <button
        type="button"
        @click="editor?.chain().focus().toggleCode().run()"
        :class="{ 'is-active': editor?.isActive('code') }"
        class="toolbar-btn"
        title="Inline Code"
      >
        &lt;/&gt;
      </button>
      
      <div class="border-l border-gray-300 mx-0.5 h-6"></div>
      
      <button
        type="button"
        @click="editor?.chain().focus().toggleBulletList().run()"
        :class="{ 'is-active': editor?.isActive('bulletList') }"
        class="toolbar-btn"
        title="Bullet List"
      >
        •
      </button>
      
      <button
        type="button"
        @click="editor?.chain().focus().toggleOrderedList().run()"
        :class="{ 'is-active': editor?.isActive('orderedList') }"
        class="toolbar-btn"
        title="Ordered List"
      >
        1.
      </button>
      
      <div class="border-l border-gray-300 mx-0.5 h-6"></div>
      
      <button
        type="button"
        @click="addLink"
        :class="{ 'is-active': editor?.isActive('link') }"
        class="toolbar-btn"
        title="Add Link"
      >
        🔗
      </button>
    </div>
    
    <!-- Editor Content -->
    <editor-content :editor="editor" class="editor-content" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Start typing...',
  },
  onSubmit: {
    type: Function,
    default: null,
  }
});

const emit = defineEmits(['update:modelValue', 'update:isEmpty']);

const editor = ref(null);

// Initialize editor
onMounted(() => {
  editor.value = new Editor({
    extensions: [
      StarterKit,
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          class: 'text-blue-600 underline',
        },
      }),
    ],
    content: props.modelValue,
    onUpdate: ({ editor }) => {
      const html = editor.getHTML();
      emit('update:modelValue', html);
      
      // Check if editor is empty
      const isEmpty = html === '<p></p>' || !html.replace(/<[^>]*>/g, '').trim();
      emit('update:isEmpty', isEmpty);
    },
    editorProps: {
      handleKeyDown: (view, event) => {
        // Handle Ctrl+Enter to submit
        if (event.ctrlKey && event.key === 'Enter' && props.onSubmit) {
          event.preventDefault();
          props.onSubmit();
          return true;
        }
        return false;
      },
    },
  });
});

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue);
  }
});

// Clean up
onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy();
  }
});

// Expose methods
const getHTML = () => {
  return editor.value?.getHTML() || '';
};

const getJSON = () => {
  return editor.value?.getJSON() || {};
};

const clearContent = () => {
  editor.value?.commands.clearContent();
};

const focus = () => {
  editor.value?.commands.focus();
};

const addLink = () => {
  const url = window.prompt('URL');
  
  if (url) {
    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
  }
};

// Expose methods to parent component
defineExpose({
  getHTML,
  getJSON,
  clearContent,
  focus,
  editor,
});
</script>

<style scoped>
.tiptap-editor {
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  background: white;
}

.toolbar-btn {
  padding: 0.375rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #374151;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.25rem;
  transition: background-color 0.2s, box-shadow 0.2s;
  min-width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toolbar-btn:hover {
  background-color: #f9fafb;
}

.toolbar-btn:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}

.toolbar-btn.is-active {
  background-color: #111827;
  color: white;
}

.editor-content {
  min-height: 150px;
  padding: 1rem;
}

/* Tiptap Editor Content Styles */
:deep(.ProseMirror) {
  outline: none;
  min-height: 150px;
}

:deep(.ProseMirror p) {
  margin: 0;
  line-height: 1.5;
}

:deep(.ProseMirror p:not(:last-child)) {
  margin-bottom: 0.75rem;
}

:deep(.ProseMirror strong) {
  font-weight: 600;
}

:deep(.ProseMirror em) {
  font-style: italic;
}

:deep(.ProseMirror code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Consolas, 'Liberation Mono', Menlo, monospace;
}

:deep(.ProseMirror ul, .ProseMirror ol) {
  padding-left: 1.5rem;
  margin: 0.75rem 0;
}

:deep(.ProseMirror li) {
  margin: 0.25rem 0;
}

:deep(.ProseMirror a) {
  color: #2563eb;
  text-decoration: underline;
}

:deep(.ProseMirror a:hover) {
  color: #1d4ed8;
}
</style>
