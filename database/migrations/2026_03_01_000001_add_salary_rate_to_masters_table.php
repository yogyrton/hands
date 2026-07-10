<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table): void {
            // Ставка зарплаты в процентах от суммы оказанных услуг (по умолчанию 35%).
            $table->decimal('salary_rate', 5, 2)->default(35)->after('experience_label');
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table): void {
            $table->dropColumn('salary_rate');
        });
    }
};
