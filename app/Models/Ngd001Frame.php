<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ngd001Frame extends Model
{
    protected $table = 'ngd001_frames';

    protected $fillable = ['category_id', 'frames'];

    protected $casts = [
        'frames' => 'array', // JSON <-> PHP array
    ];

    public static function tableName(): string
    {
        return 'ngd001_frames';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Ngd001FrameCategory::class, 'category_id');
    }
}
