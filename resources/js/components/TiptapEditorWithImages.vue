<template>
  <div class="tiptap-editor-enhanced">
    <!-- Toolbar -->
    <div class="toolbar border-b border-gray-200 p-2 flex gap-1 flex-wrap">
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
      
      <div class="border-l border-gray-300 mx-1 h-6"></div>
      
      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
        :class="{ 'is-active': editor?.isActive('heading', { level: 1 }) }"
        class="toolbar-btn"
        title="Heading 1"
      >
        H1
      </button>
      
      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="{ 'is-active': editor?.isActive('heading', { level: 2 }) }"
        class="toolbar-btn"
        title="Heading 2"
      >
        H2
      </button>
      
      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="{ 'is-active': editor?.isActive('heading', { level: 3 }) }"
        class="toolbar-btn"
        title="Heading 3"
      >
        H3
      </button>
      
      <div class="border-l border-gray-300 mx-1 h-6"></div>
      
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
      
      <div class="border-l border-gray-300 mx-1 h-6"></div>
      
      <button
        type="button"
        @click="addLink"
        :class="{ 'is-active': editor?.isActive('link') }"
        class="toolbar-btn"
        title="Add Link"
      >
        🔗
      </button>
      
      <button
        type="button"
        @click="triggerImageUpload"
        class="toolbar-btn"
        title="Add Image"
      >
        🖼️
      </button>
      
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        @change="handleImageUpload"
        class="hidden"
      />
    </div>
    
    <!-- Editor Content -->
    <editor-content :editor="editor" class="editor-content" />
    
    <!-- Loading indicator for image upload -->
    <div v-if="uploading" class="p-2 text-sm text-gray-500 border-t">
      Uploading image...
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Editor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  modelValue: {
    type: [Object, String],
    default: () => ({})
  },
  error: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const editor = ref(null)
const fileInput = ref(null)
const uploading = ref(false)

// Initialize editor
onMounted(() => {
  editor.value = new Editor({
    extensions: [
      StarterKit,
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          class: 'text-blue-600 underline hover:text-blue-800'
        }
      }),
      Image.configure({
        inline: false,
        HTMLAttributes: {
          class: 'max-w-full h-auto rounded-lg shadow-sm'
        }
      })
    ],
    content: props.modelValue,
    onUpdate: ({ editor }) => {
      const json = editor.getJSON()
      emit('update:modelValue', json)
    }
  })
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (editor.value && JSON.stringify(editor.value.getJSON()) !== JSON.stringify(newValue)) {
    editor.value.commands.setContent(newValue)
  }
})

// Clean up
onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy()
  }
})

const addLink = () => {
  const url = window.prompt('URL')
  
  if (url) {
    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  }
}

const triggerImageUpload = () => {
  fileInput.value?.click()
}

const handleImageUpload = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  
  uploading.value = true
  
  try {
    const formData = new FormData()
    formData.append('image', file)
    
    const response = await fetch(route('admin.knowledge-base.upload-image'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      editor.value?.chain().focus().setImage({ src: data.url }).run()
    } else {
      alert('Failed to upload image: ' + (data.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Upload error:', error)
    alert('Failed to upload image. Please try again.')
  } finally {
    uploading.value = false
    // Reset file input
    if (fileInput.value) {
      fileInput.value.value = ''
    }
  }
}

// Expose methods to parent component
defineExpose({
  editor
})
</script>

<style scoped>
.tiptap-editor-enhanced {
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  overflow: hidden;
}

.tiptap-editor-enhanced.error {
  border-color: #ef4444;
}

.toolbar-btn {
  padding: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  transition: all 0.2s;
  min-width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.toolbar-btn:hover {
  background-color: #f9fafb;
  border-color: #9ca3af;
}

.toolbar-btn:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.toolbar-btn.is-active {
  background-color: #1f2937;
  color: white;
  border-color: #1f2937;
}

.editor-content {
  min-height: 200px;
  padding: 1rem;
}

/* Tiptap Editor Content Styles */
:deep(.ProseMirror) {
  outline: none;
  min-height: 200px;
  line-height: 1.6;
}

:deep(.ProseMirror p) {
  margin: 0 0 1rem 0;
}

:deep(.ProseMirror p:last-child) {
  margin-bottom: 0;
}

:deep(.ProseMirror h1) {
  font-size: 2rem;
  font-weight: 700;
  margin: 1.5rem 0 1rem 0;
  line-height: 1.2;
}

:deep(.ProseMirror h2) {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 1.25rem 0 0.75rem 0;
  line-height: 1.3;
}

:deep(.ProseMirror h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem 0;
  line-height: 1.4;
}

:deep(.ProseMirror strong) {
  font-weight: 700;
}

:deep(.ProseMirror em) {
  font-style: italic;
}

:deep(.ProseMirror code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Consolas, 'Liberation Mono', Menlo, monospace;
}

:deep(.ProseMirror pre) {
  background-color: #1f2937;
  color: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  margin: 1rem 0;
  overflow-x: auto;
}

:deep(.ProseMirror pre code) {
  background: transparent;
  padding: 0;
  color: inherit;
}

:deep(.ProseMirror ul, .ProseMirror ol) {
  padding-left: 1.5rem;
  margin: 1rem 0;
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

:deep(.ProseMirror img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  margin: 1rem 0;
  display: block;
}

:deep(.ProseMirror blockquote) {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: #6b7280;
}

/* Selection styles */
:deep(.ProseMirror .ProseMirror-selectednode) {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
</style>
