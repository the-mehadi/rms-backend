<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            // Add table_id column
            $table->foreignId('table_id')->after('id')->constrained('tables')->cascadeOnDelete();
        });

        // Drop the foreign key constraint on order_id first, then the unique constraint
        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'order_id')) {
                try {
                    // Drop foreign key constraint first
                    $table->dropForeign(['order_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }

                try {
                    // Then drop the unique constraint
                    $table->dropUnique('bills_order_id_unique');
                } catch (\Exception $e) {
                    // Unique constraint might not exist, continue
                }
            }
        });

        // Create pivot table for multiple orders per bill
        Schema::create('bill_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bill_id', 'order_id']);
            $table->index('bill_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_orders');

        Schema::table('bills', function (Blueprint $table) {
            // Drop the table_id foreign key and column
            $table->dropForeignIdFor(\App\Models\Table::class);
            $table->dropColumn('table_id');

            // Restore the foreign key and unique constraint on order_id if needed
            if (Schema::hasColumn('bills', 'order_id')) {
                try {
                    $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
                    $table->unique('order_id');
                } catch (\Exception $e) {
                    // Constraints might already exist or can't be restored, continue
                }
            }
        });
    }
};
