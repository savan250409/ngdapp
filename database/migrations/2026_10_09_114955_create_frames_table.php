<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frames', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            // One frame record per category: enforce 1:1 at the DB level.
            $table->unique('category_id');
            $table->json('frames'); // ordered list of frame objects
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')->on('frame_categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frames');
    }
};
