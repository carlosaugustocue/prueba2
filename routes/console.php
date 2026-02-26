<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Modules\Appointments\Models\Reminder;
use App\Modules\Appointments\Jobs\SendReminderJob;
use App\Modules\SocialSecurity\Services\PayrollBatchService;
use App\Modules\SocialSecurity\Services\PayrollService;

Artisan::command('serve:network-info', function () {
    $ips = [];
    if (PHP_OS_FAMILY === 'Windows') {
        exec('ipconfig 2>NUL', $out);
        foreach ($out as $line) {
            if (preg_match('/IPv4[^:]*:\s*(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
                $ips[] = $m[1];
            }
        }
    } else {
        exec('hostname -I 2>/dev/null', $out);
        if (!empty($out)) {
            $ips = array_filter(explode(' ', trim($out[0])));
        }
        if (empty($ips)) {
            exec("ip -4 addr show 2>/dev/null | grep -oE 'inet [0-9.]+' | awk '{print \$2}'", $ips);
        }
    }
    $ips = array_unique(array_filter($ips, fn ($ip) => $ip !== '127.0.0.1'));
    $this->line('');
    $this->info('Para acceder desde el celular u otro equipo en la red:');
    $this->line('');
    if (empty($ips)) {
        $this->warn('No se detectó IP de red. Ejecuta: php artisan serve --host=0.0.0.0');
        $this->line('Luego en el celular usa: http://<IP-DEL-PC>:8000');
    } else {
        foreach ($ips as $ip) {
            $this->line('  → <href=http://' . $ip . ':8000>http://' . $ip . ':8000</>');
        }
        $this->line('');
        $this->comment('Asegúrate de iniciar el servidor con: php artisan serve --host=0.0.0.0');
    }
    $this->line('');
})->purpose('Muestra la URL para abrir la app desde el celular en la misma red');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('appointments:dispatch-due-reminders', function () {
    $batchSize = 50;
    $due = Reminder::dueToSend()
        ->where('type', Reminder::TYPE_REMINDER_24H)
        ->where('channel', Reminder::CHANNEL_WHATSAPP)
        ->orderBy('scheduled_at')
        ->limit($batchSize)
        ->get();

    foreach ($due as $reminder) {
        // Marcar como processing para evitar re-dispatch
        $reminder->markAsProcessing();
        SendReminderJob::dispatch($reminder->id);
    }

    $this->info("Recordatorios encolados: " . $due->count());
})->purpose('Encola recordatorios WhatsApp vencidos');

// Scheduler: ejecutar cada minuto
Schedule::command('appointments:dispatch-due-reminders')
    ->everyMinute()
    ->withoutOverlapping();

// Seguridad Social: generar planillas del mes (opciones --year= y --month=)
Artisan::command('payroll:generate-monthly {--year= : Año (default: actual)} {--month= : Mes (default: actual)}', function () {
    $year = (int) ($this->option('year') ?: now()->format('Y'));
    $month = (int) ($this->option('month') ?: now()->format('n'));
    if ($month < 1 || $month > 12) {
        $this->error('Mes debe estar entre 1 y 12.');
        return 1;
    }
    $result = app(PayrollBatchService::class)->generateMonthlyPayrolls($year, $month);
    $this->info("Creadas: {$result['created']}, Omitidas: {$result['skipped']}");
    if (!empty($result['errors'])) {
        $this->warn('Errores por afiliado: ' . count($result['errors']));
        foreach (array_slice($result['errors'], 0, 10, true) as $affiliateId => $msg) {
            $this->line("  Afiliado {$affiliateId}: {$msg}");
        }
        if (count($result['errors']) > 10) {
            $this->line('  ... y ' . (count($result['errors']) - 10) . ' más.');
        }
    }
    return 0;
})->purpose('Genera planillas para todos los afiliados activos con perfil SS en el año/mes indicado');

// Seguridad Social: marcar planillas vencidas como OVERDUE
Artisan::command('payroll:mark-overdue', function () {
    $count = app(PayrollService::class)->markOverduePayrolls();
    $this->info("Planillas marcadas en mora: {$count}");
    return 0;
})->purpose('Marca como OVERDUE las planillas con due_date pasado que no están pagadas');

// Seguridad Social: liquidar (settle) todas las planillas pendientes del mes
Artisan::command('payroll:settle-monthly {--year= : Año (default: actual)} {--month= : Mes (default: actual)} {--payer= : Opcional ID de pagador}', function () {
    $year = (int) ($this->option('year') ?: now()->format('Y'));
    $month = (int) ($this->option('month') ?: now()->format('n'));
    $payerId = $this->option('payer') ? (int) $this->option('payer') : null;
    if ($month < 1 || $month > 12) {
        $this->error('Mes debe estar entre 1 y 12.');
        return 1;
    }
    $result = app(PayrollBatchService::class)->settleMonthlyPayrolls($year, $month, $payerId);
    $this->info("Liquidadas: {$result['settled']}");
    if (!empty($result['errors'])) {
        $this->warn('Errores: ' . count($result['errors']));
        foreach (array_slice($result['errors'], 0, 10, true) as $payrollId => $msg) {
            $this->line("  Planilla {$payrollId}: {$msg}");
        }
        if (count($result['errors']) > 10) {
            $this->line('  ... y ' . (count($result['errors']) - 10) . ' más.');
        }
    }
    return 0;
})->purpose('Liquida todas las planillas PENDING del año/mes indicado');

Schedule::command('payroll:mark-overdue')->daily();
