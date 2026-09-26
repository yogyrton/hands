<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Скидки постоянным клиентам («5-й массаж −15%», «10-й массаж −50%»)
     * оформляются как обычные акции, чтобы их можно было выбрать при
     * оформлении посещения и цена пересчиталась сама. Но на витрину сайта
     * они не нужны — это внутренние скидки. Для этого добавляем флаг
     * show_on_site: акция активна и доступна в учёте, но на сайте скрыта.
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->boolean('show_on_site')->default(true)->after('is_active');
        });

        $now = now();

        $loyalty = [
            [
                'slug' => 'loyal-15',
                'title' => 'Постоянный клиент − 5 посещение',
                'description' => 'Скидка 15% постоянному клиенту (с 5-го массажа).',
                'discount_percent' => 15,
                'sort_order' => 90,
            ],
            [
                'slug' => 'loyal-50',
                'title' => 'Постоянный клиент − 10 посещение',
                'description' => 'Скидка 50% постоянному клиенту (с 10-го массажа).',
                'discount_percent' => 50,
                'sort_order' => 91,
            ],
        ];

        foreach ($loyalty as $item) {
            DB::table('promotions')->updateOrInsert(
                ['slug' => $item['slug']],
                array_merge($item, [
                    'is_active' => true,
                    'show_on_site' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            );
        }
    }

    public function down(): void
    {
        DB::table('promotions')->whereIn('slug', ['loyal-15', 'loyal-50'])->delete();

        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropColumn('show_on_site');
        });
    }
};
