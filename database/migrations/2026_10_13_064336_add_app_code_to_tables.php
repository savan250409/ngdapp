<?php

use App\Models\App;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. users: null app_code = super-admin; otherwise scoped to one app.
        if (! Schema::hasColumn('users', 'app_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('app_code')->nullable()->after('email')->index();
            });
        }

        // 2. Register default ngd001 app.
        DB::table('apps')->updateOrInsert(
            ['code' => 'ngd001'],
            ['name' => 'NGD 001', 'status' => 1, 'updated_at' => now(), 'created_at' => now()]
        );

        // 3. Migrate existing tables into ngd001 module tables (without app_code column).
        // If legacy tables exist, rename them to the ngd001 module names.
        if (Schema::hasTable('frame_categories') && ! Schema::hasTable('ngd001_frames_category')) {
            if (Schema::hasColumn('frame_categories', 'app_code')) {
                Schema::table('frame_categories', function (Blueprint $table) {
                    $table->dropColumn('app_code');
                });
            }
            Schema::rename('frame_categories', 'ngd001_frames_category');
        }

        if (Schema::hasTable('frames') && ! Schema::hasTable('ngd001_frames')) {
            if (Schema::hasColumn('frames', 'app_code')) {
                Schema::table('frames', function (Blueprint $table) {
                    $table->dropColumn('app_code');
                });
            }
            Schema::rename('frames', 'ngd001_frames');
        }

        if (Schema::hasTable('milestone_frames') && ! Schema::hasTable('ngd001_milestone_frame')) {
            if (Schema::hasColumn('milestone_frames', 'app_code')) {
                Schema::table('milestone_frames', function (Blueprint $table) {
                    $table->dropColumn('app_code');
                });
            }
            Schema::rename('milestone_frames', 'ngd001_milestone_frame');
        }

        // 4. Ensure ngd001 tables are created if this is run fresh.
        App::createAppTables('ngd001');
    }

    public function down(): void
    {
        App::dropAppTables('ngd001');

        if (Schema::hasColumn('users', 'app_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['app_code']);
                $table->dropColumn('app_code');
            });
        }
    }
};
