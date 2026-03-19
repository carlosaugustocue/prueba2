<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;
use App\Modules\Appointments\Models\Reminder;
use App\Modules\Appointments\Jobs\SendReminderJob;
use App\Modules\SocialSecurity\Services\PayrollBatchService;
use App\Modules\SocialSecurity\Services\PayrollService;
use App\Modules\SocialSecurity\Services\DueDateCalculator;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\AffiliateTask;
use App\Modules\Affiliates\Enums\DocumentType;
use App\Modules\Affiliates\Enums\PatientType;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\SocialSecurity\Models\Afp;
use App\Modules\SocialSecurity\Models\Arp;
use App\Modules\SocialSecurity\Models\Ccf;
use App\Modules\SocialSecurity\Models\ClientType;
use App\Modules\SocialSecurity\Models\ContributorType;
use App\Modules\SocialSecurity\Models\PaymentOperator;
use App\Modules\SocialSecurity\Models\AccountingRegistry;
use App\Modules\SocialSecurity\Models\Payer;
use App\Modules\SocialSecurity\Models\Payroll;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;

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

// Alertas PILA: 3 días antes del vencimiento (Seguridad Social)
Schedule::command('ss:generate-pila-alerts')->dailyAt('06:00');

// Seguridad Social: generar tareas de alerta por vencimiento PILA (3 días antes)
Artisan::command('ss:generate-pila-alerts', function (DueDateCalculator $calculator) {
    $this->info('Generando alertas de vencimiento PILA (3 días antes)...');

    $today = Carbon::today();
    $created = 0;

    $affiliates = Affiliate::query()
        ->with('socialSecurityProfile')
        ->where('status', 'ACTIVO')
        ->whereHas('socialSecurityProfile', fn ($q) => $q->whereNotNull('payment_day'))
        ->get();

    foreach ($affiliates as $affiliate) {
        $profile = $affiliate->socialSecurityProfile;
        if (! $profile) {
            continue;
        }

        $paymentDay = $profile->payment_day;
        if ($paymentDay === null && $affiliate->document_number) {
            $paymentDay = $calculator->paymentDayFromDocument($affiliate->document_number);
        }
        if ($paymentDay === null) {
            continue;
        }

        $now = Carbon::today();
        // Período base: mes actual; si el vencimiento ya pasó, usamos el siguiente mes
        $periodYear = $now->year;
        $periodMonth = $now->month;
        $nextDue = $calculator->dueDateForPeriodByPaymentDay($periodYear, $periodMonth, (int) $paymentDay);
        if ($nextDue->isPast()) {
            $next = $now->copy()->addMonth();
            $periodYear = $next->year;
            $periodMonth = $next->month;
            $nextDue = $calculator->dueDateForPeriodByPaymentDay($periodYear, $periodMonth, (int) $paymentDay);
        }

        $diff = $now->diffInDays($nextDue, false);
        if ($diff < 0 || $diff > 3) {
            continue;
        }

        $periodLabel = sprintf('%02d/%d', $periodMonth, $periodYear);
        $dueLabel = $nextDue->format('d/m/Y');

        $alreadyExists = AffiliateTask::query()
            ->where('affiliate_id', $affiliate->id)
            ->where('area', AffiliateTask::AREA_SEGURIDAD_SOCIAL)
            ->where('is_completed', false)
            ->whereDate('created_at', '>=', $today->copy()->subDays(10))
            ->where('description', 'like', "%PILA período {$periodLabel}%")
            ->exists();

        if ($alreadyExists) {
            continue;
        }

        AffiliateTask::create([
            'affiliate_id' => $affiliate->id,
            'area' => AffiliateTask::AREA_SEGURIDAD_SOCIAL,
            'description' => "Vencimiento PILA período {$periodLabel} el {$dueLabel}. Revisar y garantizar pago a tiempo.",
            'is_completed' => false,
        ]);

        $created++;
    }

    $this->info("Alertas generadas: {$created}");
})->purpose('Generar tareas de alerta para vencimientos PILA (3 días antes) para Seguridad Social');

// Importar afiliados y perfil de seguridad social desde DataSegura (hoja DATA ACTUALIZADA 2025 exportada a CSV)
Artisan::command('ss:import-affiliates-from-datasegura {path?}', function (?string $path = null) {
    $relativePath = $path ?: 'docs/DataSegura SERVICONLI 2025 - DATA ACTUALIZADA 2025.csv';
    $fullPath = base_path($relativePath);

    if (! is_file($fullPath)) {
        $this->error("No se encontró el archivo CSV en: {$fullPath}");
        return 1;
    }

    $this->info("Importando afiliados desde: {$fullPath}");

    $handle = fopen($fullPath, 'r');
    if ($handle === false) {
        $this->error('No se pudo abrir el archivo para lectura.');
        return 1;
    }

    // Leer cabecera y construir índice por nombre de columna (trim)
    $header = fgetcsv($handle, 0, ',');
    if (! $header) {
        fclose($handle);
        $this->error('El archivo CSV está vacío o no tiene cabecera.');
        return 1;
    }
    $header = array_map(static fn ($h) => trim((string) $h), $header);
    $indexes = [];
    foreach ($header as $i => $name) {
        if ($name !== '') {
            $indexes[$name] = $i;
        }
    }

    // Helper para obtener índice por nombre de columna
    $idx = static function (string $name) use ($indexes): ?int {
        return $indexes[$name] ?? null;
    };

    $colStatus = 0; // primera columna (sin nombre en cabecera)
    $colClientType = $idx('TIPO DE CLIENTE');
    $colContributorType = $idx('Tipo de Cotizante');
    $colDocType = $idx('TIPO DE DOCUMENTO AFILIADO');
    $colDocNumber = $idx('# DOCUMENTO AFILIADO');
    $colFullName = $idx('Nombre del Afiliado');
    $colGender = $idx('Sexo');
    $colBirthDate = $idx('Fecha de nacimiento');
    $colAddr = $idx('AFILIADO_DIRECCION');
    $colCity = $idx('AFILIADO_CIUDAD');
    $colDept = $idx('AFILIADO_DEPARTAMENTO');
    $colPhone = $idx('AFILIADO_TELEFONO');
    $colEmail = $idx('AFILIADO_CORREO ELECTRONICO');

    $colPayerName = $idx('PAGADOR');
    $colPayerDocType = $idx('TIPO DE DOCUMENTO');
    $colPayerDocNumber = $idx('# DOCUMENTO');
    $colPayerAddr = $idx('CLIENTE_DIRECCION');
    $colPayerCity = $idx('CLIENTE_CIUDAD');
    $colPayerDept = $idx('CLIENTE_DEPARTAMENTO');
    $colPayerPhone = $idx('CLIENTE_TELEFONO');
    $colPayerEmail = $idx('CORREO ELECTRONICO');

    $colPaymentDay = $idx('DIA HABIL');
    $colOperator = $idx('OPERADOR');
    $colSalary = $idx('SALARIO');
    $colArlName = $idx('NombreARL');
    $colArlRisk = $idx('Clase de Riesgo ARL');
    $colCcfName = $idx('NombreCCF');
    $colEpsName = $idx('Nombre EPS');
    $colAfpName = $idx('Nombre AFP');
    $colParafiscales = $idx('PARAFISCALES');
    $colSsObservations = $idx('OBSERVACIONES AFILIACIÓN');
    $colPaymentPeriodicity = $idx('PERIOCIDAD DE PAGO');
    $colAccountingRegistry = $idx('REG/CONTABLE');

    $colNovelty = $idx('NOVEDAD');
    $colNoveltyDate = $idx('FECHA NOVEDAD');

    if ($colClientType === null || $colDocType === null || $colDocNumber === null || $colFullName === null) {
        fclose($handle);
        $this->error('La cabecera del CSV no coincide con la esperada (faltan columnas clave).');
        return 1;
    }

    // Cache de catálogos para no pegar la BD en cada fila
    $clientTypesByName = ClientType::all()->keyBy(fn ($ct) => strtoupper(trim($ct->name)));
    $contributorTypesByCode = ContributorType::all()->keyBy(fn ($ct) => trim($ct->code));
    $epsByCode = Eps::all()->keyBy(fn ($e) => strtoupper(trim($e->code)));
    $afpsByCode = Afp::all()->keyBy(fn ($m) => strtoupper(trim($m->code)));
    $arpsByCode = Arp::all()->keyBy(fn ($m) => strtoupper(trim($m->code)));
    $ccfsByCode = Ccf::all()->keyBy(fn ($m) => strtoupper(trim($m->code)));
    $paymentOperatorsByName = PaymentOperator::all()->keyBy(fn ($op) => strtoupper(trim($op->name)));
    $accountingByCode = AccountingRegistry::all()->keyBy(fn ($ar) => strtoupper(trim($ar->code)));

    // Helpers
    $mapDocumentType = static function (?string $raw): ?DocumentType {
        $val = strtoupper(trim((string) $raw));
        return match ($val) {
            'CC' => DocumentType::CC,
            'TI' => DocumentType::TI,
            'CE' => DocumentType::CE,
            'PPT' => DocumentType::PPT,
            'PTT' => DocumentType::PTT,
            default => null,
        };
    };

    $splitName = static function (string $fullName): array {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        $count = count($parts);
        if ($count === 0) {
            return [null, null, null, null];
        }
        if ($count === 1) {
            return [$parts[0], null, null, null];
        }
        if ($count === 2) {
            return [$parts[0], null, $parts[1], null];
        }
        if ($count === 3) {
            return [$parts[0], null, $parts[1], $parts[2]];
        }
        // 4 o más: primer y segundo como nombres; penúltimo y último como apellidos
        return [
            $parts[0],
            $parts[1],
            $parts[$count - 2],
            $parts[$count - 1],
        ];
    };

    $extractCodeFromValue = static function (?string $value): ?string {
        $v = trim((string) $value);
        if ($v === '' || stripos($v, 'NO APLICA') !== false || strtoupper($v) === 'N/A') {
            return null;
        }
        // Ejemplos: "EPS005 -EPS SANITAS", "25-14 -COLPENSIONES", "CCF43 -COMFENALCO QUINDIO"
        if (preg_match('/^([A-Z0-9\-]+)\s*[-\s]/u', $v, $m)) {
            return strtoupper(trim($m[1]));
        }
        return strtoupper($v);
    };

    $normalizePaymentOperator = static function (?string $value): ?string {
        $v = strtoupper(trim((string) $value));
        if ($v === '' || str_starts_with($v, 'NA ') || $v === 'NA' || $v === 'N/A') {
            return null;
        }
        return match (true) {
            str_contains($v, 'ENLACE') => 'ENLACE OPERATIVO',
            $v === 'SIMPLE' => 'SIMPLE',
            str_contains($v, 'ASOPAGOS') => 'ASOPAGOS',
            str_contains($v, 'APORTES') => 'APORTES EN LINEA',
            $v === 'SOI' => 'SOI',
            str_contains($v, 'MI PLANILLA') => 'MI PLANILLA',
            default => $v,
        };
    };

    $mapAccountingRegistryCode = static function (?string $value): ?string {
        $v = strtoupper(trim((string) $value));
        return match ($v) {
            'RECIBO DE CAJA' => 'RECIBO_CAJA',
            'FACTURA ELECTRÓNICA', 'FACTURA ELECTRONICA' => 'FACTURA_ELECTRONICA',
            default => null,
        };
    };

    $parseMoney = static function (?string $value): ?float {
        $digits = preg_replace('/[^\d]/', '', (string) $value);
        if ($digits === '' || $digits === null) {
            return null;
        }
        return (float) $digits;
    };

    $normalizeDocumentNumber = static function (?string $value): ?string {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        // Tomar solo la primera "palabra" antes de espacios o comas (evitar textos largos tipo "CIF7622 PLAN PREMIUM ABRIL1 ...")
        if (preg_match('/^([^\s,]+)/u', $v, $m)) {
            $v = $m[1];
        }
        // Truncar a 20 caracteres para ajustarse al schema (document_number varchar(20))
        if (strlen($v) > 20) {
            $v = substr($v, 0, 20);
        }
        return $v;
    };

    $rowNumber = 1; // cabecera
    $createdAffiliates = 0;
    $updatedAffiliates = 0;
    $createdProfiles = 0;
    $updatedProfiles = 0;
    $createdPayers = 0;
    $updatedPayers = 0;
    $skipped = [];

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $rowNumber++;
        $rawDocNumber = $normalizeDocumentNumber($row[$colDocNumber] ?? null);
        if (! $rawDocNumber) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'Sin número de documento de afiliado'];
            continue;
        }

        $status = strtoupper(trim((string) ($row[$colStatus] ?? 'ACTIVO')));
        $docTypeEnum = $mapDocumentType($row[$colDocType] ?? null);
        if (! $docTypeEnum) {
            $skipped[] = ['row' => $rowNumber, 'reason' => 'Tipo de documento no soportado', 'value' => $row[$colDocType] ?? null];
            continue;
        }
        $docNumber = $rawDocNumber;
        $fullName = trim((string) ($row[$colFullName] ?? ''));
        [$firstName, $secondName, $lastName, $secondLastName] = $splitName($fullName);

        $genderRaw = strtoupper(trim((string) ($row[$colGender] ?? '')));
        $gender = in_array($genderRaw, ['M', 'F'], true) ? $genderRaw : null;

        $birthDate = null;
        if (! empty($row[$colBirthDate])) {
            $val = trim((string) $row[$colBirthDate]);
            try {
                $birthDate = Carbon::createFromFormat('d/m/Y', $val);
            } catch (\Throwable $e) {
                $birthDate = null;
            }
        }

        $address    = substr(trim((string) ($row[$colAddr]  ?? '')), 0, 255);
        $city       = substr(trim((string) ($row[$colCity]  ?? '')), 0, 100);
        $department = substr(trim((string) ($row[$colDept]  ?? '')), 0, 100);
        // Limpiar teléfono: quitar todo lo que no sea dígitos, +, - o espacios (truncar a 20 chars)
        $rawPhone   = preg_replace('/[^\d\+\-\s].*/', '', trim((string) ($row[$colPhone] ?? '')));
        $phone      = substr(trim($rawPhone), 0, 20);
        $email      = substr(trim((string) ($row[$colEmail] ?? '')), 0, 100);

        // Buscar o crear afiliado (document_number es UNIQUE; ignoramos tipo para no duplicar)
        $affiliate = Affiliate::where('document_number', $docNumber)->first();

        $affiliateData = [
            'document_type' => $docTypeEnum,
            'document_number' => $docNumber,
            'first_name' => $firstName,
            'second_name' => $secondName,
            'last_name' => $lastName,
            'second_last_name' => $secondLastName,
            'gender' => $gender,
            'birth_date' => $birthDate,
            'address' => $address,
            'city' => $city,
            'department' => $department,
            'phone' => $phone,
            'whatsapp' => $phone,
            'email' => $email,
            'status' => $status,
            'patient_type' => PatientType::COTIZANTE,
        ];

        if ($affiliate) {
            $affiliate->fill($affiliateData);
            $affiliate->save();
            $updatedAffiliates++;
        } else {
            $affiliate = Affiliate::create($affiliateData);
            $createdAffiliates++;
        }

        // Pagador
        $payerId = null;
        if ($colPayerDocNumber !== null && ! empty($row[$colPayerDocNumber])) {
            $payerDocTypeEnum = $mapDocumentType($row[$colPayerDocType] ?? null) ?? DocumentType::NIT;
            $payerDocNumber = $normalizeDocumentNumber($row[$colPayerDocNumber] ?? null);
            if (! $payerDocNumber) {
                // Si no podemos normalizar el documento del pagador, seguimos sin crear/actualizar payer
                $skipped[] = ['row' => $rowNumber, 'reason' => 'Documento de pagador no válido', 'value' => $row[$colPayerDocNumber] ?? null];
                goto after_payer;
            }
            $payerName = trim((string) ($row[$colPayerName] ?? ''));
            $payerAddress   = substr(trim((string) ($row[$colPayerAddr]  ?? '')), 0, 255);
            $rawPayerPhone  = preg_replace('/[^\d\+\-\s].*/', '', trim((string) ($row[$colPayerPhone] ?? '')));
            $payerPhone     = substr(trim($rawPayerPhone), 0, 20);
            $payerEmail     = substr(trim((string) ($row[$colPayerEmail] ?? '')), 0, 100);

            // document_number es UNIQUE en payers; buscamos solo por número para evitar duplicados
            $payer = Payer::where('document_number', $payerDocNumber)->first();

            $payerData = [
                'name' => $payerName ?: $payerDocNumber,
                'document_type' => $payerDocTypeEnum,
                'document_number' => $payerDocNumber,
                'address' => $payerAddress,
                'phone' => $payerPhone,
                'email' => $payerEmail,
                'is_active' => true,
            ];

            if ($payer) {
                $payer->fill($payerData);
                $payer->save();
                $updatedPayers++;
            } else {
                $payer = Payer::create($payerData);
                $createdPayers++;
            }

            $payerId = $payer->id;
        }

        after_payer:

        // Perfil de seguridad social
        $clientTypeRaw = strtoupper(trim((string) ($row[$colClientType] ?? '')));
        $clientType = $clientTypesByName[$clientTypeRaw] ?? null;

        $contributorCode = null;
        if ($colContributorType !== null && ! empty($row[$colContributorType])) {
            if (preg_match('/^(\d{1,2})/', (string) $row[$colContributorType], $m)) {
                $contributorCode = $m[1];
            }
        }
        $contributorType = $contributorCode ? ($contributorTypesByCode[$contributorCode] ?? null) : null;

        $ibc = $colSalary !== null ? $parseMoney($row[$colSalary] ?? null) : null;

        $epsId = null;
        if ($colEpsName !== null && ! empty($row[$colEpsName])) {
            $epsCode = $extractCodeFromValue($row[$colEpsName]);
            if ($epsCode && isset($epsByCode[$epsCode])) {
                $epsId = $epsByCode[$epsCode]->id;
            }
        }

        $afpId = null;
        if ($colAfpName !== null && ! empty($row[$colAfpName])) {
            $afpCode = $extractCodeFromValue($row[$colAfpName]);
            if ($afpCode && isset($afpsByCode[$afpCode])) {
                $afpId = $afpsByCode[$afpCode]->id;
            }
        }

        $arpId = null;
        if ($colArlName !== null && ! empty($row[$colArlName])) {
            $arpCode = $extractCodeFromValue($row[$colArlName]);
            if ($arpCode && isset($arpsByCode[$arpCode])) {
                $arpId = $arpsByCode[$arpCode]->id;
            }
        }

        $arpRisk = null;
        if ($colArlRisk !== null && ! empty($row[$colArlRisk])) {
            if (preg_match('/^(\d)/', (string) $row[$colArlRisk], $m)) {
                $arpRisk = $m[1];
            }
        }

        $ccfId = null;
        if ($colCcfName !== null && ! empty($row[$colCcfName])) {
            $ccfCode = $extractCodeFromValue($row[$colCcfName]);
            if ($ccfCode && isset($ccfsByCode[$ccfCode])) {
                $ccfId = $ccfsByCode[$ccfCode]->id;
            }
        }

        $paymentOperatorId = null;
        if ($colOperator !== null && ! empty($row[$colOperator])) {
            $opName = $normalizePaymentOperator($row[$colOperator]);
            if ($opName && isset($paymentOperatorsByName[$opName])) {
                $paymentOperatorId = $paymentOperatorsByName[$opName]->id;
            }
        }

        $paymentDay = null;
        if ($colPaymentDay !== null && $row[$colPaymentDay] !== null && $row[$colPaymentDay] !== '') {
            $paymentDay = (int) round((float) $row[$colPaymentDay]);
            if ($paymentDay < 2 || $paymentDay > 16) {
                $paymentDay = null;
            }
        }

        // Por defecto asumimos que NO tiene parafiscales (coincide con default de la BD).
        $hasParafiscales = false;
        if ($colParafiscales !== null && $row[$colParafiscales] !== null && $row[$colParafiscales] !== '') {
            $val = strtoupper(trim((string) $row[$colParafiscales]));
            $hasParafiscales = $val === 'SI';
        }

        $paymentPeriodicity = null;
        if ($colPaymentPeriodicity !== null && $row[$colPaymentPeriodicity] !== null && $row[$colPaymentPeriodicity] !== '') {
            $val = strtoupper(trim((string) $row[$colPaymentPeriodicity]));
            $paymentPeriodicity = match ($val) {
                'VENCIDO' => 'OVERDUE',
                'ACTUAL' => 'CURRENT',
                default => null,
            };
        }

        $accountingRegistryId = null;
        if ($colAccountingRegistry !== null && ! empty($row[$colAccountingRegistry])) {
            $code = $mapAccountingRegistryCode($row[$colAccountingRegistry]);
            if ($code && isset($accountingByCode[$code])) {
                $accountingRegistryId = $accountingByCode[$code]->id;
            }
        }

        $observations = trim((string) ($row[$colSsObservations] ?? ''));
        if ($colNovelty !== null && ! empty($row[$colNovelty])) {
            $nov = trim((string) $row[$colNovelty]);
            $novDate = $colNoveltyDate !== null ? trim((string) ($row[$colNoveltyDate] ?? '')) : '';
            $extra = "NOVEDAD: {$nov}" . ($novDate ? " ({$novDate})" : '');
            $observations = $observations ? ($observations . ' | ' . $extra) : $extra;
        }

        $profile = $affiliate->socialSecurityProfile;
        $profileData = [
            'client_type_id' => $clientType?->id,
            'contributor_type_id' => $contributorType?->id,
            'ibc' => $ibc,
            'eps_id' => $epsId,
            'afp_id' => $afpId,
            'arp_id' => $arpId,
            'arp_risk_class' => $arpRisk,
            'ccf_id' => $ccfId,
            'payer_id' => $payerId,
            'payment_operator_id' => $paymentOperatorId,
            'payment_day' => $paymentDay,
            'payment_periodicity' => $paymentPeriodicity,
            'has_parafiscales' => $hasParafiscales,
            'accounting_registry_id' => $accountingRegistryId,
            'observations' => $observations !== '' ? $observations : null,
        ];

        if ($profile) {
            $profile->fill($profileData);
            $profile->save();
            $updatedProfiles++;
        } else {
            $profile = new SocialSecurityProfile(array_merge(
                ['affiliate_id' => $affiliate->id],
                $profileData
            ));
            $profile->save();
            $createdProfiles++;
        }
    }

    fclose($handle);

    $this->info('Importación completada.');
    $this->line("Afiliados creados: {$createdAffiliates}, actualizados: {$updatedAffiliates}");
    $this->line("Perfiles SS creados: {$createdProfiles}, actualizados: {$updatedProfiles}");
    $this->line("Pagadores creados: {$createdPayers}, actualizados: {$updatedPayers}");
    if (count($skipped) > 0) {
        $this->warn('Filas omitidas: ' . count($skipped));
        foreach (array_slice($skipped, 0, 10) as $item) {
            $msg = '  Fila ' . $item['row'] . ' — ' . $item['reason'];
            if (isset($item['value'])) {
                $msg .= ' [' . $item['value'] . ']';
            }
            $this->line($msg);
        }
        if (count($skipped) > 10) {
            $this->line('  ... y ' . (count($skipped) - 10) . ' más.');
        }
    }

    return 0;
})->purpose('Importa afiliados y perfil de seguridad social desde el CSV exportado de DataSegura (DATA ACTUALIZADA 2025)');
