<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->string('client_first_name')->nullable()->after('number');
            $table->string('client_last_name')->nullable()->after('client_first_name');
            $table->string('client_phone')->nullable()->after('client_last_name');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropColumn(['client_first_name', 'client_last_name', 'client_phone']);
        });
    }
};
