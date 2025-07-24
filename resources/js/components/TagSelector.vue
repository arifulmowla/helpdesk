<template>
  <div class="space-y-3">
    <!-- Selected Tags -->
    <div v-if="selectedTags.length > 0" class="flex flex-wrap gap-2">
      <span
        v-for="(tag, index) in selectedTags"
        :key="tag.id || `new-${index}`"
        class="inline-flex items-center px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full"
      >
        {{ tag.name }}
        <button
          type="button"
          @click="removeTag(index)"
          class="ml-2 text-blue-600 hover:text-blue-800"
        >
          <XIcon class="w-3 h-3" />
        </button>
      </span>
    </div>

    <!-- Tag Input -->
    <div class="relative">
      <Input
        v-model="newTagName"
        placeholder="Type to search or create tags..."
        @input="handleInput"
        @keydown.enter.prevent="selectFirstSuggestion"
        @keydown.escape="closeSuggestions"
        @keydown.arrow-down.prevent="highlightNext"
        @keydown.arrow-up.prevent="highlightPrevious"
        :class="{ 'border-red-300': errors }"
      />
      
      <!-- Suggestions Dropdown -->
      <div
        v-if="showSuggestions && (filteredTags.length > 0 || canCreateNew)"
        class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
      >
        <!-- Existing tags -->
        <button
          v-for="(tag, index) in filteredTags"
          :key="tag.id"
          type="button"
          @click="selectTag(tag)"
          :class="[
            'w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center justify-between',
            { 'bg-blue-50': index === highlightedIndex }
          ]"
        >
          <span>{{ tag.name }}</span>
          <span class="text-xs text-gray-500">{{ tag.slug }}</span>
        </button>
        
        <!-- Create new tag option -->
        <button
          v-if="canCreateNew"
          type="button"
          @click="createNewTag"
          :class="[
            'w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center text-green-600',
            { 'bg-blue-50': filteredTags.length === highlightedIndex }
          ]"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Create "{{ newTagName }}"
        </button>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="errors" class="text-sm text-red-600">
      {{ errors }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Input from '@/components/ui/input/Input.vue'
import { XIcon, PlusIcon } from 'lucide-vue-next'

interface Tag {
  id?: number
  name: string
  slug?: string
}

interface Props {
  modelValue: Tag[]
  availableTags: Tag[]
  errors?: string
}

interface Emits {
  (e: 'update:modelValue', value: Tag[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const newTagName = ref('')
const showSuggestions = ref(false)
const highlightedIndex = ref(0)

const selectedTags = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const filteredTags = computed(() => {
  if (!newTagName.value) return []
  
  const query = newTagName.value.toLowerCase()
  const selectedIds = selectedTags.value.map(tag => tag.id).filter(Boolean)
  
  return props.availableTags
    .filter(tag => 
      tag.name.toLowerCase().includes(query) && 
      !selectedIds.includes(tag.id)
    )
    .slice(0, 10) // Limit to 10 suggestions
})

const canCreateNew = computed(() => {
  if (!newTagName.value || newTagName.value.length < 2) return false
  
  const exactMatch = props.availableTags.some(tag => 
    tag.name.toLowerCase() === newTagName.value.toLowerCase()
  )
  
  const alreadySelected = selectedTags.value.some(tag => 
    tag.name.toLowerCase() === newTagName.value.toLowerCase()
  )
  
  return !exactMatch && !alreadySelected
})

const handleInput = () => {
  showSuggestions.value = true
  highlightedIndex.value = 0
}

const selectTag = (tag: Tag) => {
  selectedTags.value = [...selectedTags.value, tag]
  newTagName.value = ''
  showSuggestions.value = false
}

const createNewTag = () => {
  if (canCreateNew.value) {
    const newTag: Tag = {
      name: newTagName.value.trim()
    }
    selectedTags.value = [...selectedTags.value, newTag]
    newTagName.value = ''
    showSuggestions.value = false
  }
}

const removeTag = (index: number) => {
  selectedTags.value = selectedTags.value.filter((_, i) => i !== index)
}

const selectFirstSuggestion = () => {
  if (filteredTags.value.length > 0 && highlightedIndex.value < filteredTags.value.length) {
    selectTag(filteredTags.value[highlightedIndex.value])
  } else if (canCreateNew.value) {
    createNewTag()
  }
}

const closeSuggestions = () => {
  showSuggestions.value = false
}

const highlightNext = () => {
  const maxIndex = filteredTags.value.length + (canCreateNew.value ? 0 : -1)
  if (highlightedIndex.value < maxIndex) {
    highlightedIndex.value++
  }
}

const highlightPrevious = () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
  }
}

// Close suggestions when clicking outside
watch(showSuggestions, (show) => {
  if (show) {
    document.addEventListener('click', closeSuggestions)
  } else {
    document.removeEventListener('click', closeSuggestions)
  }
})
</script>
