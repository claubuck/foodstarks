<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cobro manual de suscripciones: el superadmin registra si el periodo fue pagado
 * (no hay cobro online todavía).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('current_period_end');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('paid_at');
            $table->string('payment_reference')->nullable()->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'amount_paid', 'payment_reference']);
        });
    }
};
