<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_id')->constrained()->cascadeOnDelete();
            $table->date('advance_date')->nullable();
            $table->decimal('advance_amount', 10, 2)->nullable();
            $table->date('salary_date')->nullable();
            $table->decimal('salary_amount', 10, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'master_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_payouts');
    }
};
