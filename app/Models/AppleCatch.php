<?php

namespace App\Models;

use Database\Factories\AppleCatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['user_id', 'variety_id', 'caught_at', 'lat', 'lng', 'location_label', 'notes', 'is_favorite'])]
class AppleCatch extends Model implements HasMedia
{
    /** @use HasFactory<AppleCatchFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'catches';

    protected function casts(): array
    {
        return [
            'caught_at' => 'date',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_favorite' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
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
