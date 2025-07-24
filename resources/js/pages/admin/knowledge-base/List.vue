<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <Heading>Knowledge Base Articles</Heading>
      <Button @click="createArticle">Create New Article</Button>
    </div>

    <!-- Articles Table -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Title
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Tags
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Author
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Updated At
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="article in articles" :key="article.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">
                {{ article.title }}
              </div>
              <div class="text-sm text-gray-500 truncate max-w-xs">
                {{ article.slug }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                :class="{
                  'bg-green-100 text-green-800': article.status === 'published',
                  'bg-yellow-100 text-yellow-800': article.status === 'draft',
                  'bg-red-100 text-red-800': article.status === 'archived'
                }"
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
              >
                {{ article.status }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex flex-wrap gap-1">
                <span 
                  v-for="tag in article.tags" 
                  :key="tag.id"
                  class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full"
                >
                  {{ tag.name }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ article.author.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(article.updated_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <Button 
                variant="outline" 
                size="sm" 
                @click="editArticle(article.id)"
              >
                Edit
              </Button>
              <Button 
                variant="outline" 
                size="sm"
                @click="togglePublish(article)"
              >
                {{ article.status === 'published' ? 'Unpublish' : 'Publish' }}
              </Button>
              <Button 
                variant="destructive" 
                size="sm"
                @click="deleteArticle(article)"
              >
                Delete
              </Button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="articles.length === 0" class="text-center py-12">
      <p class="text-gray-500 mb-4">No articles found</p>
      <Button @click="createArticle">Create your first article</Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import Button from '@/components/ui/button/Button.vue'
import Heading from '@/components/Heading.vue'

interface Tag {
  id: number
  name: string
}

interface Author {
  id: number
  name: string
}

interface Article {
  id: number
  title: string
  slug: string
  status: 'draft' | 'published' | 'archived'
  tags: Tag[]
  author: Author
  updated_at: string
}

interface Props {
  articles: Article[]
}

defineProps<Props>()

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const createArticle = () => {
  router.visit('/admin/knowledge-base/create')
}

const editArticle = (id: number) => {
  router.visit(`/admin/knowledge-base/${id}/edit`)
}

const togglePublish = (article: Article) => {
  const newStatus = article.status === 'published' ? 'draft' : 'published'
  
  router.put(`/admin/knowledge-base/${article.id}`, {
    status: newStatus
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: Show success message
    }
  })
}

const deleteArticle = (article: Article) => {
  if (confirm(`Are you sure you want to delete "${article.title}"?`)) {
    router.delete(`/admin/knowledge-base/${article.id}`, {
      onSuccess: () => {
        // Optional: Show success message
      }
    })
  }
}
</script>

