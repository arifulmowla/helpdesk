<template>
  <div class="relative">
    <div 
      class="flex flex-wrap gap-2 p-2 border border-input rounded-md bg-background min-h-[40px] cursor-text"
      @click="focusInput"
    >
      <!-- Selected tags -->
      <span
        v-for="tag in selectedTags"
        :key="tag.id"
        class="inline-flex items-center gap-1 px-2 py-1 text-sm bg-primary text-primary-foreground rounded-md"
      >
        {{ tag.name }}
        <button
          type="button"
          @click="removeTag(tag)"
          class="ml-1 hover:bg-primary/80 rounded-full p-0.5"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </span>
      
      <!-- Input for new tags -->
      <input
        ref="inputRef"
        v-model="inputValue"
        @input="handleInput"
        @keydown="handleKeydown"
        @focus="showDropdown = true"
        class="flex-1 min-w-[100px] outline-none bg-transparent"
        :placeholder="selectedTags.length === 0 ? placeholder : ''"
      />
    </div>
    
    <!-- Dropdown -->
    <div
      v-if="showDropdown && (filteredTags.length > 0 || inputValue.trim())"
      class="absolute z-10 w-full mt-1 bg-background border border-input rounded-md shadow-lg max-h-60 overflow-auto"
    >
      <!-- Existing tags -->
      <div
        v-for="tag in filteredTags"
        :key="tag.id"
        @click="addTag(tag)"
        class="px-3 py-2 hover:bg-muted cursor-pointer"
      >
        {{ tag.name }}
      </div>
      
      <!-- Create new tag option -->
      <div
        v-if="inputValue.trim() && !existingTag"
        @click="createNewTag"
        class="px-3 py-2 hover:bg-muted cursor-pointer border-t text-sm text-muted-foreground"
      >
        Create "{{ inputValue.trim() }}"
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

interface Tag {
  id: number | string
  name: string
  slug?: string
}

interface Props {
  modelValue: Tag[]
  availableTags?: Tag[]
  placeholder?: string
  allowCreate?: boolean
  createTag?: (name: string) => Promise<Tag>
}

const props = withDefaults(defineProps<Props>(), {
  availableTags: () => [],
  placeholder: 'Select tags...',
  allowCreate: true,
})

const emit = defineEmits<{
  'update:modelValue': [value: Tag[]]
  'tag-created': [tag: Tag]
}>()

const inputRef = ref<HTMLInputElement>()
const inputValue = ref('')
const showDropdown = ref(false)
const selectedTags = computed(() => props.modelValue)

const filteredTags = computed(() => {
  if (!inputValue.value.trim()) return props.availableTags
  
  const selected = selectedTags.value.map(tag => tag.id)
  return props.availableTags.filter(tag => 
    !selected.includes(tag.id) &&
    tag.name.toLowerCase().includes(inputValue.value.toLowerCase())
  )
})

const existingTag = computed(() => {
  return props.availableTags.find(tag => 
    tag.name.toLowerCase() === inputValue.value.trim().toLowerCase()
  )
})

const focusInput = () => {
  inputRef.value?.focus()
}

const handleInput = () => {
  showDropdown.value = true
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault()
    if (existingTag.value) {
      addTag(existingTag.value)
    } else if (inputValue.value.trim() && props.allowCreate) {
      createNewTag()
    }
  } else if (event.key === 'Backspace' && !inputValue.value && selectedTags.value.length > 0) {
    removeTag(selectedTags.value[selectedTags.value.length - 1])
  } else if (event.key === 'Escape') {
    showDropdown.value = false
  }
}

const addTag = (tag: Tag) => {
  if (!selectedTags.value.find(selected => selected.id === tag.id)) {
    emit('update:modelValue', [...selectedTags.value, tag])
  }
  inputValue.value = ''
  showDropdown.value = false
  nextTick(() => focusInput())
}

const removeTag = (tag: Tag) => {
  emit('update:modelValue', selectedTags.value.filter(selected => selected.id !== tag.id))
}

const createNewTag = async () => {
  if (!inputValue.value.trim()) return
  
  try {
    // If a custom createTag function is provided, use it
    if (props.createTag) {
      const newTag = await props.createTag(inputValue.value.trim())
      addTag(newTag)
      return
    }
    
    // Default behavior - create temporary tag and emit event
    const newTag: Tag = {
      id: `new-${Date.now()}`,
      name: inputValue.value.trim()
    }
    
    emit('tag-created', newTag)
    addTag(newTag)
  } catch (error) {
    console.error('Failed to create tag:', error)
    // You might want to show a toast or error message here
  }
}

// Close dropdown when clicking outside
document.addEventListener('click', (event) => {
  if (!event.target?.closest('.relative')) {
    showDropdown.value = false
  }
})
</script>
