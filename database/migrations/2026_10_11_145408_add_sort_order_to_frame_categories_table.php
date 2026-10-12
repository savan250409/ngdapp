<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frame_categories', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('status')->index();
        });

        // Backfill: keep the current "newest first" order (1 = newest).
        $i = 1;
        foreach (DB::table('frame_categories')->orderBy('id', 'desc')->pluck('id') as $id) {
            DB::table('frame_categories')->where('id', $id)->update(['sort_order' => $i++]);
        }
    }

    public function down(): void
    {
        Schema::table('frame_categories', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
