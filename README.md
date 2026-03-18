# VITA-COMPARE - AI-Powered Supplement Comparison Chatbot

An intelligent AI chatbot system that helps users find the best supplements for their specific health conditions. Built with Laravel 13 and powered by Claude/GPT-4 via OpenRouter.

## Features

- **23,800+ Supplements**: Comprehensive database of Greek and international products
- **20 Supplement Categories**: Omega-3, Magnesium, Vitamin D, Probiotics, Collagen, and more
- **18 Medical Conditions**: Diabetes, Hypertension, Arthritis, Sleep Issues, PCOS, etc.
- **Evidence-Based Scoring**: Clinical bioavailability data, quality certifications, efficacy ratings
- **Smart Chatbot**: Greek-language conversational AI that extracts user needs
- **Multi-Turn Memory**: Remembers user preferences across sessions
- **Streaming Responses**: Real-time SSE streaming for instant feedback
- **Ranked Recommendations**: Top 5 supplements with detailed explanations

## Tech Stack

- **Backend**: PHP 8.3 + Laravel 13
- **Database**: MySQL 8.0+
- **AI**: OpenRouter (Claude Sonnet 4 / GPT-4 / Gemini)
- **Frontend**: Alpine.js + Tailwind CSS 4 + Blade
- **Build**: Vite 7

## Quick Start

### 1. Clone & Install

```bash
git clone https://github.com/your-repo/vita-compare.git
cd vita-compare
composer install
npm install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your settings:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=b_compare
DB_USERNAME=your_username
DB_PASSWORD=your_password

# AI Configuration (Required)
AI_PROVIDER=openrouter
OPENROUTER_API_KEY=your_openrouter_key

# AI Models
AI_CHAT_MODEL=anthropic/claude-sonnet-4
AI_RECOMMENDATION_MODEL=anthropic/claude-sonnet-4
```

### 3. Setup Database

```bash
php artisan migrate
php artisan db:seed
```

### 4. Build Assets & Run

```bash
npm run build
php artisan serve
```

Visit: **http://localhost:8000**

## Laravel Cloud Deployment

### Environment Variables

Set these in Laravel Cloud dashboard:

| Variable | Description |
|----------|-------------|
| `AI_PROVIDER` | `openrouter` (recommended) |
| `OPENROUTER_API_KEY` | Your API key from openrouter.ai |
| `AI_CHAT_MODEL` | `anthropic/claude-sonnet-4` |
| `AI_RECOMMENDATION_MODEL` | `anthropic/claude-sonnet-4` |

### Deploy Commands

Laravel Cloud will automatically run:
- `composer install`
- `npm install && npm run build`
- `php artisan migrate --force`

### Post-Deploy (First Time)

After first deployment, run seeder via Laravel Cloud console:
```bash
php artisan db:seed
```

## API Endpoints

### Chat
```
POST /api/chat/message     - Send chat message
POST /api/chat/stream      - SSE streaming response
POST /api/chat/select-category - Select category
```

### Supplements
```
GET  /api/supplements          - List all
GET  /api/supplements/search   - Search
GET  /api/supplements/{id}     - Get details
POST /api/supplements/compare  - Compare multiple
```

### Categories & Conditions
```
GET /api/categories            - List categories
GET /api/conditions            - List conditions
```

### Analytics
```
GET /api/analytics/conversations
GET /api/analytics/recommendations/top
GET /api/analytics/trends/daily
```

### Export
```
GET /api/export/supplements
GET /api/export/full
```

## Database Schema

| Table | Description |
|-------|-------------|
| `supplements` | 23,800+ products with scores |
| `supplement_categories` | 20 categories |
| `medical_conditions` | 18 conditions |
| `supplement_condition_mappings` | Efficacy ratings |
| `chat_conversations` | Session state |
| `user_profiles` | Multi-turn memory |
| `supplement_recommendations` | Saved results |
| `analytics_events` | Usage tracking |

## Scoring System

Each supplement is scored on:

1. **Efficacy Score (35%)** - Based on ingredients, dosage, user ratings
2. **Quality Score (30%)** - Certifications (GMP, third-party tested), brand reputation
3. **Bioavailability Score (25%)** - Absorption rate of ingredient forms
4. **Formulation Score (10%)** - Synergistic ingredients, purity

## AI Models

Supported via OpenRouter:

| Model | Best For |
|-------|----------|
| `anthropic/claude-sonnet-4` | Best quality (recommended) |
| `openai/gpt-4o` | High quality alternative |
| `openai/gpt-4o-mini` | Fast & cheap |
| `google/gemini-2.0-flash-exp:free` | Free tier |

## Project Structure

```
vita-compare/
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatController.php
│   │   ├── AdminController.php
│   │   └── Api/
│   ├── Models/
│   │   ├── Supplement.php
│   │   ├── UserProfile.php
│   │   └── ...
│   └── Services/
│       ├── AIService.php
│       ├── ChatbotService.php
│       └── SupplementKnowledgeBase.php
├── config/
│   └── ai.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/views/
│   └── chat.blade.php
└── routes/
    ├── api.php
    └── web.php
```

## Admin Panel

Access at `/admin` to:
- View/edit supplements
- Import from Excel/JSON
- Run scraper tests
- Manage products

## License

MIT License

---

**Built with Laravel + Claude AI**
