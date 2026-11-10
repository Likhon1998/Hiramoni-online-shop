<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false);
            }
            if (! Schema::hasColumn('customers', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable();
            }
            if (! Schema::hasColumn('customers', 'blocked_reason')) {
                $table->string('blocked_reason', 255)->nullable();
            }
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->default('Home');
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
        Schema::dropIfExists('customer_addresses');
        Schema::table('customers', function (Blueprint $table) {
            foreach (['blocked_reason', 'blocked_at', 'is_blocked'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
