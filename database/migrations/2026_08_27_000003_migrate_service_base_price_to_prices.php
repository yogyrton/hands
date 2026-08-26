<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Переносим текущую base_price каждой услуги в стартовую строку прайса
     * (длительность 60 мин, одна цена для обеих должностей) и убираем колонку
     * base_price у услуг — теперь ценами услуги управляет прайс.
     */
    public function up(): void
    {
        if (Schema::hasColumn('services', 'base_price')) {
            foreach (DB::table('services')->get() as $service) {
                $already = DB::table('service_prices')->where('service_id', $service->id)->exists();
                if ($already) {
                    continue;
                }

                DB::table('service_prices')->insert([
                    'service_id' => $service->id,
                    'duration_minutes' => 60,
                    'price_master' => $service->base_price,
                    'price_pro' => $service->base_price,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('services', function (Blueprint $table): void {
                $table->dropColumn('base_price');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('services', 'base_price')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->decimal('base_price', 10, 2)->default(0);
            });

            // Возвращаем цену из самой короткой строки прайса (цена мастера).
            foreach (DB::table('services')->get() as $service) {
                $price = DB::table('service_prices')
                    ->where('service_id', $service->id)
                    ->orderBy('duration_minutes')
                    ->value('price_master');

                if ($price !== null) {
                    DB::table('services')->where('id', $service->id)->update(['base_price' => $price]);
                }
            }
        }
    }
};
