<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - order_details.order_id: cascade (borrar un pedido borra sus ítems).
 * - reservations.table_id: nullable + null on delete (borrar una mesa conserva el historial de reservas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('table_id')->nullable()->change();
            $table->foreign('table_id')->references('id')->on('tables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')->references('id')->on('orders');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('table_id')->nullable(false)->change();
            $table->foreign('table_id')->references('id')->on('tables');
        });
    }
};
