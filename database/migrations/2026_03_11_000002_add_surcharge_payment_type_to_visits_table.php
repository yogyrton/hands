<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            // Метод доплаты (нал/карта) для оплаты «сертификат с доплатой».
            $table->string('surcharge_payment_type')->nullable()->after('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropColumn('surcharge_payment_type');
        });
    }
};
