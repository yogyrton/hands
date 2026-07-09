<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type'); // sale | usage | correction
            $table->decimal('amount', 10, 2); // для money: -40; для visits: -1
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_operations');
    }
};
