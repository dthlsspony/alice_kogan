<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt')->nullable();
            $table->longText('body');
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('is_published');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('pages', function (Blueprint $table) {
                $table->fullText(['title', 'body']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
