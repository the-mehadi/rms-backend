<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->string('original_filename')->nullable();
            $table->string('url');
            $table->string('format', 10)->default('webp');
            $table->string('hash', 16)->nullable();
            $table->string('alt_text')->nullable();
            $table->integer('order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_images');
    }
};
