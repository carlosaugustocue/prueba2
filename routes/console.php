<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Modules\Appointments\Models\Reminder;
use App\Modules\Appointments\Jobs\SendReminderJob;

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
