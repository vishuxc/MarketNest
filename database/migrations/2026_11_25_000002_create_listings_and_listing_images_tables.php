<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['product', 'service']);
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('subcategory_id');
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('area_id');
            $table->decimal('price', 12, 2)->unsigned();
            $table->timestamps();

            // Keep each listing's subcategory and area tied to its selected parent.
            $table->foreign(['category_id', 'subcategory_id'], 'listings_category_subcategory_fk')
                ->references(['category_id', 'id'])->on('subcategories')->restrictOnDelete();
            $table->foreign(['city_id', 'area_id'], 'listings_city_area_fk')
                ->references(['city_id', 'id'])->on('areas')->restrictOnDelete();

            $table->index(['category_id', 'subcategory_id']);
            $table->index(['city_id', 'area_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('subcategory_id');
            $table->index('area_id');
            $table->index('price');
            $table->index('created_at');
        });

        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['listing_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('listings');
    }
};
