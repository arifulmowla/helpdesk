<?php

namespace Tests\Unit\Models;

use App\Models\KnowledgeBaseArticle;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_many_knowledge_base_articles()
    {
        $tag = Tag::factory()->create();
        $article1 = KnowledgeBaseArticle::factory()->create();
        $article2 = KnowledgeBaseArticle::factory()->create();
        
        $tag->knowledgeBaseArticles()->attach([$article1->id, $article2->id]);

        $this->assertCount(2, $tag->knowledgeBaseArticles);
        $this->assertTrue($tag->knowledgeBaseArticles->contains($article1));
        $this->assertTrue($tag->knowledgeBaseArticles->contains($article2));
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $tag = new Tag();
        
        $this->assertEquals(['name', 'slug'], $tag->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $tag = Tag::factory()->create([
            'name' => 'Test Tag',
            'slug' => 'test-tag'
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'Test Tag',
            'slug' => 'test-tag'
        ]);
    }

    /** @test */
    public function it_can_create_support_tags()
    {
        $tag = Tag::factory()->supportTag()->create();

        $this->assertNotEmpty($tag->name);
        $this->assertNotEmpty($tag->slug);
        $this->assertStringNotContainsString(' ', $tag->slug);
    }

    /** @test */
    public function it_can_create_technical_tags()
    {
        $tag = Tag::factory()->technicalTag()->create();

        $this->assertNotEmpty($tag->name);
        $this->assertNotEmpty($tag->slug);
        $this->assertStringNotContainsString(' ', $tag->slug);
    }

    /** @test */
    public function it_can_create_tag_with_specific_name()
    {
        $tag = Tag::factory()->withName('Custom Tag')->create();

        $this->assertEquals('Custom Tag', $tag->name);
        $this->assertEquals('custom-tag', $tag->slug);
    }

    /** @test */
    public function it_can_create_tag_with_specific_slug()
    {
        $tag = Tag::factory()->withSlug('custom-slug')->create();

        $this->assertEquals('custom-slug', $tag->slug);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $tag = new Tag();
        
        $this->assertEquals('tags', $tag->getTable());
    }

    /** @test */
    public function it_uses_default_primary_key()
    {
        $tag = new Tag();
        
        $this->assertEquals('id', $tag->getKeyName());
    }

    /** @test */
    public function knowledge_base_articles_relationship_uses_correct_pivot_table()
    {
        $tag = new Tag();
        $relation = $tag->knowledgeBaseArticles();
        
        $this->assertEquals('article_tag', $relation->getTable());
        $this->assertEquals('tag_id', $relation->getForeignPivotKeyName());
        $this->assertEquals('article_id', $relation->getRelatedPivotKeyName());
    }
}
