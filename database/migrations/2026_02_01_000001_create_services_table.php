<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('level')->default(3);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->string('duration_label')->default('от 60 мин');
            $table->string('price_label')->default('от 50 р');
            $table->text('lead');
            $table->string('ideal')->nullable();
            $table->string('request_lead')->nullable();
            $table->json('includes')->nullable();
            $table->json('requests')->nullable();
            $table->json('details')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
