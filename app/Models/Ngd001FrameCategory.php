<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ngd001FrameCategory extends Model
{
    protected $table = 'ngd001_frames_category';

    protected $fillable = ['category_name', 'category_image', 'status', 'sort_order'];

    public static function tableName(): string
    {
        return 'ngd001_frames_category';
    }

    public function frames(): HasMany
    {
        return $this->hasMany(Ngd001Frame::class, 'category_id');
    }
}
