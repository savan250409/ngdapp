<?php

namespace App\Models;

use App\Models\Concerns\BelongsToApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FrameCategory extends Model
{
    use BelongsToApp;

    protected $table = 'frame_categories';

    protected $fillable = ['app_code', 'category_name', 'category_image', 'status', 'sort_order'];

    public function frames(): HasMany
    {
        return $this->hasMany(Frame::class, 'category_id');
    }
}
