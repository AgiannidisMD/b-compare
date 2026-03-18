# B-COMPARE API Documentation

**Version:** 1.0.0
**Base URL:** `https://your-domain.com/api`
**Last Updated:** March 2026

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Supplement Data APIs](#supplement-data-apis)
5. [Category & Condition APIs](#category--condition-apis)
6. [Recommendation Engine APIs](#recommendation-engine-apis)
7. [Statistics APIs](#statistics-apis)
8. [Analytics APIs](#analytics-apis)
9. [Export/Sync APIs](#exportsync-apis)
10. [Error Handling](#error-handling)

---

## Overview

B-COMPARE provides a comprehensive API for supplement data, recommendations, and analytics. This API is designed for integration with NutriCRM and other health management systems.

### Key Features
- **23,853 supplements** with clinical scoring
- **20 supplement categories**
- **18 medical conditions**
- **Clinical scoring methodology** (price-free)
- **Real-time analytics** on usage and demand

---

## Authentication

Currently, the API is open for development. For production, implement one of:
- API Key authentication (header: `X-API-Key`)
- OAuth 2.0
- JWT tokens

---

## Response Format

All responses follow this structure:

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "total": 100,
    "page": 1
  }
}
```

Error responses:
```json
{
  "success": false,
  "error": "Error message here"
}
```

---

## Supplement Data APIs

### List Supplements

```
GET /api/supplements
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `category_id` | int | Filter by category ID |
| `category_slug` | string | Filter by category slug |
| `brand` | string | Filter by brand name (partial match) |
| `min_score` | float | Minimum overall score (0-10) |
| `max_score` | float | Maximum overall score (0-10) |
| `dosage_form` | string | Filter by form (capsule, softgel, tablet, etc.) |
| `min_rating` | float | Minimum user rating (0-5) |
| `sort_by` | string | Sort field (default: overall_recommendation_score) |
| `sort_dir` | string | Sort direction: asc/desc (default: desc) |
| `per_page` | int | Results per page (max: 100, default: 20) |
| `page` | int | Page number |

**Example Request:**
```bash
curl "https://api.example.com/api/supplements?category_slug=magnesium&min_score=7&per_page=10"
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1234,
      "brand": "Thorne",
      "title": "Magnesium Bisglycinate",
      "current_price": 32.50,
      "rating": 4.8,
      "review_count": 2450,
      "overall_recommendation_score": 8.23,
      "efficacy_score": 9.5,
      "quality_score": 8.0,
      "bioavailability_score": 9.2,
      "formulation_score": 7.5,
      "category": {
        "id": 2,
        "name": "Magnesium",
        "slug": "magnesium"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 48
  }
}
```

---

### Get Single Supplement

```
GET /api/supplements/{id}
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "id": 1234,
    "brand": "Thorne",
    "title": "Magnesium Bisglycinate",
    "product_url": "https://...",
    "image_url": "https://...",
    "current_price": 32.50,
    "currency": "EUR",
    "rating": 4.8,
    "review_count": 2450,
    "product_description": "...",
    "serving_size_value": 2,
    "serving_size_unit": "capsules",
    "servings_per_container": 60,
    "dosage_form": "capsule",
    "certification_flags": ["GMP", "NSF Certified"],
    "active_ingredients": [
      {"name": "Magnesium Bisglycinate", "amount": 200, "unit": "mg"}
    ],
    "allergen_free_from_flags": ["gluten", "dairy"],
    "overall_recommendation_score": 8.23,
    "efficacy_score": 9.5,
    "quality_score": 8.0,
    "bioavailability_score": 9.2,
    "formulation_score": 7.5,
    "category": {...},
    "conditions": [...]
  }
}
```

---

### Search Supplements

```
GET /api/supplements/search
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `q` | string | Search query (min 2 chars) |
| `limit` | int | Max results (default: 20) |

**Example:**
```bash
curl "https://api.example.com/api/supplements/search?q=omega+nordic"
```

---

### Get Top Rated Supplements

```
GET /api/supplements/top-rated
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `category_id` | int | Filter by category |
| `limit` | int | Max results (max: 50, default: 10) |

---

### Get Supplements by IDs

```
GET /api/supplements/by-ids
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `ids` | string | Comma-separated IDs |

**Example:**
```bash
curl "https://api.example.com/api/supplements/by-ids?ids=1,2,3,4,5"
```

---

### Compare Supplements

```
POST /api/supplements/compare
```

**Request Body:**
```json
{
  "ids": [1234, 1235, 1236]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "supplements": [...],
    "comparison": {
      "highest_score": 8.5,
      "lowest_score": 6.2,
      "avg_score": 7.35,
      "highest_efficacy": 9.0,
      "highest_quality": 8.5,
      "highest_bioavailability": 9.2,
      "price_range": {
        "min": 19.99,
        "max": 45.00
      }
    },
    "winner": {...}
  }
}
```

---

## Category & Condition APIs

### List All Categories

```
GET /api/categories
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Fiber Supplements",
      "slug": "fiber-supplements",
      "icon": "🥬",
      "product_count": 2301
    },
    {
      "id": 2,
      "name": "Magnesium",
      "slug": "magnesium",
      "icon": "⚡",
      "product_count": 2107
    }
  ],
  "meta": {
    "total_categories": 20,
    "total_products": 23853
  }
}
```

---

### Get Category Details

```
GET /api/categories/{slug}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | int | Top supplements to return (default: 10) |

**Response:**
```json
{
  "success": true,
  "data": {
    "category": {
      "id": 2,
      "name": "Magnesium",
      "slug": "magnesium",
      "product_count": 2107
    },
    "top_supplements": [...],
    "stats": {
      "total_products": 2107,
      "avg_score": 6.45,
      "avg_price": 28.50,
      "score_distribution": {
        "excellent": 45,
        "good": 320,
        "average": 1200,
        "below_average": 542
      }
    }
  }
}
```

---

### Get Category Supplements

```
GET /api/categories/{slug}/supplements
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `sort_by` | string | Sort field |
| `sort_dir` | string | asc/desc |
| `per_page` | int | Results per page (max: 100) |
| `page` | int | Page number |

---

### List All Medical Conditions

```
GET /api/conditions
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Diabetes",
      "slug": "diabetes",
      "description": "Type 1, Type 2, and Prediabetes",
      "recommended_supplements_count": 450
    }
  ],
  "meta": {
    "total_conditions": 18
  }
}
```

---

### Get Condition Details

```
GET /api/conditions/{slug}
```

**Response includes:**
- Condition information
- Top recommended supplements
- Best categories for this condition

---

## Recommendation Engine APIs

### Get Personalized Recommendations

```
POST /api/recommend
```

**Request Body:**
```json
{
  "category": "magnesium",
  "conditions": ["diabetes", "fatigue"],
  "allergens": ["soy", "gluten"],
  "preferences": ["vegan", "organic"],
  "limit": 5,
  "session_id": "optional_session_id"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "recommendations": [
      {
        "id": 1234,
        "brand": "Thorne",
        "title": "Magnesium Bisglycinate",
        "overall_recommendation_score": 8.5,
        "efficacy_score": 9.0,
        "quality_score": 8.5,
        "bioavailability_score": 9.2,
        "formulation_score": 7.5,
        "category": {...}
      }
    ],
    "filters_applied": {
      "category": "magnesium",
      "conditions": ["diabetes", "fatigue"],
      "allergens": ["soy", "gluten"],
      "preferences": ["vegan", "organic"]
    }
  },
  "meta": {
    "count": 5
  }
}
```

---

### Get Recommendations for Condition

```
GET /api/recommend/condition/{slug}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | int | Max results (default: 10) |

---

### Get Top Supplements in Category

```
GET /api/recommend/category/{slug}
```

---

### Explain Score Breakdown

```
GET /api/scoring/explain/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "supplement": {
      "id": 1234,
      "brand": "Thorne",
      "title": "Magnesium Bisglycinate"
    },
    "scores": {
      "overall": 8.23,
      "efficacy": {
        "score": 9.5,
        "weight": "35%",
        "factors": {
          "rating": 4.8,
          "review_count": 2450,
          "has_active_ingredients": true
        }
      },
      "quality": {
        "score": 8.0,
        "weight": "30%",
        "factors": {
          "certifications": ["GMP", "NSF Certified"],
          "has_warnings": false
        }
      },
      "bioavailability": {
        "score": 9.2,
        "weight": "25%",
        "factors": {
          "dosage_form": "capsule",
          "active_ingredients": [...]
        }
      },
      "formulation": {
        "score": 7.5,
        "weight": "10%",
        "factors": {
          "serving_size": "2 capsules",
          "servings_per_container": 60,
          "days_of_supply": 30
        }
      }
    },
    "methodology": "Clinical Score = (Efficacy × 35%) + (Quality × 30%) + (Bioavailability × 25%) + (Formulation × 10%)"
  }
}
```

---

## Statistics APIs

### Overview Statistics

```
GET /api/stats/overview
```

**Response:**
```json
{
  "success": true,
  "data": {
    "supplements": {
      "total": 23853,
      "with_scores": 23853,
      "avg_score": 6.14,
      "avg_rating": 4.35,
      "avg_price": 28.50
    },
    "categories": {
      "total": 20,
      "with_products": 19
    },
    "conditions": {
      "total": 18
    },
    "brands": {
      "total": 980
    },
    "score_distribution": {
      "excellent": 245,
      "good": 4520,
      "average": 12000,
      "below_average": 7088,
      "unscored": 0
    },
    "dosage_forms": {
      "capsule": 8500,
      "softgel": 4200,
      "tablet": 3800,
      "powder": 2500,
      "liquid": 1800,
      "gummy": 3053
    }
  }
}
```

---

### Category Statistics

```
GET /api/stats/categories
```

**Response includes per-category:**
- Product count
- Average scores (overall, efficacy, quality, bioavailability)
- Average price and rating
- Top brand

---

### Brand Statistics

```
GET /api/stats/brands
```

**Returns top 50 brands with:**
- Product count
- Average score and rating
- Average price
- Total reviews

---

### Score Distribution

```
GET /api/stats/scores
```

**Response includes:**
- Distribution counts
- Min/max/mean/median statistics
- Breakdown by score component

---

### Certification Statistics

```
GET /api/stats/certifications
```

**Returns certification counts:**
```json
{
  "success": true,
  "data": {
    "certifications": {
      "gmp": 15420,
      "third-party tested": 8230,
      "non-gmo": 6500,
      "organic": 3200,
      "nsf certified": 2100,
      "usp verified": 850
    },
    "total_supplements_with_certs": 18500,
    "total_supplements": 23853
  }
}
```

---

## Analytics APIs

### Conversation Analytics

```
GET /api/analytics/conversations
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `days` | int | Number of days to analyze (default: 30) |

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 1250,
    "recent": 450,
    "period_days": 30,
    "completion_rate": 68.5,
    "daily": {
      "2026-03-15": 45,
      "2026-03-16": 52,
      "2026-03-17": 38
    }
  }
}
```

---

### Conversation Summary

```
GET /api/analytics/conversations/summary
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_conversations": 1250,
    "completed_conversations": 856,
    "completion_rate": 68.5,
    "total_messages": 8750,
    "avg_messages_per_conversation": 7.0,
    "by_category": [
      {"category_id": 2, "category_name": "Magnesium", "count": 320},
      {"category_id": 5, "category_name": "Omega-3 & Fish Oil", "count": 280}
    ]
  }
}
```

---

### Message Statistics

```
GET /api/analytics/messages
```

---

### Category Demand

```
GET /api/analytics/categories/demand
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "category_id": 2,
      "category_name": "Magnesium",
      "category_slug": "magnesium",
      "conversations_count": 320
    },
    {
      "category_id": 5,
      "category_name": "Omega-3 & Fish Oil",
      "category_slug": "omega-3-fish-oil",
      "conversations_count": 280
    }
  ],
  "meta": {
    "period_days": 30,
    "total_category_selections": 1100
  }
}
```

---

### Condition Demand

```
GET /api/analytics/conditions/demand
```

**Response:**
```json
{
  "success": true,
  "data": {
    "diabetes": 145,
    "fatigue": 120,
    "joint pain": 95,
    "sleep issues": 88,
    "stress": 72
  },
  "meta": {
    "period_days": 30,
    "total_condition_mentions": 520
  }
}
```

---

### User Preferences

```
GET /api/analytics/preferences
```

---

### Recommendation Statistics

```
GET /api/analytics/recommendations/generated
```

---

### Top Recommended Supplements

```
GET /api/analytics/recommendations/top
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1234,
      "brand": "Thorne",
      "title": "Magnesium Bisglycinate",
      "category": "Magnesium",
      "score": 8.5,
      "times_recommended": 87
    }
  ],
  "meta": {
    "period_days": 30
  }
}
```

---

### Daily Trends

```
GET /api/analytics/trends/daily
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `days` | int | Number of days (default: 30) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "date": "2026-03-15",
      "conversations": 45,
      "recommendations": 32,
      "messages": 315
    }
  ]
}
```

---

### Weekly Trends

```
GET /api/analytics/trends/weekly
```

---

### Hourly Distribution

```
GET /api/analytics/trends/hourly
```

**Response:**
```json
{
  "success": true,
  "data": {
    "distribution": {
      "0": 5, "1": 3, "2": 2, "3": 1, "4": 2,
      "5": 4, "6": 8, "7": 15, "8": 25, "9": 45,
      "10": 62, "11": 58, "12": 42, "13": 55, "14": 60,
      "15": 58, "16": 52, "17": 48, "18": 42, "19": 38,
      "20": 32, "21": 28, "22": 18, "23": 10
    },
    "peak_hour": 10,
    "peak_count": 62
  }
}
```

---

### Conversion Funnel

```
GET /api/analytics/funnel
```

**Response:**
```json
{
  "success": true,
  "data": {
    "funnel": [
      {"step": "Started Conversation", "count": 1250, "rate": 100},
      {"step": "Category Selected", "count": 1100, "rate": 88.0},
      {"step": "Conditions Entered", "count": 920, "rate": 73.6},
      {"step": "Recommendations Shown", "count": 856, "rate": 68.5},
      {"step": "Product Clicked", "count": 420, "rate": 33.6}
    ]
  },
  "meta": {
    "period_days": 30
  }
}
```

---

### Track Event

```
POST /api/analytics/track
```

**Request Body:**
```json
{
  "event_type": "supplement_click",
  "supplement_id": 1234,
  "category_id": 2,
  "session_id": "session_123",
  "metadata": {
    "source": "recommendation_card",
    "rank": 1
  }
}
```

**Event Types:**
- `supplement_click` - User clicked on a supplement
- `recommendation_view` - Recommendations were displayed
- `category_select` - User selected a category
- `comparison_view` - User viewed comparison

---

## Export/Sync APIs

### Export Supplements (Paginated)

```
GET /api/export/supplements
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | int | Results per page (max: 500, default: 100) |
| `page` | int | Page number |
| `category_id` | int | Filter by category |
| `min_score` | float | Minimum score filter |

---

### Export Summary (Lightweight)

```
GET /api/export/summary
```

Returns only essential fields: id, brand, title, category_id, scores, price, rating.

---

### Delta Sync (Changes Since)

```
GET /api/export/changes
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `since` | string | ISO 8601 timestamp |
| `per_page` | int | Results per page (max: 2000) |

**Example:**
```bash
curl "https://api.example.com/api/export/changes?since=2026-03-15T00:00:00Z"
```

**Response includes:**
- Changed supplements since timestamp
- `sync_timestamp` for next sync

---

### Export Categories

```
GET /api/export/categories
```

---

### Export Conditions

```
GET /api/export/conditions
```

---

### Full Export Info

```
GET /api/export/full
```

Returns categories, conditions, and instructions for full sync.

---

## Error Handling

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request (invalid parameters) |
| 404 | Not Found |
| 429 | Rate Limited |
| 500 | Server Error |

### Error Response Format

```json
{
  "success": false,
  "error": "Descriptive error message"
}
```

---

## Rate Limiting

Default limits (configurable):
- 100 requests per minute per IP
- 1000 requests per hour per IP

Rate limit headers:
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`

---

## Changelog

### v1.0.0 (March 2026)
- Initial release
- 42 API endpoints
- Clinical scoring methodology (price-free)
- Full analytics suite
- Export/sync capabilities

---

## Support

For API support, contact: api@stolosofficial.gr
