<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Frame extends Model
{
    protected $table = 'frames';

    protected $fillable = ['category_id', 'frames'];

    protected $casts = [
        'frames' => 'array', // JSON <-> PHP array
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FrameCategory::class, 'category_id');
    }
}
