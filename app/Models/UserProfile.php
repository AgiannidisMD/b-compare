<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * User Profile for multi-turn memory
 * Stores preferences, allergies, conditions across sessions
 */
class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_ip',
        'name',
        'email',
        'phone',
        // Stored preferences
        'known_conditions',
        'known_allergies',
        'dietary_preferences',
        'budget_preference',
        'preferred_brands',
        'disliked_brands',
        'preferred_dosage_forms',
        // Interaction history
        'categories_explored',
        'supplements_viewed',
        'supplements_recommended',
        'last_active_at',
        'total_conversations',
    ];

    protected $casts = [
        'known_conditions' => 'array',
        'known_allergies' => 'array',
        'dietary_preferences' => 'array',
        'preferred_brands' => 'array',
        'disliked_brands' => 'array',
        'preferred_dosage_forms' => 'array',
        'categories_explored' => 'array',
        'supplements_viewed' => 'array',
        'supplements_recommended' => 'array',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get or create profile for a session
     */
    public static function getOrCreateForSession(string $sessionId, ?string $ip = null): self
    {
        return self::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'user_ip' => $ip,
                'known_conditions' => [],
                'known_allergies' => [],
                'dietary_preferences' => [],
                'preferred_brands' => [],
                'disliked_brands' => [],
                'preferred_dosage_forms' => [],
                'categories_explored' => [],
                'supplements_viewed' => [],
                'supplements_recommended' => [],
                'total_conversations' => 0,
            ]
        );
    }

    /**
     * Update conditions (merge with existing)
     */
    public function addConditions(array $conditions): void
    {
        $existing = $this->known_conditions ?? [];
        $merged = array_unique(array_merge($existing, $conditions));
        $this->update(['known_conditions' => array_values($merged)]);
    }

    /**
     * Update allergies (merge with existing)
     */
    public function addAllergies(array $allergies): void
    {
        $existing = $this->known_allergies ?? [];
        $merged = array_unique(array_merge($existing, $allergies));
        $this->update(['known_allergies' => array_values($merged)]);
    }

    /**
     * Update dietary preferences
     */
    public function addDietaryPreferences(array $preferences): void
    {
        $existing = $this->dietary_preferences ?? [];
        $merged = array_unique(array_merge($existing, $preferences));
        $this->update(['dietary_preferences' => array_values($merged)]);
    }

    /**
     * Track explored category
     */
    public function trackCategoryExplored(int $categoryId): void
    {
        $explored = $this->categories_explored ?? [];
        if (!in_array($categoryId, $explored)) {
            $explored[] = $categoryId;
            $this->update(['categories_explored' => $explored]);
        }
    }

    /**
     * Track viewed supplement
     */
    public function trackSupplementViewed(int $supplementId): void
    {
        $viewed = $this->supplements_viewed ?? [];
        // Keep last 50 viewed
        if (!in_array($supplementId, $viewed)) {
            $viewed[] = $supplementId;
            if (count($viewed) > 50) {
                array_shift($viewed);
            }
            $this->update(['supplements_viewed' => $viewed]);
        }
    }

    /**
     * Track recommended supplement
     */
    public function trackSupplementRecommended(int $supplementId): void
    {
        $recommended = $this->supplements_recommended ?? [];
        // Keep last 100 recommended
        if (!in_array($supplementId, $recommended)) {
            $recommended[] = $supplementId;
            if (count($recommended) > 100) {
                array_shift($recommended);
            }
            $this->update(['supplements_recommended' => $recommended]);
        }
    }

    /**
     * Update last active timestamp and increment conversation count
     */
    public function recordActivity(): void
    {
        $this->update([
            'last_active_at' => now(),
            'total_conversations' => ($this->total_conversations ?? 0) + 1,
        ]);
    }

    /**
     * Get memory context for AI prompts
     */
    public function getMemoryContext(): string
    {
        $context = [];

        if (!empty($this->known_conditions)) {
            $context[] = "Γνωστές καταστάσεις: " . implode(', ', $this->known_conditions);
        }

        if (!empty($this->known_allergies)) {
            $context[] = "Αλλεργίες: " . implode(', ', $this->known_allergies);
        }

        if (!empty($this->dietary_preferences)) {
            $context[] = "Διατροφικές προτιμήσεις: " . implode(', ', $this->dietary_preferences);
        }

        if ($this->budget_preference) {
            $context[] = "Προτιμώμενο budget: €{$this->budget_preference}";
        }

        if (!empty($this->preferred_brands)) {
            $context[] = "Αγαπημένες μάρκες: " . implode(', ', $this->preferred_brands);
        }

        if (!empty($this->preferred_dosage_forms)) {
            $context[] = "Προτιμώμενες μορφές: " . implode(', ', $this->preferred_dosage_forms);
        }

        if ($this->total_conversations > 0) {
            $context[] = "Αυτή είναι η {$this->total_conversations}η συνομιλία μαζί μας";
        }

        return empty($context) ? '' : "# ΠΡΟΦΙΛ ΧΡΗΣΤΗ (από προηγούμενες συνομιλίες)\n" . implode("\n", $context) . "\n";
    }

    /**
     * Relationship to conversations
     */
    public function conversations()
    {
        return $this->hasMany(ChatConversation::class, 'session_id', 'session_id');
    }
}
