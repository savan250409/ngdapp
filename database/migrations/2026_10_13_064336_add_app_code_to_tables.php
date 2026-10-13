<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users: null app_code = super-admin (all apps); otherwise scoped to one app.
        Schema::table('users', function (Blueprint $table) {
            $table->string('app_code')->nullable()->after('email')->index();
        });

        Schema::table('frame_categories', function (Blueprint $table) {
            $table->string('app_code')->nullable()->after('id')->index();
        });
        Schema::table('frames', function (Blueprint $table) {
            $table->string('app_code')->nullable()->after('id')->index();
        });
        Schema::table('milestone_frames', function (Blueprint $table) {
            $table->string('app_code')->nullable()->after('id')->index();
        });

        // Backfill everything that already exists to the default app.
        DB::table('apps')->updateOrInsert(['code' => 'ngd001'], ['name' => 'NGD 001', 'status' => 1]);
        DB::table('users')->update(['app_code' => null]); // keep existing users as super-admins
        DB::table('frame_categories')->update(['app_code' => 'ngd001']);
        DB::table('frames')->update(['app_code' => 'ngd001']);
        DB::table('milestone_frames')->update(['app_code' => 'ngd001']);

        // Uniqueness becomes per-app.
        Schema::table('frame_categories', function (Blueprint $table) {
            $table->dropUnique('frame_categories_category_name_unique');
            $table->unique(['app_code', 'category_name']);
        });
        Schema::table('milestone_frames', function (Blueprint $table) {
            $table->dropUnique('milestone_frames_gender_month_unique');
            $table->unique(['app_code', 'gender', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('frame_categories', function (Blueprint $table) {
            $table->dropUnique(['app_code', 'category_name']);
            $table->unique('category_name');
            $table->dropIndex(['app_code']);
            $table->dropColumn('app_code');
        });
        Schema::table('milestone_frames', function (Blueprint $table) {
            $table->dropUnique(['app_code', 'gender', 'month']);
            $table->unique(['gender', 'month']);
            $table->dropIndex(['app_code']);
            $table->dropColumn('app_code');
        });
        Schema::table('frames', function (Blueprint $table) {
            $table->dropIndex(['app_code']);
            $table->dropColumn('app_code');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['app_code']);
            $table->dropColumn('app_code');
        });
    }
};
