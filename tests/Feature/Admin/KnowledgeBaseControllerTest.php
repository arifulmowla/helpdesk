<?php

namespace Tests\Feature\Admin;

use App\Models\KnowledgeBaseArticle;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KnowledgeBaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create users with appropriate permissions
        $this->adminUser = User::factory()->create();
        $this->regularUser = User::factory()->create();
        
        // Mock the permission system - adjust according to your actual permission implementation
        $this->adminUser->permissions = ['manage-knowledge-base'];
        $this->regularUser->permissions = [];
    }

    /** @test */
    public function it_can_display_articles_index()
    {
        $article1 = KnowledgeBaseArticle::factory()->published()->create();
        $article2 = KnowledgeBaseArticle::factory()->draft()->create();
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('articles.data', 2)
        );
    }

    /** @test */
    public function it_can_filter_articles_by_status()
    {
        KnowledgeBaseArticle::factory()->published()->create();
        KnowledgeBaseArticle::factory()->draft()->create();

        // Test published filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', ['status' => 'published']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('articles.data', 1)
        );

        // Test draft filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', ['status' => 'draft']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('articles.data', 1)
        );
    }

    /** @test */
    public function it_can_filter_articles_by_tag()
    {
        $tag = Tag::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create();
        $article->tags()->attach($tag);

        KnowledgeBaseArticle::factory()->create(); // Another article without the tag

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', ['tag' => $tag->slug]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('articles.data', 1)
        );
    }

    /** @test */
    public function it_can_search_articles()
    {
        KnowledgeBaseArticle::factory()->create(['title' => 'How to setup Laravel']);
        KnowledgeBaseArticle::factory()->create(['title' => 'Database configuration']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', ['search' => 'Laravel']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('articles.data', 1)
        );
    }

    /** @test */
    public function it_can_display_create_form()
    {
        $tags = Tag::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('tags', 3)
        );
    }

    /** @test */
    public function it_can_store_new_article()
    {
        $tag = Tag::factory()->create();
        
        $articleData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'excerpt' => 'This is a test excerpt',
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'This is test content']
                        ]
                    ]
                ]
            ],
            'is_published' => true,
            'tags' => [
                ['id' => $tag->id, 'name' => $tag->name]
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.knowledge-base.store'), $articleData);

        $response->assertRedirect(route('admin.knowledge-base.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'is_published' => true,
            'created_by' => $this->adminUser->id
        ]);

        $article = KnowledgeBaseArticle::where('slug', 'test-article')->first();
        $this->assertTrue($article->tags->contains($tag));
    }

    /** @test */
    public function it_can_create_new_tags_when_storing_article()
    {
        $articleData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'excerpt' => 'This is a test excerpt',
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'This is test content']
                        ]
                    ]
                ]
            ],
            'is_published' => true,
            'tags' => [
                ['name' => 'New Tag']
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.knowledge-base.store'), $articleData);

        $response->assertRedirect(route('admin.knowledge-base.index'));

        $this->assertDatabaseHas('tags', [
            'name' => 'New Tag',
            'slug' => 'new-tag'
        ]);

        $article = KnowledgeBaseArticle::where('slug', 'test-article')->first();
        $newTag = Tag::where('name', 'New Tag')->first();
        $this->assertTrue($article->tags->contains($newTag));
    }

    /** @test */
    public function it_generates_unique_slug_when_slug_exists()
    {
        KnowledgeBaseArticle::factory()->create(['slug' => 'test-article']);

        $articleData = [
            'title' => 'Test Article',
            'excerpt' => 'This is a test excerpt',
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'This is test content']
                        ]
                    ]
                ]
            ],
            'is_published' => true
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.knowledge-base.store'), $articleData);

        $response->assertRedirect(route('admin.knowledge-base.index'));

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'Test Article',
            'slug' => 'test-article-1'
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.knowledge-base.store'), []);

        $response->assertSessionHasErrors(['title', 'body']);
    }

    /** @test */
    public function it_validates_unique_slug()
    {
        KnowledgeBaseArticle::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.knowledge-base.store'), [
                'title' => 'Test Article',
                'slug' => 'existing-slug',
                'body' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'content']]]]
            ]);

        $response->assertSessionHasErrors(['slug']);
    }

    /** @test */
    public function it_can_display_article()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.show', $article));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('article')
                ->where('article.id', $article->id)
        );
    }

    /** @test */
    public function it_can_display_edit_form()
    {
        $article = KnowledgeBaseArticle::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.edit', $article));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('article')
                ->has('tags', 3)
                ->where('article.id', $article->id)
        );
    }

    /** @test */
    public function it_can_update_article()
    {
        $article = KnowledgeBaseArticle::factory()->create([
            'title' => 'Original Title',
            'is_published' => false
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'slug' => $article->slug,
            'excerpt' => 'Updated excerpt',
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Updated content']
                        ]
                    ]
                ]
            ],
            'is_published' => true
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.knowledge-base.update', $article), $updateData);

        $response->assertRedirect(route('admin.knowledge-base.index'));
        $response->assertSessionHas('success');

        $article->refresh();
        $this->assertEquals('Updated Title', $article->title);
        $this->assertTrue($article->is_published);
        $this->assertNotNull($article->published_at);
        $this->assertEquals($this->adminUser->id, $article->updated_by);
    }

    /** @test */
    public function it_sets_published_at_when_publishing_article()
    {
        $article = KnowledgeBaseArticle::factory()->draft()->create();

        $this->actingAs($this->adminUser)
            ->put(route('admin.knowledge-base.update', $article), [
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'is_published' => true
            ]);

        $article->refresh();
        $this->assertTrue($article->is_published);
        $this->assertNotNull($article->published_at);
    }

    /** @test */
    public function it_can_soft_delete_article()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.knowledge-base.destroy', $article));

        $response->assertRedirect(route('admin.knowledge-base.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted($article);
    }

    /** @test */
    public function it_can_display_soft_deleted_articles()
    {
        $activeArticle = KnowledgeBaseArticle::factory()->create();
        $deletedArticle = KnowledgeBaseArticle::factory()->create();
        $deletedArticle->delete();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', ['status' => 'trashed']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('articles.data', 1)
        );
    }

    /** @test */
    public function it_can_restore_soft_deleted_article()
    {
        $article = KnowledgeBaseArticle::factory()->create();
        $article->delete();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.knowledge-base.restore', $article->id));

        $response->assertRedirect(route('admin.knowledge-base.index'));
        $response->assertSessionHas('success');

        $article->refresh();
        $this->assertNull($article->deleted_at);
    }

    /** @test */
    public function it_can_force_delete_article()
    {
        $article = KnowledgeBaseArticle::factory()->create();
        $article->delete();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.knowledge-base.force-delete', $article->id));

        $response->assertRedirect(route('admin.knowledge-base.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('knowledge_base_articles', [
            'id' => $article->id
        ]);
    }

    /** @test */
    public function it_requires_manage_knowledge_base_permission()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        // Test index
        $this->actingAs($this->regularUser)
            ->get(route('admin.knowledge-base.index'))
            ->assertForbidden();

        // Test create
        $this->actingAs($this->regularUser)
            ->get(route('admin.knowledge-base.create'))
            ->assertForbidden();

        // Test store
        $this->actingAs($this->regularUser)
            ->post(route('admin.knowledge-base.store'), [])
            ->assertForbidden();

        // Test show
        $this->actingAs($this->regularUser)
            ->get(route('admin.knowledge-base.show', $article))
            ->assertForbidden();

        // Test edit
        $this->actingAs($this->regularUser)
            ->get(route('admin.knowledge-base.edit', $article))
            ->assertForbidden();

        // Test update
        $this->actingAs($this->regularUser)
            ->put(route('admin.knowledge-base.update', $article), [])
            ->assertForbidden();

        // Test delete
        $this->actingAs($this->regularUser)
            ->delete(route('admin.knowledge-base.destroy', $article))
            ->assertForbidden();
    }

    /** @test */
    public function it_can_sort_articles()
    {
        $article1 = KnowledgeBaseArticle::factory()->create(['title' => 'A Article']);
        $article2 = KnowledgeBaseArticle::factory()->create(['title' => 'Z Article']);

        // Test ascending sort
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', [
                'sort_by' => 'title',
                'sort_dir' => 'asc'
            ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->where('articles.data.0.title', 'A Article')
                ->where('articles.data.1.title', 'Z Article')
        );

        // Test descending sort
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', [
                'sort_by' => 'title',
                'sort_dir' => 'desc'
            ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->where('articles.data.0.title', 'Z Article')
                ->where('articles.data.1.title', 'A Article')
        );
    }

    /** @test */
    public function it_can_paginate_articles()
    {
        KnowledgeBaseArticle::factory()->count(20)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.knowledge-base.index', ['per_page' => 10]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('articles.data', 10)
                ->where('articles.total', 20)
        );
    }
}
