<?php

namespace App\Console\Commands;

use App\Modules\PilaManagement\Services\AffiliateImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportPilaExcel extends Command
{
    protected $signature = 'pila:import-excel {path : Ruta absoluta al archivo .xlsx} {--dry-run : Simular sin guardar en BD} {--skip-credentials : Omitir importación de contraseñas}';

    protected $description = 'Importa masivamente afiliados, afiliaciones y credenciales desde el Excel PILA (Serviconli).';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_readable($path)) {
            $this->error('El archivo no existe o no es legible: ' . $path);
            return 1;
        }

        if (! Str::endsWith(strtolower($path), '.xlsx')) {
            $this->error('El archivo debe tener extensión .xlsx');
            return 1;
        }

        $realPath = realpath($path) ?: $path;
        $count = $this->countDataRows($realPath);
        $fileName = basename($realPath);

        if (! $this->confirm("¿Importar {$count} registros desde {$fileName}?")) {
            $this->info('Importación cancelada.');
            return 0;
        }

        $dryRun = (bool) $this->option('dry-run');
        $skipCredentials = (bool) $this->option('skip-credentials');

        $bar = null;
        $currentPhase = null;

        $onProgress = function (string $phase, int $processed, int $total) use (&$bar, &$currentPhase) {
            if ($currentPhase !== $phase) {
                if ($bar) {
                    $bar->finish();
                    $bar = null;
                    $currentPhase = null;
                }

                $currentPhase = $phase;
                $max = max(1, $total);
                $bar = new ProgressBar($this->output, $max);
                $bar->setMessage($phase . ' ');
                $bar->start();
            }

            if ($bar) {
                $bar->setProgress(min($processed, $bar->getMaxSteps()));
            }
        };

        $this->line('Iniciando importación...');

        $result = app(AffiliateImportService::class)->import($realPath, [
            'dry_run' => $dryRun,
            'skip_credentials' => $skipCredentials,
            'progress' => $onProgress,
        ]);

        if ($bar) {
            $bar->finish();
            $this->line('');
        }

        $this->info('Importación finalizada.');

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['employers_created', (string) $result['employers_created']],
                ['employers_updated', (string) $result['employers_updated']],
                ['affiliates_created', (string) $result['affiliates_created']],
                ['affiliates_updated', (string) $result['affiliates_updated']],
                ['credentials_encrypted', (string) $result['credentials_encrypted']],
                ['notes_created', (string) $result['notes_created']],
            ]
        );

        $errorsCount = is_array($result['errors'] ?? null) ? count($result['errors']) : 0;
        if ($errorsCount > 0) {
            $timestamp = now()->format('Ymd_His');
            $logPath = storage_path("logs/pila-import-{$timestamp}.json");
            @file_put_contents($logPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->warn("Se encontraron {$errorsCount} errores. Log: {$logPath}");
        }

        return 0;
    }

    private function countDataRows(string $path): int
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable) {
            return 0;
        }

        $sheet = $spreadsheet->getSheetByName('DATA ACTUALIZADA 2025') ?? $spreadsheet->getSheet(0);
        $highestRow = (int) $sheet->getHighestRow();

        $count = 0;
        for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
            $docNumber = (string) $sheet->getCellByColumnAndRow(5, $rowIdx)->getValue();
            $fullName = (string) $sheet->getCellByColumnAndRow(6, $rowIdx)->getValue();

            $docNumber = trim(str_replace(["\xc2\xa0"], ' ', $docNumber));
            $fullName = trim(str_replace(["\xc2\xa0"], ' ', $fullName));

            if ($docNumber === '' && $fullName === '') {
                continue;
            }

            if ($docNumber === '' || $fullName === '') {
                continue;
            }

            $count++;
        }

        return $count;
    }
}

