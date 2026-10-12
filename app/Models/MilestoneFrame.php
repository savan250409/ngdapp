<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneFrame extends Model
{
    protected $table = 'milestone_frames';

    protected $fillable = ['gender', 'month', 'frames'];

    protected $casts = [
        'frames' => 'array', // JSON <-> PHP array
    ];

    /** 1 = boy, 2 = girl */
    public const GENDERS = [1 => 'Boy', 2 => 'Girl'];

    public function getGenderLabelAttribute(): string
    {
        return self::GENDERS[$this->gender] ?? 'Unknown';
    }

    /** Folder-safe gender slug used in the upload path (boy / girl). */
    public function getGenderSlugAttribute(): string
    {
        return strtolower(self::GENDERS[$this->gender] ?? 'unknown');
    }

    public static function genderSlug($gender): string
    {
        return strtolower(self::GENDERS[(int) $gender] ?? 'unknown');
    }
}
