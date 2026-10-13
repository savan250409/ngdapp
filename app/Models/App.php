<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $table = 'apps';

    protected $fillable = ['code', 'name', 'status'];
}
