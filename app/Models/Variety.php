<?php

namespace App\Models;

use Database\Factories\VarietyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'origin', 'user_id'])]
class Variety extends Model implements HasMedia
{
    /** @use HasFactory<VarietyFactory> */
    use HasFactory, InteractsWithMedia;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function catches(): HasMany
    {
        return $this->hasMany(AppleCatch::class);
    }

    /**
     * Global varieties (user_id null) plus the given user's own custom varieties.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->whereNull('user_id')->orWhere('user_id', $user->id);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('reference_photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('display')
            ->fit(Fit::Max, 1600, 1600)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 400)
            ->nonQueued();
    }
}
