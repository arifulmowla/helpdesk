<template>
  <div class="flex gap-10">
    <!-- Main article content -->
    <div class="flex-1">
      <TiptapEditor :modelValue="articleContent" readonly />

      <!-- Tag links -->
      <div class="mt-4">
        <span v-for="tag in article.tag_names" :key="tag" class="mr-2">
          <TextLink :href="`/articles?tag=${tag}`">{{ tag }}</TextLink>
        </span>
      </div>
    </div>

    <!-- Related articles sidebar -->
    <aside class="w-80 space-y-6">
      <!-- AI Assistant -->
      <div>
        <h3 class="text-lg font-semibold mb-4">AI Assistant</h3>
        <AIAnswerGenerator />
      </div>
      
      <!-- Related Articles -->
      <div>
        <h3 class="text-lg font-semibold mb-2">Related Articles</h3>
        <ul class="space-y-1">
          <li v-for="related in relatedArticles" :key="related.slug">
            <TextLink :href="`/articles/${related.slug}`">{{ related.title }}</TextLink>
          </li>
        </ul>
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TiptapEditor from '@/components/TiptapEditor.vue';
import TextLink from '@/components/TextLink.vue';
import AIAnswerGenerator from '@/components/AIAnswerGenerator.vue';

interface Article {
  title: string;
  slug: string;
  content: string;
  tag_names: string[];
}

interface RelatedArticle {
  title: string;
  slug: string;
}

const page = usePage<{ props: { article: Article; relatedArticles: RelatedArticle[] } }>();
const article = computed(() => page.props.article);
const relatedArticles = computed(() => page.props.relatedArticles);
const articleContent = computed(() => article.value.content);
</script>

<style scoped>
.flex {
  display: flex;
}
.gap-10 {
  gap: 2.5rem;
}
.mr-2 {
  margin-right: 0.5rem;
}
.w-60 {
  width: 15rem;
}
</style>

