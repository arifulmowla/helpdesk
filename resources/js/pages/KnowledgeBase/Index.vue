<template>
  <div class="flex flex-col gap-6 p-6">
    <!-- Search Header -->
    <div class="space-y-4">
      <h1 class="text-3xl font-bold">Knowledge Base</h1>
      
      <!-- Search Bar -->
      <div class="max-w-md">
        <Input 
          v-model="searchQuery" 
          placeholder="Search articles..." 
          @input="handleSearch"
          class="w-full"
        />
      </div>
    </div>
    
    <!-- Active Filters -->
    <div class="flex flex-wrap gap-2" v-if="searchQuery || selectedTag">
      <div v-if="searchQuery" class="flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
        <span>Search: "{{ searchQuery }}"</span>
        <button @click="clearSearch" class="hover:text-blue-600">&times;</button>
      </div>
      <div v-if="currentTag" class="flex items-center gap-2 px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
        <span>Tag: {{ currentTag.name }}</span>
        <button @click="clearTag" class="hover:text-green-600">&times;</button>
      </div>
    </div>
    
    <!-- Tag Filter Chips -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="tag in tags"
        :key="tag.slug"
        @click="filterByTag(tag.slug)"
        :class="[
          'px-3 py-1 rounded-full text-sm transition-colors',
          selectedTag === tag.slug 
            ? 'bg-blue-600 text-white' 
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
        ]"
      >
        {{ tag.name }}
      </button>
    </div>

    <!-- Search Results Count -->
    <div v-if="searchQuery" class="text-gray-600">
      Found {{ articles.data.length }} results
      <span v-if="searchQuery">for "{{ searchQuery }}"</span>
    </div>

    <!-- Article Cards -->
    <div v-if="articles.data.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
      <Card v-for="article in articles.data" :key="article.id" class="hover:shadow-lg transition-shadow">
        <CardHeader>
          <CardTitle>
            <div 
              v-if="article.highlighted_title" 
              v-html="article.highlighted_title"
              class="search-highlight"
            ></div>
            <div v-else>{{ article.title }}</div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div 
            v-if="article.highlighted_excerpt" 
            v-html="article.highlighted_excerpt"
            class="text-gray-600 search-highlight"
          ></div>
          <p v-else class="text-gray-600">{{ article.excerpt }}</p>
          
          <!-- Tags -->
          <div v-if="article.tag_names.length > 0" class="flex flex-wrap gap-1 mt-3">
            <span 
              v-for="tagName in article.tag_names" 
              :key="tagName"
              class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded"
            >
              {{ tagName }}
            </span>
          </div>
        </CardContent>
        <CardFooter>
          <Link 
            :href="route('knowledge-base.show', article.slug)" 
            class="text-blue-600 hover:text-blue-800 font-medium"
          >
            Read more →
          </Link>
        </CardFooter>
      </Card>
    </div>

    <!-- No Results -->
    <div v-else class="text-center py-12">
      <div class="text-gray-500 text-lg">
        <div v-if="searchQuery">No articles found for "{{ searchQuery }}"</div>
        <div v-else-if="selectedTag">No articles found with the selected tag</div>
        <div v-else>No articles available</div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="articles.links && articles.links.length > 3" class="flex justify-center">
      <nav class="flex items-center space-x-1">
        <template v-for="link in articles.links" :key="link.url || link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            :class="[
              'px-3 py-2 text-sm',
              link.active 
                ? 'bg-blue-600 text-white rounded' 
                : 'text-blue-600 hover:bg-blue-50 rounded'
            ]"
            v-html="link.label"
          />
          <span
            v-else
            :class="[
              'px-3 py-2 text-sm text-gray-400 cursor-not-allowed',
              link.active ? 'bg-blue-600 text-white rounded' : ''
            ]"
            v-html="link.label"
          />
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useThrottleFn } from '@vueuse/core';
import Input from '@/components/ui/input/Input.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';

interface Article {
  id: string;
  title: string;
  slug: string;
  excerpt: string;
  highlighted_title?: string;
  highlighted_excerpt?: string;
  tag_names: string[];
}

const props = defineProps<{ 
  articles: { data: Article[], links: any[], current_page: number, last_page: number };
  tags: Array<{ name: string; slug: string }>;
  currentFilters: { search: string; tag: string; page: number };
  currentTag?: { name: string; slug: string } | null;
}>();

const searchQuery = ref(props.currentFilters.search || '');
const selectedTag = ref(props.currentFilters.tag || '');

const handleSearch = useThrottleFn(() => {
  performSearch();
}, 500);

const performSearch = () => {
  router.get(route('knowledge-base.index'), {
    search: searchQuery.value || undefined,
    tag: selectedTag.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const filterByTag = (slug: string) => {
  selectedTag.value = slug === selectedTag.value ? '' : slug;
  performSearch();
};

const clearSearch = () => {
  searchQuery.value = '';
  performSearch();
};

const clearTag = () => {
  selectedTag.value = '';
  performSearch();
};
</script>

<style scoped>
.flex {
  display: flex;
}
.gap-2 {
  gap: 0.5rem;
}
.gap-4 {
  gap: 1rem;
}

/* Search highlighting styles */
:deep(.search-highlight mark) {
  background-color: #fef08a; /* yellow-200 */
  color: #854d0e; /* yellow-900 */
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-weight: 600;
}
</style>

