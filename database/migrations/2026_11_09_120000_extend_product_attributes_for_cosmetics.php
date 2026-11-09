<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('product_attributes', 'is_variation')) {
                $table->boolean('is_variation')->default(false)->after('is_filterable');
            }
        });

        if (! Schema::hasTable('category_product_attribute')) {
            Schema::create('category_product_attribute', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_variation_default')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['category_id', 'product_attribute_id'], 'cat_attr_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product_attribute');

        Schema::table('product_attributes', function (Blueprint $table) {
            if (Schema::hasColumn('product_attributes', 'is_variation')) {
                $table->dropColumn('is_variation');
            }
        });
    }
};
