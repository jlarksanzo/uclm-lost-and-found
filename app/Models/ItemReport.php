<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'name', 'description', 'location', 'occurred_on', 'photo_path', 'status', 'category'])]
class ItemReport extends Model
{
    public const CATEGORIES = ['IDs & cards', 'Electronics', 'Bags', 'Books & supplies', 'Clothing', 'Keys', 'Other'];

    protected function casts(): array
    {
        return ['occurred_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function returnRecord()
    {
        return $this->hasOne(ReturnRecord::class);
    }

    public function visibleTo(User $user): bool
    {
        return $this->status === 'approved' || $this->user_id === $user->id || $user->is_admin
            || ($this->status === 'returned' && $this->claims()->where('user_id', $user->id)->where('status', 'approved')->exists());
    }

    public function editable(): bool
    {
        return $this->status !== 'returned' && ! $this->claims()->whereIn('status', ['pending', 'approved'])->exists();
    }

    public function matches()
    {
        $tokens = static function (string $text): array {
            return array_values(array_unique(array_filter(preg_split('/[^\pL\pN]+/u', mb_strtolower($text)),
                fn ($word) => mb_strlen($word) >= 3 && ! in_array($word, ['the', 'and', 'with', 'lost', 'found', 'item', 'near', 'was', 'this', 'that', 'for']))));
        };
        $words = $tokens($this->name.' '.$this->description);
        $location = $tokens($this->location);
        $matches = collect();
        foreach (self::where('status', 'approved')->where('type', $this->type === 'lost' ? 'found' : 'lost')
            ->where('user_id', '!=', $this->user_id)->lazyById() as $candidate) {
            $shared = count(array_intersect($words, $tokens($candidate->name.' '.$candidate->description)));
            $samePlace = count(array_intersect($location, $tokens($candidate->location))) > 0;
            $sameCategory = $this->category !== 'Other' && $this->category === $candidate->category;
            $score = min($shared, 3) * 2 + ($samePlace ? 2 : 0) + ($sameCategory ? 2 : 0);
            if ($score >= 4) {
                $candidate->match_reasons = implode(' · ', array_filter([$shared ? 'Similar words' : null, $samePlace ? 'Similar location' : null, $sameCategory ? 'Same category' : null]));
                $candidate->match_score = $score;
                $matches->push($candidate);
                $matches = $matches->sortByDesc('match_score')->take(5)->values();
            }
        }

        return $matches;
    }
}
