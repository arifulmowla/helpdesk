<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <Heading>{{ isEditing ? 'Edit Article' : 'Create Article' }}</Heading>
      <div class="space-x-2">
        <Button variant="outline" @click="cancel">Cancel</Button>
        <Button @click="save" :disabled="form.processing">
          {{ form.processing ? 'Saving...' : 'Save' }}
        </Button>
      </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-6 space-y-6">
      <!-- Title & Slug -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <Label for="title">Title *</Label>
          <Input 
            id="title"
            v-model="form.title" 
            placeholder="Enter article title"
            @input="updateSlug"
          />
          <InputError :message="form.errors.title" />
        </div>
        
        <div class="space-y-2">
          <Label for="slug">Slug</Label>
          <Input 
            id="slug"
            v-model="form.slug" 
            placeholder="auto-generated-slug"
            class="bg-gray-50"
            readonly
          />
          <InputError :message="form.errors.slug" />
          <p class="text-xs text-gray-500">Auto-generated from title</p>
        </div>
      </div>

      <!-- Tags -->
      <div class="space-y-2">
        <Label for="tags">Tags</Label>
        <TagMultiSelect
          v-model="form.tags"
          :available-tags="availableTags"
          placeholder="Select or create tags..."
          @tag-created="handleNewTag"
        />
        <InputError :message="form.errors.tags" />
      </div>

      <!-- Content -->
      <div class="space-y-2">
        <Label for="content">Content *</Label>
        <TiptapEditor
          v-model="form.content"
          placeholder="Write your article content here..."
          class="min-h-[400px]"
        />
        <InputError :message="form.errors.content" />
      </div>

      <!-- Publish Status -->
      <div class="flex items-center space-x-2">
        <Checkbox 
          id="published"
          v-model:checked="form.published"
        />
        <Label for="published">Publish immediately</Label>
      </div>
      <InputError :message="form.errors.published" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import Checkbox from '@/components/ui/checkbox/Checkbox.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import TiptapEditor from '@/components/TiptapEditor.vue'
import TagMultiSelect from '@/components/TagMultiSelect.vue'

interface Tag {
  id: number | string
  name: string
}

interface Article {
  id?: number
  title: string
  slug: string
  content: string
  tags: Tag[]
  published: boolean
}

interface Props {
  article?: Article
  availableTags: Tag[]
}

const props = withDefaults(defineProps<Props>(), {
  article: undefined,
  availableTags: () => []
})

const isEditing = computed(() => !!props.article?.id)
const availableTags = ref([...props.availableTags])

// Initialize form with article data or defaults
const form = useForm({
  title: props.article?.title || '',
  slug: props.article?.slug || '',
  content: props.article?.content || '',
  tags: props.article?.tags || [],
  published: props.article?.published || false,
})

// Auto-generate slug from title
const updateSlug = () => {
  if (!isEditing.value) {
    form.slug = slugify(form.title)
  }
}

const slugify = (text: string): string => {
  return text
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '') // Remove special characters
    .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
    .replace(/^-+|-+$/g, '') // Remove leading/trailing hyphens
}

// Handle new tag creation
const createTag = async (name: string): Promise<Tag> => {
  try {
    const response = await fetch(route('admin.tags.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ name }),
    })
    
    if (!response.ok) {
      throw new Error('Failed to create tag')
    }
    
    const data = await response.json()
    return data.tag
  } catch (error) {
    console.error('Error creating tag:', error)
    throw error
  }
}

const handleNewTag = (newTag: Tag) => {
  // Add to available tags for future use
  availableTags.value.push(newTag)
}

// Save form
const save = () => {
  const url = isEditing.value 
    ? `/admin/knowledge-base/${props.article!.id}`
    : '/admin/knowledge-base'
  
  const method = isEditing.value ? 'put' : 'post'
  
  form[method](url, {
    onSuccess: () => {
      router.visit('/admin/knowledge-base')
    },
    onError: (errors) => {
      console.error('Form errors:', errors)
    }
  })
}

// Cancel and go back
const cancel = () => {
  if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
    router.visit('/admin/knowledge-base')
  }
}

// Watch for changes in title to update slug (only for new articles)
watch(() => form.title, updateSlug)
</script>
