<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->json('name');
            $table->json('code');
            $table->boolean('active')->default(true);
            $table->dateTime('active_from')->nullable();
            $table->integer('sort')->default(500);
            $table->string('preview_picture')->nullable();
            $table->json('preview_text')->nullable();
            $table->json('detail_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
