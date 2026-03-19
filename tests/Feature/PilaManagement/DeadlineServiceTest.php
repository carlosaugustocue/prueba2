<?php

namespace Tests\Feature\PilaManagement;

use App\Modules\PilaManagement\Services\DeadlineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeadlineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_day_from_document_uses_config_table(): void
    {
        $svc = new DeadlineService();

        $this->assertSame(16, $svc->paymentBusinessDayFromDocument('900.000.094'));
        $this->assertSame(2, $svc->paymentBusinessDayFromDocument('900000000-01'));
        $this->assertSame(12, $svc->paymentBusinessDayFromDocument('901776975-4')); // últimos 2 = 75 → día 12
    }

    public function test_due_date_is_nth_business_day_of_next_month_skipping_weekends_and_holidays(): void
    {
        // Marzo 2026: declarar el 2 de marzo como festivo para forzar salto.
        DB::table('colombian_holidays')->insert([
            'date' => '2026-03-02',
            'name' => 'Festivo prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $svc = new DeadlineService();

        // Período: Febrero 2026 → vence en Marzo 2026.
        // Marzo 2026 inicia domingo (01). Lun 02 sería hábil pero lo marcamos festivo.
        // Entonces el 1er día hábil es Mar 03 y el 2do día hábil es Mar 04.
        $due = $svc->dueDateForPeriodByPaymentDay(2026, 2, 2);

        $this->assertSame('2026-03-04', $due->toDateString());
        $this->assertFalse($due->isWeekend());
    }
}

