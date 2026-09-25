<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ручной учёт зарплаты и расходов заменён автоматическим расчётом по месяцу
     * на дашборде (App\Support\MonthlyFinance). Таблицы больше не нужны —
     * все цифры пересчитываются из посещений и настроек. Данные удаляются
     * осознанно, откат не поддерживаем (down — no-op).
     */
    public function up(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_periods');
        Schema::dropIfExists('master_payouts');
        Schema::dropIfExists('payroll_periods');
    }

    public function down(): void
    {
        // Необратимо: старый табличный учёт снят намеренно.
    }
};
