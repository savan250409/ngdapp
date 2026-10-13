<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ngd001MilestoneFrame extends Model
{
    protected $table = 'ngd001_milestone_frame';

    protected $fillable = ['gender', 'month', 'frames'];

    protected $casts = [
        'frames' => 'array', // JSON <-> PHP array
    ];

    /** 1 = boy, 2 = girl */
    public const GENDERS = [1 => 'Boy', 2 => 'Girl'];

    public static function tableName(): string
    {
        return 'ngd001_milestone_frame';
    }

    public function getGenderLabelAttribute(): string
    {
        return self::GENDERS[$this->gender] ?? 'Unknown';
    }

    public function getGenderSlugAttribute(): string
    {
        return strtolower(self::GENDERS[$this->gender] ?? 'unknown');
    }

    public static function genderSlug($gender): string
    {
        return strtolower(self::GENDERS[(int) $gender] ?? 'unknown');
    }
}
