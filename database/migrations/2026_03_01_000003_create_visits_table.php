<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('master_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->decimal('base_price', 10, 2)->default(0);      // цена по прайсу
            $table->decimal('service_price', 10, 2)->default(0);   // итог (база зарплаты)
            $table->decimal('paid_amount', 10, 2)->default(0);     // реально деньгами
            $table->string('payment_type');                        // cash|card|mixed|certificate
            $table->string('discount_reason')->nullable();
            $table->foreignId('certificate_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment')->nullable();
            $table->dateTime('performed_at');
            $table->timestamps();

            $table->index('performed_at');
            $table->index('master_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
