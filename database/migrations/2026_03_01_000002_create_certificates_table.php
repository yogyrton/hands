<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('type'); // visits | money
            $table->unsignedInteger('initial_visits')->nullable();
            $table->decimal('initial_amount', 10, 2)->nullable();
            $table->unsignedInteger('remaining_visits')->nullable();
            $table->decimal('remaining_amount', 10, 2)->nullable();
            $table->date('sold_at');
            $table->date('expires_at');
            $table->string('status')->default('active'); // active | used | expired
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
