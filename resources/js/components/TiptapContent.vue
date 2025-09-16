<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Document from '@tiptap/extension-document'
import Paragraph from '@tiptap/extension-paragraph'
import Text from '@tiptap/extension-text'
import Bold from '@tiptap/extension-bold'
import BulletList from '@tiptap/extension-bullet-list'
import ListItem from '@tiptap/extension-list-item'

const props = defineProps<{
  content: string
}>()

const editor = useEditor({
  extensions: [
    StarterKit,
    Document,
    Paragraph,
    Text,
    Bold,
    BulletList,
    ListItem,
  ],
  content: typeof props.content === 'string' ? JSON.parse(props.content) : props.content,
  editable: false,
})
</script>

<template>
  <div class="tiptap-content">
    <EditorContent v-if="editor" :editor="editor" />
  </div>
</template>

<style>
.tiptap-content {
  font-size: 0.875rem;
  line-height: 1.5;
  color: rgb(75 85 99);
}

.tiptap-content p {
  margin-bottom: 0.75rem;
}

.tiptap-content ul {
  list-style-type: disc;
  padding-left: 1.25rem;
  margin-bottom: 0.75rem;
}

.tiptap-content li {
  margin-bottom: 0.25rem;
}

.tiptap-content strong {
  font-weight: 600;
}

.tiptap-content li p {
  margin: 0;
  display: inline;
}
</style>
