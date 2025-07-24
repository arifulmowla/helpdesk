# Helpdesk System

A modern, Laravel-based helpdesk system with Vue.js frontend, featuring comprehensive ticket management, knowledge base, and customer communication tools.

## 🚀 Features

### Core Helpdesk Features
- **Ticket Management**: Create, assign, track, and resolve customer support tickets
- **Conversation Threading**: Seamless email integration with conversation history
- **Customer Management**: Comprehensive contact and organization management
- **Real-time Updates**: Live updates using Laravel WebSockets or similar
- **Multi-channel Support**: Email, web forms, and API integrations

### Knowledge Base Module
- **Article Management**: Rich WYSIWYG editor (TipTap) for creating help articles
- **Advanced Search**: Full-text search with SQLite FTS5 and optional Laravel Scout
- **Tag System**: Categorize and organize articles with flexible tagging
- **Publishing Workflow**: Draft/publish system with scheduled publishing
- **SEO Optimized**: Clean URLs, meta descriptions, and structured data
- **Analytics**: View tracking and popular content insights

### Technical Features
- **Modern Stack**: Laravel 11 + Vue 3 + Inertia.js + TypeScript
- **Database**: MySQL/SQLite with optimized full-text search
- **Email Integration**: Postmark/SMTP for reliable email delivery
- **API Ready**: RESTful API for integrations
- **Responsive Design**: Mobile-first UI with Tailwind CSS
- **State Management**: Persistent UI state with Inertia preservation

## 📋 Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Knowledge Base](#knowledge-base)
- [Email Setup](#email-setup)
- [Development](#development)
- [API Documentation](#api-documentation)
- [Deployment](#deployment)
- [Contributing](#contributing)

## 🛠 Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- Node.js 18+ & npm
- MySQL 8.0+ or SQLite 3.35+
- Redis (optional, for caching and queues)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd helpdesk
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   # Configure database in .env, then:
   php artisan migrate
   php artisan db:seed
   ```

6. **Build assets**
   ```bash
   npm run build
   # or for development:
   npm run dev
   ```

7. **Start the server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to access the application.

## ⚙️ Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# Application
APP_NAME="Helpdesk System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk
DB_USERNAME=root
DB_PASSWORD=

# Email (Postmark recommended)
MAIL_MAILER=postmark
POSTMARK_TOKEN=your-postmark-token

# Search (optional - for advanced search)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
```

### Database Configuration

The system supports both MySQL and SQLite:

**MySQL (Recommended for production):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk
```

**SQLite (Great for development):**
```env
DB_CONNECTION=sqlite
# DB_DATABASE will be created automatically
```

## 📚 Knowledge Base

The Knowledge Base module provides a comprehensive article management system.

### Quick Start

1. **Seed demo data**
   ```bash
   php artisan db:seed --class=TagSeeder
   php artisan db:seed --class=KnowledgeBaseSeeder
   ```

2. **Access the Knowledge Base**
   - Public KB: `/knowledge-base`
   - Admin interface: `/admin/knowledge-base`

### Creating Articles

**Via Admin Interface:**
1. Navigate to `/admin/knowledge-base/create`
2. Fill in title (slug auto-generates)
3. Add excerpt for article listings
4. Write content using the rich text editor
5. Select or create tags
6. Choose publish status

**Via Code:**
```php
use App\Models\KnowledgeBaseArticle;
use App\Models\Tag;

$article = KnowledgeBaseArticle::create([
    'title' => 'Getting Started Guide',
    'slug' => 'getting-started-guide',
    'excerpt' => 'Learn the basics of our system',
    'body' => $tiptapJsonContent,
    'is_published' => true,
    'published_at' => now(),
    'created_by' => auth()->id(),
    'updated_by' => auth()->id(),
]);

// Attach tags
$tags = Tag::whereIn('name', ['Guide', 'Basics'])->get();
$article->tags()->attach($tags->pluck('id'));
```

### Search Configuration

The system includes two search options:

#### Option 1: Built-in Full-text Search (Default)
- Uses SQLite FTS5 virtual tables
- Zero configuration required
- Perfect for small to medium datasets
- Automatic content indexing with triggers

#### Option 2: Laravel Scout (Advanced)
For enhanced search capabilities:

```bash
# Install Scout
composer require laravel/scout

# Publish config
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"

# Configure in .env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700

# Index existing articles
php artisan scout:import "App\Models\KnowledgeBaseArticle"
```

**Supported Scout Drivers:**
- **Meilisearch**: Self-hosted, typo-tolerant, instant search
- **Algolia**: Cloud-based, powerful analytics
- **Elasticsearch**: Enterprise-grade, highly customizable

For detailed KB documentation, see [`docs/knowledge-base.md`](docs/knowledge-base.md).

## 📧 Email Setup

### Postmark Integration (Recommended)

1. **Create Postmark account** at [postmarkapp.com](https://postmarkapp.com)
2. **Configure environment**:
   ```env
   MAIL_MAILER=postmark
   POSTMARK_TOKEN=your-server-token
   ```
3. **Set up webhook** for incoming emails (see [`docs/postmark-webhook-setup.md`](docs/postmark-webhook-setup.md))

### SMTP Configuration

For other email providers:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

## 🧑‍💻 Development

### Frontend Development

```bash
# Start development server with hot reload
npm run dev

# Build for production
npm run build

# Lint and format code
npm run lint
npm run format
```

### Backend Development

```bash
# Run tests
php artisan test

# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models

# Clear all caches
php artisan optimize:clear
```

### Development Workflow

1. **Create feature branch**
   ```bash
   git checkout -b feature/new-feature
   ```

2. **Make changes and test**
   ```bash
   php artisan test
   npm run test
   ```

3. **Commit with conventional commits**
   ```bash
   git commit -m "feat: add new feature"
   ```

### Project Structure

```
helpdesk/
├── app/
│   ├── Data/                  # DTOs for type-safe data transfer
│   ├── Http/Controllers/      # Laravel controllers
│   ├── Models/               # Eloquent models
│   └── ...
├── database/
│   ├── factories/            # Model factories for testing
│   ├── migrations/           # Database migrations
│   └── seeders/             # Database seeders
├── docs/                    # Documentation
├── resources/
│   ├── js/                  # Vue.js frontend
│   │   ├── components/      # Reusable Vue components
│   │   ├── pages/          # Inertia.js pages
│   │   └── types/          # TypeScript definitions
│   └── views/              # Laravel Blade templates
└── tests/                  # Test files
```

## 🔌 API Documentation

### Authentication

The API uses Laravel Sanctum for authentication:

```bash
# Get API token
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Use token in requests
Authorization: Bearer {token}
```

### Core Endpoints

**Tickets:**
- `GET /api/tickets` - List tickets
- `POST /api/tickets` - Create ticket
- `GET /api/tickets/{id}` - Get ticket details
- `PUT /api/tickets/{id}` - Update ticket
- `DELETE /api/tickets/{id}` - Delete ticket

**Knowledge Base:**
- `GET /api/knowledge-base` - List articles
- `GET /api/knowledge-base/{slug}` - Get article by slug
- `GET /api/knowledge-base/search?q={query}` - Search articles
- `GET /api/knowledge-base/tags/{tag}` - Articles by tag

For complete API documentation, visit `/api/documentation` when running the application.

## 🚀 Deployment

### Production Requirements

- PHP 8.1+ with required extensions
- MySQL 8.0+ or PostgreSQL 13+
- Redis for caching and queues
- Web server (Nginx/Apache)
- SSL certificate
- Cron jobs for scheduled tasks

### Deployment Steps

1. **Server setup**
   ```bash
   # Install dependencies
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```

2. **Environment configuration**
   ```bash
   # Set production environment
   APP_ENV=production
   APP_DEBUG=false
   
   # Configure database and services
   # Set secure APP_KEY
   php artisan key:generate
   ```

3. **Database setup**
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=ProductionSeeder
   ```

4. **Optimize for production**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```

5. **Set up queues and scheduler**
   ```bash
   # Add to crontab
   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
   
   # Start queue workers (use Supervisor in production)
   php artisan queue:work
   ```

### Docker Deployment

```bash
# Build and run with Docker Compose
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Frontend tests
npm run test
npm run test:watch
```

## 📖 Documentation

- **[Knowledge Base Module](docs/knowledge-base.md)** - Comprehensive KB documentation
- **[Knowledge Base DTOs](docs/knowledge-base-dtos.md)** - Data transfer objects guide
- **[Postmark Setup](docs/postmark-webhook-setup.md)** - Email integration guide
- **[Backend Integration](BACKEND_INTEGRATION_EXAMPLE.md)** - Laravel integration examples
- **[Scout Installation](SCOUT_INSTALLATION.md)** - Advanced search setup

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

### Coding Standards

- **PHP**: Follow PSR-12 coding standards
- **JavaScript/TypeScript**: Use ESLint and Prettier configurations
- **Vue.js**: Follow Vue 3 Composition API patterns
- **Commits**: Use conventional commit messages

## 📝 License

This project is licensed under the [MIT License](LICENSE).

## 🆘 Support

- **Documentation**: Check the `docs/` directory
- **Issues**: Create an issue on GitHub
- **Discussions**: Use GitHub Discussions for questions

## 🏗 System Requirements

### Minimum Requirements
- PHP 8.1+
- MySQL 8.0+ or SQLite 3.35+
- Node.js 18+
- 512MB RAM
- 1GB disk space

### Recommended for Production
- PHP 8.2+
- MySQL 8.0+ with InnoDB
- Redis for caching
- 2GB+ RAM
- 10GB+ disk space
- SSL certificate
- CDN for static assets

---

**Built with ❤️ using Laravel, Vue.js, and modern web technologies.**
