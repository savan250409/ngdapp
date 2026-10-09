<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frame_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();   // also the on-disk folder name
            $table->string('category_image')->nullable(); // thumbnail file name only
            $table->tinyInteger('status')->default(1);    // 1 = active, 0 = inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frame_categories');
    }
};
