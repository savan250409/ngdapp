<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class App extends Model
{
    protected $table = 'apps';

    protected $fillable = ['code', 'name', 'status'];

    /**
     * Create the module tables for a specific app code (e.g. ngd001, ngd002).
     * Tables created:
     *  - {code}_frames_category
     *  - {code}_frames
     *  - {code}_milestone_frame
     */
    public static function createAppTables(string $code): void
    {
        $categoriesTable = "{$code}_frames_category";
        $framesTable     = "{$code}_frames";
        $milestoneTable  = "{$code}_milestone_frame";

        if (! Schema::hasTable($categoriesTable)) {
            Schema::create($categoriesTable, function (Blueprint $table) {
                $table->id();
                $table->string('category_name')->unique();
                $table->string('category_image')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($framesTable)) {
            Schema::create($framesTable, function (Blueprint $table) use ($categoriesTable) {
                $table->id();
                $table->unsignedBigInteger('category_id')->unique();
                $table->longText('frames');
                $table->timestamps();

                $table->foreign('category_id')
                    ->references('id')->on($categoriesTable)
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable($milestoneTable)) {
            Schema::create($milestoneTable, function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('gender')->comment('1 = boy, 2 = girl');
                $table->unsignedTinyInteger('month')->comment('1 - 12');
                $table->longText('frames');
                $table->timestamps();

                $table->unique(['gender', 'month']);
            });
        }
    }

    /**
     * Drop the module tables for a specific app code.
     */
    public static function dropAppTables(string $code): void
    {
        Schema::dropIfExists("{$code}_frames");
        Schema::dropIfExists("{$code}_frames_category");
        Schema::dropIfExists("{$code}_milestone_frame");
    }
}
