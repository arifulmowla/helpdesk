<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <Heading>Create Article</Heading>
      <Button @click="goBack" variant="outline">
        <ArrowLeftIcon class="w-4 h-4 mr-2" />
        Back to Articles
      </Button>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <form @submit.prevent="submitForm" class="p-6 space-y-6">
        <!-- Title -->
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Title *
          </label>
          <Input
            id="title"
            v-model="form.title"
            placeholder="Enter article title"
            :class="{ 'border-red-300': errors.title }"
            @input="generateSlug"
          />
          <div v-if="errors.title" class="mt-1 text-sm text-red-600">
            {{ errors.title }}
          </div>
        </div>

        <!-- Slug -->
        <div>
          <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
            Slug *
          </label>
          <Input
            id="slug"
            v-model="form.slug"
            placeholder="article-slug"
            :class="{ 'border-red-300': errors.slug }"
          />
          <div v-if="errors.slug" class="mt-1 text-sm text-red-600">
            {{ errors.slug }}
          </div>
          <div class="mt-1 text-sm text-gray-500">
            URL: {{ baseUrl }}/knowledge-base/{{ form.slug || 'article-slug' }}
          </div>
        </div>


        <!-- Tags -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tags
          </label>
          <TagSelector 
            v-model="form.tags" 
            :available-tags="tags"
            :errors="errors.tags"
          />
        </div>

        <!-- Content Editor -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Content *
          </label>
          <TiptapEditor 
            v-model="form.body"
            :error="errors.body"
          />
          <div v-if="errors.body" class="mt-1 text-sm text-red-600">
            {{ errors.body }}
          </div>
        </div>

        <!-- Publishing Options -->
        <div class="border-t pt-6">
          <div class="flex items-center space-x-4">
            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="form.is_published"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm font-medium text-gray-700">
                Publish immediately
              </span>
            </label>
          </div>
          <div v-if="form.is_published" class="mt-4">
            <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">
              Publish Date
            </label>
            <Input
              id="published_at"
              type="datetime-local"
              v-model="form.published_at"
              :class="{ 'border-red-300': errors.published_at }"
            />
            <div v-if="errors.published_at" class="mt-1 text-sm text-red-600">
              {{ errors.published_at }}
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center pt-6 border-t">
          <Button type="button" @click="goBack" variant="outline">
            Cancel
          </Button>
          <div class="flex gap-2">
            <Button 
              type="button" 
              @click="saveDraft" 
              variant="outline"
              :disabled="processing"
            >
              Save as Draft
            </Button>
            <Button 
              type="submit"
              :disabled="processing"
            >
              {{ form.is_published ? 'Publish' : 'Save' }}
            </Button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Heading from '@/components/Heading.vue'
import TagSelector from '@/components/TagSelector.vue'
import TiptapEditor from '@/components/TiptapEditorWithImages.vue'
import { ArrowLeftIcon } from 'lucide-vue-next'

defineOptions({
  layout: AppLayout,
})

interface Tag {
  id: number
  name: string
  slug: string
}

interface Props {
  tags: Tag[]
  errors?: Record<string, string>
}

const props = defineProps<Props>()

const baseUrl = window.location.origin
const processing = ref(false)

const form = reactive({
  title: '',
  slug: '',
  excerpt: '',
  body: [],
  tags: [] as Array<{id?: number, name: string}>,
  is_published: false,
  published_at: ''
})

const errors = ref(props.errors || {})

const generateSlug = () => {
  if (form.title && !form.slug) {
    form.slug = form.title
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim()
  }
}

const submitForm = () => {
  processing.value = true
  
  router.post(route('admin.knowledge-base.store'), form, {
    onSuccess: () => {
      // Redirect handled by controller
    },
    onError: (pageErrors) => {
      errors.value = pageErrors
      processing.value = false
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

const saveDraft = () => {
  const originalPublished = form.is_published
  form.is_published = false
  
  processing.value = true
  
  router.post(route('admin.knowledge-base.store'), form, {
    onSuccess: () => {
      // Redirect handled by controller
    },
    onError: (pageErrors) => {
      errors.value = pageErrors
      form.is_published = originalPublished
      processing.value = false
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

const goBack = () => {
  router.visit(route('admin.knowledge-base.index'))
}
</script>
