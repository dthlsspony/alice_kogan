<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['articles', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('seo_title')->nullable()->after('excerpt');
                $table->string('meta_description', 500)->nullable()->after('seo_title');
            });
        }
    }

    public function down(): void
    {
        foreach (['articles', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['seo_title', 'meta_description']);
            });
        }
    }
};
