<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseEmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EmbedKnowledgeBaseArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kb:embed 
                          {--all : Embed all published articles}
                          {--article= : Embed specific article by ID}
                          {--force : Force re-embedding even if already embedded}
                          {--stats : Show embedding statistics}
                          {--check : Check Pinecone connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Embed knowledge base articles into Pinecone vector database';

    protected KnowledgeBaseEmbeddingService $embeddingService;

    public function __construct(KnowledgeBaseEmbeddingService $embeddingService)
    {
        parent::__construct();
        $this->embeddingService = $embeddingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🤖 Knowledge Base Embedding Manager');
        $this->newLine();

        // Check Pinecone connection first
        if ($this->option('check')) {
            return $this->checkConnection();
        }
        
        // Check if Pinecone is ready
        if (!$this->embeddingService->isReady()) {
            $this->error('❌ Pinecone service is not ready. Please check your configuration.');
            return Command::FAILURE;
        }
        
        // Show statistics
        if ($this->option('stats')) {
            return $this->showStats();
        }

        // Embed specific article
        if ($articleId = $this->option('article')) {
            return $this->embedSpecificArticle($articleId);
        }

        // Embed all articles
        if ($this->option('all')) {
            return $this->embedAllArticles();
        }

        // No specific option provided, show help
        $this->error('❌ Please specify an action:');
        $this->line('  --all       Embed all published articles');
        $this->line('  --article=X Embed specific article by ID');
        $this->line('  --stats     Show embedding statistics');
        $this->line('  --check     Check Pinecone connection');
        $this->newLine();
        $this->line('Example: php artisan kb:embed --all');
        
        return self::FAILURE;
    }

    /**
     * Check Pinecone connection
     */
    protected function checkConnection(): int
    {
        $this->info('🔍 Checking Pinecone connection...');
        
        if ($this->embeddingService->isReady()) {
            $this->info('✅ Pinecone connection successful!');
            $this->showStats();
            return self::SUCCESS;
        } else {
            $this->error('❌ Pinecone connection failed!');
            $this->line('Please check your configuration:');
            $this->line('- PINECONE_API_KEY in .env');
            $this->line('- PINECONE_INDEX_NAME in .env');
            $this->line('- PINECONE_ENVIRONMENT in .env');
            return self::FAILURE;
        }
    }

    /**
     * Show embedding statistics
     */
    protected function showStats(): int
    {
        $stats = $this->embeddingService->getStats();
        
        $this->info('📊 Embedding Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Pinecone Ready', $stats['pinecone_ready'] ? '✅ Yes' : '❌ No'],
                ['Published Articles', $stats['published_articles'] ?? 'N/A'],
                ['Total Vectors', $stats['estimated_vectors'] ?? 'N/A'],
                ['Index Dimension', $stats['pinecone_stats']['dimension'] ?? 'N/A'],
            ]
        );
        
        return self::SUCCESS;
    }

    /**
     * Embed a specific article
     */
    protected function embedSpecificArticle(string $articleId): int
    {
        $article = KnowledgeBaseArticle::find($articleId);
        
        if (!$article) {
            $this->error("❌ Article with ID {$articleId} not found.");
            return self::FAILURE;
        }
        
        if (!$article->is_published) {
            $this->warn("⚠️  Article '{$article->title}' is not published. Only published articles are embedded.");
            return self::FAILURE;
        }
        
        $this->info("🔄 Embedding article: {$article->title}");
        
        $startTime = microtime(true);
        $success = $this->embeddingService->embedArticle($article);
        $duration = round(microtime(true) - $startTime, 2);
        
        if ($success) {
            $this->info("✅ Article embedded successfully in {$duration}s");
            return self::SUCCESS;
        } else {
            $this->error("❌ Failed to embed article. Check logs for details.");
            return self::FAILURE;
        }
    }

    /**
     * Embed all published articles
     */
    protected function embedAllArticles(): int
    {
        $totalArticles = KnowledgeBaseArticle::where('is_published', true)->count();
        
        if ($totalArticles === 0) {
            $this->warn('⚠️  No published articles found to embed.');
            return self::SUCCESS;
        }
        
        $this->info("🚀 Starting bulk embedding of {$totalArticles} articles...");
        $this->newLine();
        
        if (!$this->option('force')) {
            $this->warn('⚠️  This will re-embed all articles, potentially overwriting existing embeddings.');
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }
        
        $bar = $this->output->createProgressBar($totalArticles);
        $bar->setFormat('debug');
        $bar->start();
        
        $startTime = microtime(true);
        $results = $this->embeddingService->embedAllArticles();
        $duration = round(microtime(true) - $startTime, 2);
        
        $bar->finish();
        $this->newLine(2);
        
        // Show results
        $this->info("📈 Bulk Embedding Results (completed in {$duration}s):");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Articles', $results['total']],
                ['Successfully Embedded', $results['success']],
                ['Failed', $results['failed']],
            ]
        );
        
        if (!empty($results['errors'])) {
            $this->error('❌ Errors encountered:');
            foreach (array_slice($results['errors'], 0, 5) as $error) {
                $this->line("  - {$error}");
            }
            if (count($results['errors']) > 5) {
                $this->line("  ... and " . (count($results['errors']) - 5) . " more errors (check logs)");
            }
        }
        
        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
