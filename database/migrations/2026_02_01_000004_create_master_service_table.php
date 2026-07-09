<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_service', function (Blueprint $table): void {
            $table->foreignId('master_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['master_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_service');
    }
};
