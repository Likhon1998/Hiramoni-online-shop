<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_services', function (Blueprint $table) {
            if (! Schema::hasColumn('courier_services', 'driver')) {
                $table->string('driver', 32)->default('manual')->after('name');
            }
            if (! Schema::hasColumn('courier_services', 'api_base_url')) {
                $table->string('api_base_url', 255)->nullable()->after('driver');
            }
            if (! Schema::hasColumn('courier_services', 'api_key')) {
                $table->text('api_key')->nullable()->after('api_base_url');
            }
            if (! Schema::hasColumn('courier_services', 'api_secret')) {
                $table->text('api_secret')->nullable()->after('api_key');
            }
            if (! Schema::hasColumn('courier_services', 'webhook_secret')) {
                $table->string('webhook_secret', 120)->nullable()->after('api_secret');
            }
            if (! Schema::hasColumn('courier_services', 'extra_config')) {
                $table->json('extra_config')->nullable()->after('webhook_secret');
            }
            if (! Schema::hasColumn('courier_services', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('is_active');
            }
        });

        if (Schema::hasTable('courier_services')) {
            foreach (DB::table('courier_services')->select('id', 'name')->get() as $row) {
                $driver = match (Str::lower((string) $row->name)) {
                    'steadfast' => 'steadfast',
                    'pathao' => 'pathao',
                    'redx', 'red-x', 'red x' => 'redx',
                    default => 'manual',
                };
                DB::table('courier_services')->where('id', $row->id)->update(['driver' => $driver]);
            }
        }

        Schema::create('courier_consignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_service_id')->nullable()->constrained('courier_services')->nullOnDelete();
            $table->string('type', 20)->default('forward');
            $table->string('driver', 32)->default('manual');
            $table->string('tracking_number', 80)->nullable();
            $table->string('consignment_id', 80)->nullable();
            $table->string('invoice_ref', 80)->nullable();
            $table->string('status', 32)->default('booked');
            $table->string('courier_status', 80)->nullable();
            $table->boolean('is_remote')->default(false);
            $table->json('payload')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status', 'type']);
            $table->index(['order_id', 'type']);
            $table->index(['tracking_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_consignments');

        Schema::table('courier_services', function (Blueprint $table) {
            foreach (['driver', 'api_base_url', 'api_key', 'api_secret', 'webhook_secret', 'extra_config', 'last_synced_at'] as $col) {
                if (Schema::hasColumn('courier_services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
