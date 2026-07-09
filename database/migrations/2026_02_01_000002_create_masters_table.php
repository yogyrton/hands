<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masters', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_dative');
            $table->string('role');
            $table->string('yclients_url');
            $table->string('experience_label')->nullable();
            $table->text('bio1');
            $table->text('bio2');
            $table->jsonb('principles')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masters');
    }
};
