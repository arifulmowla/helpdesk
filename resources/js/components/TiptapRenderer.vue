<template>
  <div class="tiptap-content" v-html="renderedContent"></div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  content: any
}

const props = defineProps<Props>()

const renderedContent = computed(() => {
  if (!props.content) return ''
  
  // If content is already HTML string, return it
  if (typeof props.content === 'string') {
    return props.content
  }
  
  // If content is TipTap JSON, convert it to HTML
  if (typeof props.content === 'object' && props.content.type === 'doc') {
    return convertTiptapToHtml(props.content)
  }
  
  return ''
})

const convertTiptapToHtml = (node: any): string => {
  if (!node || !node.content) return ''
  
  let html = ''
  
  for (const child of node.content) {
    html += convertNodeToHtml(child)
  }
  
  return html
}

const convertNodeToHtml = (node: any): string => {
  if (!node) return ''
  
  switch (node.type) {
    case 'paragraph':
      return `<p>${convertContentToHtml(node.content || [])}</p>`
    
    case 'heading':
      const level = node.attrs?.level || 1
      return `<h${level}>${convertContentToHtml(node.content || [])}</h${level}>`
    
    case 'bulletList':
      return `<ul>${convertContentToHtml(node.content || [])}</ul>`
    
    case 'orderedList':
      return `<ol>${convertContentToHtml(node.content || [])}</ol>`
    
    case 'listItem':
      return `<li>${convertContentToHtml(node.content || [])}</li>`
    
    case 'blockquote':
      return `<blockquote>${convertContentToHtml(node.content || [])}</blockquote>`
    
    case 'codeBlock':
      const language = node.attrs?.language || ''
      return `<pre><code class="language-${language}">${convertContentToHtml(node.content || [])}</code></pre>`
    
    case 'hardBreak':
      return '<br>'
    
    case 'horizontalRule':
      return '<hr>'
    
    case 'text':
      let text = node.text || ''
      
      // Apply marks (formatting)
      if (node.marks) {
        for (const mark of node.marks) {
          switch (mark.type) {
            case 'bold':
              text = `<strong>${text}</strong>`
              break
            case 'italic':
              text = `<em>${text}</em>`
              break
            case 'underline':
              text = `<u>${text}</u>`
              break
            case 'strike':
              text = `<s>${text}</s>`
              break
            case 'code':
              text = `<code>${text}</code>`
              break
            case 'link':
              const href = mark.attrs?.href || '#'
              const target = mark.attrs?.target || '_self'
              text = `<a href="${href}" target="${target}">${text}</a>`
              break
          }
        }
      }
      
      return text
    
    default:
      // For unknown node types, try to render content if it exists
      return convertContentToHtml(node.content || [])
  }
}

const convertContentToHtml = (content: any[]): string => {
  if (!Array.isArray(content)) return ''
  
  return content.map(node => convertNodeToHtml(node)).join('')
}
</script>

<style scoped>
.tiptap-content {
  line-height: 1.6;
}

.tiptap-content :deep(h1) {
  font-size: 2rem;
  font-weight: bold;
  margin: 1.5rem 0 1rem 0;
}

.tiptap-content :deep(h2) {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 1.25rem 0 0.75rem 0;
}

.tiptap-content :deep(h3) {
  font-size: 1.25rem;
  font-weight: bold;
  margin: 1rem 0 0.5rem 0;
}

.tiptap-content :deep(p) {
  margin: 0.75rem 0;
}

.tiptap-content :deep(ul),
.tiptap-content :deep(ol) {
  margin: 0.75rem 0;
  padding-left: 1.5rem;
}

.tiptap-content :deep(li) {
  margin: 0.25rem 0;
}

.tiptap-content :deep(blockquote) {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: #6b7280;
}

.tiptap-content :deep(code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-family: ui-monospace, SFMono-Regular, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
}

.tiptap-content :deep(pre) {
  background-color: #1f2937;
  color: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1rem 0;
}

.tiptap-content :deep(pre code) {
  background-color: transparent;
  padding: 0;
  color: inherit;
}

.tiptap-content :deep(a) {
  color: #3b82f6;
  text-decoration: underline;
}

.tiptap-content :deep(a:hover) {
  color: #1d4ed8;
}

.tiptap-content :deep(hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 2rem 0;
}
</style>
