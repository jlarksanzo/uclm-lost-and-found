<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
#[Fillable(['user_id', 'type', 'name', 'description', 'location', 'occurred_on', 'photo_path', 'status'])]
class ItemReport extends Model {
    protected function casts(): array { return ['occurred_on' => 'date']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
