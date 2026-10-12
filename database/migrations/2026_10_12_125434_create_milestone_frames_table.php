<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_frames', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('gender')->comment('1 = boy, 2 = girl');
            $table->unsignedTinyInteger('month')->comment('1 - 12');
            $table->json('frames'); // ordered list of frame objects
            $table->timestamps();

            $table->unique(['gender', 'month']); // one record per gender+month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_frames');
    }
};
