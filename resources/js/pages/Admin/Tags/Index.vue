<template>
  <div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <Heading>Tag Management</Heading>
        <p class="text-muted-foreground mt-1">Manage knowledge base tags and their usage.</p>
      </div>
      <div class="space-x-2">
        <Button @click="showCreateModal = true">
          <Plus class="w-4 h-4 mr-2" />
          Create Tag
        </Button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-4">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <Input
            v-model="filters.search"
            placeholder="Search tags..."
            @input="handleSearch"
            class="max-w-sm"
          />
        </div>
        <div class="flex items-center gap-2">
          <!-- Select component temporarily disabled -->
          <span class="text-sm text-muted-foreground">{{ filters.per_page }} per page</span>
        </div>
      </div>
    </div>

    <!-- Tags Table -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left p-4 font-medium text-gray-900 cursor-pointer hover:bg-gray-100"
                  @click="sort('name')">
                <div class="flex items-center gap-2">
                  Name
                  <ArrowUpDown class="w-4 h-4 text-gray-400" />
                </div>
              </th>
              <th class="text-left p-4 font-medium text-gray-900">Slug</th>
              <th class="text-left p-4 font-medium text-gray-900 cursor-pointer hover:bg-gray-100"
                  @click="sort('knowledge_base_articles_count')">
                <div class="flex items-center gap-2">
                  Articles
                  <ArrowUpDown class="w-4 h-4 text-gray-400" />
                </div>
              </th>
              <th class="text-left p-4 font-medium text-gray-900 cursor-pointer hover:bg-gray-100"
                  @click="sort('created_at')">
                <div class="flex items-center gap-2">
                  Created
                  <ArrowUpDown class="w-4 h-4 text-gray-400" />
                </div>
              </th>
              <th class="text-right p-4 font-medium text-gray-900">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="tag in tags.data" :key="tag.id" class="hover:bg-gray-50">
              <td class="p-4">
                <div class="font-medium text-gray-900">{{ tag.name }}</div>
              </td>
              <td class="p-4 text-gray-600">{{ tag.slug }}</td>
              <td class="p-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ tag.articles_count }}
                </span>
              </td>
              <td class="p-4 text-gray-600">
                {{ formatDate(tag.created_at) }}
              </td>
              <td class="p-4 text-right space-x-2">
                <Button 
                  variant="outline" 
                  size="sm"
                  @click="editTag(tag)"
                >
                  <Edit class="w-4 h-4" />
                </Button>
                <Button 
                  variant="outline" 
                  size="sm"
                  class="text-red-600 hover:text-red-700"
                  @click="confirmDelete(tag)"
                  :disabled="tag.articles_count > 0"
                >
                  <Trash2 class="w-4 h-4" />
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Empty State -->
      <div v-if="tags.data.length === 0" class="text-center py-12">
        <Tag class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">No tags found</h3>
        <p class="text-gray-500 mb-4">
          {{ filters.search ? 'No tags match your search criteria.' : 'Get started by creating your first tag.' }}
        </p>
        <Button v-if="!filters.search" @click="showCreateModal = true">
          Create Tag
        </Button>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="tags.data.length > 0" class="flex justify-between items-center">
      <p class="text-sm text-gray-700">
        Showing {{ tags.from }} to {{ tags.to }} of {{ tags.total }} results
      </p>
      <div class="flex space-x-2">
        <Button 
          v-for="link in tags.links" 
          :key="link.label"
          :variant="link.active ? 'default' : 'outline'"
          size="sm"
          :disabled="!link.url"
          @click="changePage(link.url)"
          v-html="link.label"
        />
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Dialog v-model:open="showCreateModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{{ editingTag ? 'Edit Tag' : 'Create Tag' }}</DialogTitle>
          <DialogDescription>
            {{ editingTag ? 'Update the tag details below.' : 'Create a new tag for organizing articles.' }}
          </DialogDescription>
        </DialogHeader>
        
        <form @submit.prevent="saveTag" class="space-y-4">
          <div class="space-y-2">
            <Label for="tag-name">Name</Label>
            <Input
              id="tag-name"
              v-model="tagForm.name"
              placeholder="Enter tag name"
              :class="{ 'border-red-500': tagForm.errors?.name }"
            />
            <InputError :message="tagForm.errors?.name" />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="closeModal">
              Cancel
            </Button>
            <Button type="submit" :disabled="tagForm.processing">
              {{ tagForm.processing ? 'Saving...' : (editingTag ? 'Update' : 'Create') }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Delete Tag</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete the tag "{{ tagToDelete?.name }}"? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        
        <DialogFooter>
          <Button variant="outline" @click="showDeleteDialog = false">
            Cancel
          </Button>
          <Button 
            variant="destructive" 
            @click="deleteTag"
            :disabled="deleteForm.processing"
          >
            {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Plus, Edit, Trash2, Tag, ArrowUpDown } from 'lucide-vue-next'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
// import {
//   Select,
//   SelectContent,
//   SelectItem,
//   SelectTrigger,
//   SelectValue,
// } from '@/components/ui/select'

interface Tag {
  id: number
  name: string
  slug: string
  articles_count: number
  created_at: string
}

interface PaginatedTags {
  data: Tag[]
  from: number
  to: number
  total: number
  links: Array<{
    url?: string
    label: string
    active: boolean
  }>
}

interface Props {
  tags: PaginatedTags
  currentFilters: {
    search?: string
    sort_by: string
    sort_dir: string
    per_page: number
  }
}

const props = defineProps<Props>()

// State
const showCreateModal = ref(false)
const showDeleteDialog = ref(false)
const editingTag = ref<Tag | null>(null)
const tagToDelete = ref<Tag | null>(null)

// Filters
const filters = reactive({
  search: props.currentFilters.search || '',
  sort_by: props.currentFilters.sort_by,
  sort_dir: props.currentFilters.sort_dir,
  per_page: props.currentFilters.per_page.toString(),
})

// Forms
const tagForm = useForm({
  name: '',
})

const deleteForm = useForm({})

// Methods
const handleSearch = debounce(() => {
  applyFilters()
}, 300)

const applyFilters = () => {
  router.get(route('admin.tags.index'), filters, {
    preserveState: true,
    preserveScroll: true,
  })
}

const sort = (column: string) => {
  if (filters.sort_by === column) {
    filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sort_by = column
    filters.sort_dir = 'asc'
  }
  applyFilters()
}

const changePage = (url: string | null) => {
  if (url) {
    router.get(url, {}, {
      preserveState: true,
      preserveScroll: true,
    })
  }
}

const editTag = (tag: Tag) => {
  editingTag.value = tag
  tagForm.name = tag.name
  showCreateModal.value = true
}

const closeModal = () => {
  showCreateModal.value = false
  editingTag.value = null
  tagForm.reset()
  tagForm.clearErrors()
}

const saveTag = () => {
  if (editingTag.value) {
    // Update existing tag
    tagForm.put(route('admin.tags.update', editingTag.value.id), {
      onSuccess: () => {
        closeModal()
      }
    })
  } else {
    // Create new tag
    tagForm.post(route('admin.tags.store'), {
      onSuccess: () => {
        closeModal()
      }
    })
  }
}

const confirmDelete = (tag: Tag) => {
  tagToDelete.value = tag
  showDeleteDialog.value = true
}

const deleteTag = () => {
  if (tagToDelete.value) {
    deleteForm.delete(route('admin.tags.destroy', tagToDelete.value.id), {
      onSuccess: () => {
        showDeleteDialog.value = false
        tagToDelete.value = null
      }
    })
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Utility function for debouncing
function debounce(func: Function, wait: number) {
  let timeout: NodeJS.Timeout
  return function executedFunction(...args: any[]) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}
</script>
