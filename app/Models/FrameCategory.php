<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FrameCategory extends Model
{
    protected $table = 'frame_categories';

    protected $fillable = ['category_name', 'category_image', 'status'];

    public function frames(): HasMany
    {
        return $this->hasMany(Frame::class, 'category_id');
    }
}
