<?php

namespace App\Console\Commands;

use App\Modules\PilaManagement\Models\AffiliateNotes;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCredential;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Models\PortalCredential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ValidatePilaImport extends Command
{
    protected $signature = 'pila:validate-import {path : Ruta absoluta al archivo .xlsx}';

    protected $description = 'Valida totales del Excel PILA contra el estado actual en BD.';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_readable($path)) {
            $this->error('El archivo no existe o no es legible: ' . $path);
            return 1;
        }

        if (! str_ends_with(strtolower($path), '.xlsx')) {
            $this->error('El archivo debe tener extensión .xlsx');
            return 1;
        }

        $realPath = realpath($path) ?: $path;

        $spreadsheet = IOFactory::load($realPath);
        $sheet = $spreadsheet->getSheetByName('DATA ACTUALIZADA 2025') ?? $spreadsheet->getSheet(0);

        $highestRow = (int) $sheet->getHighestRow();

        $affiliateDocs = [];
        $employerDocs = [];

        $pilaCredKeys = [];
        $portalCredKeys = [];

        $paymentStats = [
            'current' => 0,
            'overdue' => 0,
            'anticipated' => 0,
            'unknown' => 0,
        ];

        $notesExpected = 0;

        // Fila 1: encabezados. Datos desde 2.
        for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
            $affiliateDocRaw = $this->cellString($sheet, $rowIdx, 5);
            $fullNameRaw = $this->cellString($sheet, $rowIdx, 6);

            $isEmptyLine = $this->cellString($sheet, $rowIdx, 1) === '' &&
                $this->cellString($sheet, $rowIdx, 2) === '' &&
                $affiliateDocRaw === '' &&
                $fullNameRaw === '';
            if ($isEmptyLine) {
                continue;
            }

            $affiliateDocDigits = $this->onlyDigits($affiliateDocRaw);
            if ($affiliateDocDigits === '') {
                continue;
            }
            $affiliateDocs[$affiliateDocDigits] = true;

            $clientTypeRaw = $this->cellString($sheet, $rowIdx, 2);
            $isIndependent = $this->isIndependent($clientTypeRaw);
            [$affiliateDocBase, ] = $this->splitDocumentNumberForEmployer($affiliateDocRaw);

            // Empleador: documento en col 16, deduplicar por base sin DV.
            $employerDocRaw = $this->cellString($sheet, $rowIdx, 16);
            [$employerBaseFromCol16] = $this->splitDocumentNumberForEmployer($employerDocRaw);
            $employerBase = $isIndependent ? $affiliateDocBase : $employerBaseFromCol16;
            if ($employerBase !== '') $employerDocs[$employerBase] = true;

            // Estado de pago: col 49
            $paymentStatus = $this->paymentStatus($this->cellString($sheet, $rowIdx, 49));
            if ($paymentStatus === null) {
                $paymentStats['unknown']++;
            } else {
                $paymentStats[$paymentStatus]++;
            }

            // Notas: cols 44 y 50
            $noteAff = trim($this->cellString($sheet, $rowIdx, 44));
            $notePay = trim($this->cellString($sheet, $rowIdx, 50));
            if ($noteAff !== '' || $notePay !== '') {
                $notesExpected += ($noteAff !== '' ? 1 : 0) + ($notePay !== '' ? 1 : 0);
            }

            // PILA credenciales: operador col 26, username col 27, password col 28
            $operator = $this->normalizeOperator($this->cellString($sheet, $rowIdx, 26));
            $pilaUser = $this->cellString($sheet, $rowIdx, 27);
            $pilaPass = $this->cellString($sheet, $rowIdx, 28);

            // Import deduplica PILA por (employer_id, pila_operator). Aquí usamos el NIT/CC del empleador (sin DV).
            if (
                $operator &&
                $employerBase !== '' &&
                $pilaUser !== '' &&
                $pilaPass !== '' &&
                ! $this->isNotApplicableValue($pilaUser) &&
                ! $this->isNotApplicableValue($pilaPass)
            ) {
                $pilaCredKeys[$employerBase . '|' . $operator] = true;
            }

            // Portal credenciales: ARL (32-33), CCF (35-36), EPS (38-39), AFP (41-42)
            $this->countPortalKey($portalCredKeys, $affiliateDocDigits, 'ARL', 32, 33, $sheet, $rowIdx);
            $this->countPortalKey($portalCredKeys, $affiliateDocDigits, 'CCF', 35, 36, $sheet, $rowIdx);
            $this->countPortalKey($portalCredKeys, $affiliateDocDigits, 'EPS', 38, 39, $sheet, $rowIdx);
            $this->countPortalKey($portalCredKeys, $affiliateDocDigits, 'AFP', 41, 42, $sheet, $rowIdx);
        }

        $dbAffiliations = PilaAffiliation::query()->count();
        $dbEmployers = PilaEmployer::query()->count();
        $dbPilaCreds = PilaCredential::query()->count();
        $dbPortalCredsConfigured = PortalCredential::query()
            ->where('is_not_applicable', 0)
            ->count();

        $dbNotes = DB::table('affiliate_notes')->count();

        $expectedPilaCreds = count($pilaCredKeys);
        $expectedPortalCreds = count($portalCredKeys);

        $this->info('=== Validación Excel vs BD ===');
        $this->table(
            ['Métrica', 'Excel (estimado)', 'BD (actual)'],
            [
                ['Afiliados (distinct doc)', (string) count($affiliateDocs), (string) $dbAffiliations],
                ['Empleadores (distinct base doc)', (string) count($employerDocs), (string) $dbEmployers],
                ['Credenciales PILA (estimado)', (string) $expectedPilaCreds, (string) $dbPilaCreds],
                ['Credenciales Portales configuradas (estimado)', (string) $expectedPortalCreds, (string) $dbPortalCredsConfigured],
                ['Notas (estimado filas con notas)', (string) $notesExpected, (string) $dbNotes],
            ]
        );

        $this->line('');
        $this->info('Distribución Excel - pago (validas por fila con doc):');
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['current', (string) $paymentStats['current']],
                ['overdue', (string) $paymentStats['overdue']],
                ['anticipated', (string) $paymentStats['anticipated']],
                ['unknown', (string) $paymentStats['unknown']],
            ]
        );

        return 0;
    }

    private function countPortalKey(array &$keys, string $affiliateDocDigits, string $entityType, int $userCol, int $passCol, $sheet, int $rowIdx): void
    {
        $user = $this->cellString($sheet, $rowIdx, $userCol);
        $pass = $this->cellString($sheet, $rowIdx, $passCol);

        if ($user === '' && $pass === '') {
            return;
        }

        $isNotApplicable = $this->isNotApplicableValue($user) || $this->isNotApplicableValue($pass);
        if ($isNotApplicable) {
            return;
        }

        if ($user === '' || $pass === '') {
            return;
        }

        $keys[$affiliateDocDigits . '|' . $entityType] = true;
    }

    private function cellString($sheet, int $rowIdx, int $colIdx): string
    {
        $value = $sheet->getCellByColumnAndRow($colIdx, $rowIdx)->getValue();
        $s = trim((string) $value);

        // Limpieza de NBSP común en Excel.
        $s = str_replace(["\xc2\xa0"], ' ', $s);

        // Marcadores visuales de vacíos.
        if ($s === '-' || $s === '—') {
            return '';
        }

        return $s;
    }

    private function onlyDigits(string $raw): string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        return $digits ?? '';
    }

    private function splitDocumentNumberForEmployer(string $raw): array
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return ['', null];
        }

        if (str_contains($s, '-')) {
            $parts = explode('-', $s, 2);
            $base = preg_replace('/\D/', '', $parts[0] ?? '');
            $dv = preg_replace('/\D/', '', $parts[1] ?? '');
            $dv = $dv !== '' ? substr($dv, -1) : null;
            return [$base, $dv];
        }

        $digitsOnly = preg_replace('/\D/', '', $s);
        return [$digitsOnly, null];
    }

    private function isNotApplicableValue(string $value): bool
    {
        $v = strtoupper(trim($value));
        if ($v === '') return false;
        if (in_array($v, ['N/A', 'NA'], true)) return true;
        if (str_contains($v, 'NO APLICA')) return true;
        if (str_contains($v, 'SIN INFORMACION') || str_contains($v, 'SIN INFORMACIÓN')) return true;
        return false;
    }

    private function normalizeOperator(string $raw): ?string
    {
        $v = strtoupper(trim($raw));
        if ($v === '' || $this->isNotApplicableValue($v)) {
            return null;
        }

        if (str_contains($v, '(')) {
            $v = trim(explode('(', $v, 2)[0]);
        }

        if (str_contains($v, ',')) {
            $v = trim(explode(',', $v, 2)[0]);
        }

        $v = preg_replace('/[^A-Z0-9_-]/', '', trim($v));
        if ($v === '') return null;

        return strtolower($v);
    }

    private function isIndependent(string $clientTypeRaw): bool
    {
        $v = strtoupper(trim($clientTypeRaw));
        return $v === 'INDEPENDIENTE' || $v === 'INDEPENDENT';
    }

    private function paymentStatus(string $raw): ?string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') return null;
        if (str_contains($v, 'ANTIC')) return 'anticipated';
        if ($v === 'SI' || str_contains($v, 'SI')) return 'current';
        if ($v === 'NO' || str_contains($v, 'NO')) return 'overdue';
        return null;
    }
}

