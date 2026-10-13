<?php

namespace App\Models;

use App\Models\Concerns\BelongsToApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Frame extends Model
{
    use BelongsToApp;

    protected $table = 'frames';

    protected $fillable = ['app_code', 'category_id', 'frames'];

    protected $casts = [
        'frames' => 'array', // JSON <-> PHP array
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FrameCategory::class, 'category_id');
    }
}
