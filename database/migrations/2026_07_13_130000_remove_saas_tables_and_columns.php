<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscription_requests');

        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';
        if ($sqlite) {
            Schema::disableForeignKeyConstraints();
        }

        if (Schema::hasTable('shops') && Schema::hasColumn('shops', 'upcoming_package_id')) {
            $this->dropForeignKeyIfExists('shops', 'shops_upcoming_package_id_foreign');
            $this->dropForeignKeyIfExists('shops', 'upcoming_package_id');

            try {
                Schema::table('shops', function (Blueprint $table) {
                    if (Schema::hasColumn('shops', 'upcoming_package_id')) {
                        $table->dropColumn('upcoming_package_id');
                    }
                });
            } catch (\Throwable) {
                // SQLite may retain the column if the FK definition cannot be rewritten — safe to continue.
            }
        }

        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                foreach (['subscription_plan', 'subscription_start_date', 'subscription_end_date'] as $column) {
                    if (Schema::hasColumn('shops', $column)) {
                        try {
                            $table->dropColumn($column);
                        } catch (\Throwable) {
                            // ignore
                        }
                    }
                }
            });
        }

        // On SQLite, shops may still embed a FK to packages in the table SQL even after
        // dropColumn fails. Keep an empty packages table so inserts into shops succeed.
        if ($sqlite && Schema::hasColumn('shops', 'upcoming_package_id')) {
            if (! Schema::hasTable('packages')) {
                Schema::create('packages', function (Blueprint $table) {
                    $table->id();
                    $table->string('name')->nullable();
                    $table->timestamps();
                });
            }
        } else {
            Schema::dropIfExists('packages');
        }

        if ($sqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('duration_days');
            $table->integer('max_staff')->default(5);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_start_date')->nullable();
            $table->timestamp('subscription_end_date')->nullable();
            $table->foreignId('upcoming_package_id')->nullable()->constrained('packages')->nullOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $table, string $constraintOrColumn): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite (phpunit) has no information_schema — try common constraint names directly.
        if ($driver === 'sqlite') {
            foreach ([
                $constraintOrColumn,
                $constraintOrColumn.'_foreign',
                $table.'_'.$constraintOrColumn.'_foreign',
            ] as $name) {
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($name) {
                        $blueprint->dropForeign($name);
                    });

                    return;
                } catch (\Throwable) {
                    // try next
                }
            }

            return;
        }

        $schema = Schema::getConnection()->getDatabaseName();

        $constraint = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->where(function ($q) use ($constraintOrColumn, $table) {
                $q->where('CONSTRAINT_NAME', $constraintOrColumn)
                    ->orWhere('CONSTRAINT_NAME', $constraintOrColumn.'_foreign')
                    ->orWhere('CONSTRAINT_NAME', $table.'_'.$constraintOrColumn.'_foreign');
            })
            ->value('CONSTRAINT_NAME');

        if (! $constraint) {
            // Fallback: find FK by column name (hosts that rename constraints).
            $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('CONSTRAINT_SCHEMA', $schema)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $constraintOrColumn)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('CONSTRAINT_NAME');
        }

        if (! $constraint) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($constraint) {
                $blueprint->dropForeign($constraint);
            });
        } catch (\Throwable) {
            // Already gone or unsupported — safe to continue and drop the column.
        }
    }
};
