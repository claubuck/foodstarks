<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This migration is deprecated. branch_id is now defined in the create_* migrations.
     * Run migrate:fresh after removing this file from your deployment.
     */
    public function up(): void
    {
        // No-op: branch_id already in create_products_table, create_orders_table,
        // create_tables_table, create_reservations_table
    }

    public function down(): void
    {
        // No-op
    }
};
