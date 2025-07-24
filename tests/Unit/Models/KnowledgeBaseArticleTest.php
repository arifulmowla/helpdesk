<?php

namespace Tests\Unit\Models;

use App\Models\KnowledgeBaseArticle;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseArticleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_created_by_user()
    {
        $user = User::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $article->createdBy);
        $this->assertEquals($user->id, $article->createdBy->id);
    }

    /** @test */
    public function it_belongs_to_updated_by_user()
    {
        $user = User::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create(['updated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $article->updatedBy);
        $this->assertEquals($user->id, $article->updatedBy->id);
    }

    /** @test */
    public function it_belongs_to_many_tags()
    {
        $article = KnowledgeBaseArticle::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        
        $article->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $article->tags);
        $this->assertTrue($article->tags->contains($tag1));
        $this->assertTrue($article->tags->contains($tag2));
    }

    /** @test */
    public function it_casts_body_to_array()
    {
        $bodyContent = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'Test content']]
                ]
            ]
        ];

        $article = KnowledgeBaseArticle::factory()->create(['body' => $bodyContent]);

        $this->assertIsArray($article->body);
        $this->assertEquals($bodyContent, $article->body);
    }

    /** @test */
    public function it_casts_is_published_to_boolean()
    {
        $article = KnowledgeBaseArticle::factory()->create(['is_published' => 1]);
        $this->assertIsBool($article->is_published);
        $this->assertTrue($article->is_published);

        $article = KnowledgeBaseArticle::factory()->create(['is_published' => 0]);
        $this->assertIsBool($article->is_published);
        $this->assertFalse($article->is_published);
    }

    /** @test */
    public function it_casts_published_at_to_datetime()
    {
        $article = KnowledgeBaseArticle::factory()->create(['published_at' => '2023-01-01 12:00:00']);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->published_at);
        $this->assertEquals('2023-01-01 12:00:00', $article->published_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $article = KnowledgeBaseArticle::factory()->create();
        
        $article->delete();
        
        $this->assertSoftDeleted($article);
        $this->assertNotNull($article->deleted_at);
    }

    /** @test */
    public function to_searchable_array_returns_empty_for_unpublished_articles()
    {
        $article = KnowledgeBaseArticle::factory()->draft()->create();
        
        $searchableArray = $article->toSearchableArray();
        
        $this->assertEmpty($searchableArray);
    }

    /** @test */
    public function to_searchable_array_returns_correct_data_for_published_articles()
    {
        $article = KnowledgeBaseArticle::factory()->published()->create([
            'title' => 'Test Article',
            'excerpt' => 'Test excerpt',
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
            ]
        ]);

        $searchableArray = $article->toSearchableArray();

        $this->assertArrayHasKey('id', $searchableArray);
        $this->assertArrayHasKey('title', $searchableArray);
        $this->assertArrayHasKey('excerpt', $searchableArray);
        $this->assertArrayHasKey('body', $searchableArray);
        $this->assertArrayHasKey('is_published', $searchableArray);
        
        $this->assertEquals($article->id, $searchableArray['id']);
        $this->assertEquals('Test Article', $searchableArray['title']);
        $this->assertEquals('Test excerpt', $searchableArray['excerpt']);
        $this->assertEquals('This is test content', $searchableArray['body']);
        $this->assertTrue($searchableArray['is_published']);
    }

    /** @test */
    public function extract_text_from_tiptap_content_works_with_simple_content()
    {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'First paragraph']
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Second paragraph']
                    ]
                ]
            ]
        ];

        $article = KnowledgeBaseArticle::factory()->published()->create(['body' => $content]);
        $searchableArray = $article->toSearchableArray();

        $this->assertEquals('First paragraph Second paragraph', $searchableArray['body']);
    }

    /** @test */
    public function extract_text_from_tiptap_content_works_with_complex_content()
    {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [
                        ['type' => 'text', 'text' => 'Main Title']
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Some content with '],
                        ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'bold text'],
                        ['type' => 'text', 'text' => ' and more.']
                    ]
                ],
                [
                    'type' => 'bulletList',
                    'content' => [
                        [
                            'type' => 'listItem',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        ['type' => 'text', 'text' => 'List item 1']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $article = KnowledgeBaseArticle::factory()->published()->create(['body' => $content]);
        $searchableArray = $article->toSearchableArray();

        $expectedText = 'Main Title Some content with bold text and more. List item 1';
        $this->assertEquals($expectedText, $searchableArray['body']);
    }

    /** @test */
    public function extract_text_from_tiptap_content_handles_empty_content()
    {
        $content = [
            'type' => 'doc',
            'content' => []
        ];

        $article = KnowledgeBaseArticle::factory()->published()->create(['body' => $content]);
        $searchableArray = $article->toSearchableArray();

        $this->assertEquals('', $searchableArray['body']);
    }

    /** @test */
    public function to_searchable_array_handles_string_body()
    {
        $article = KnowledgeBaseArticle::factory()->published()->create();
        // Manually set body as string to simulate legacy data
        $article->body = 'Plain text body content';
        $article->save();

        // Reload to test the actual behavior
        $article->refresh();
        $searchableArray = $article->toSearchableArray();

        $this->assertEquals('Plain text body content', $searchableArray['body']);
    }
}
