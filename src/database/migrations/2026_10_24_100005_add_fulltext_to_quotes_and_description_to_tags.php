<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->text('description')->nullable()->after('category_id');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('quotes', function (Blueprint $table) {
                $table->fullText('text');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('quotes', function (Blueprint $table) {
                $table->dropFullText('text');
            });
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
