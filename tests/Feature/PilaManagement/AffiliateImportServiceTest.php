<?php

namespace Tests\Feature\PilaManagement;

use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCredential;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Models\PortalCredential;
use App\Modules\PilaManagement\Services\AffiliateImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AffiliateImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_deduplicates_employers_and_encrypts_credentials(): void
    {
        $path = $this->buildExcelPath();
        $pilaSecret = Str::random(24);
        $arlSecret = Str::random(24);
        $ccfSecret = Str::random(24);
        $epsSecret = Str::random(24);
        $afpSecret = Str::random(24);

        $this->createTestExcel($path, [
            // Valid rows (2 and 3) -> same employer doc + same operator -> 1 PILA credential.
            $this->excelRow(
                row: 2,
                affiliateDoc: '80002098',
                fullName: 'JUAN PEREZ',
                employerDoc: '900123456-7',
                pilaUsername: 'user-pila',
                pilaSecret: $pilaSecret,
                arlSecret: $arlSecret,
                ccfSecret: $ccfSecret,
                epsSecret: $epsSecret,
                afpSecret: $afpSecret,
            ),
            $this->excelRow(
                row: 3,
                affiliateDoc: '80002099',
                fullName: 'MARIA LOPEZ',
                employerDoc: '900123456-7',
                pilaUsername: 'user-pila',
                pilaSecret: $pilaSecret,
                arlSecret: $arlSecret,
                ccfSecret: $ccfSecret,
                epsSecret: $epsSecret,
                afpSecret: $afpSecret,
            ),
            // Invalid row: document_number has no digits -> parseRows should skip and add error.
            $this->excelRow(
                row: 4,
                affiliateDoc: 'ABRIL',
                fullName: 'PERSONA SIN DIGITOS',
                employerDoc: '900123456-7',
                pilaUsername: 'user-pila',
                pilaSecret: $pilaSecret,
                arlSecret: $arlSecret,
                ccfSecret: $ccfSecret,
                epsSecret: $epsSecret,
                afpSecret: $afpSecret,
            ),
        ]);

        $service = app(AffiliateImportService::class);
        $summary = $service->import($path, ['dry_run' => false]);

        // Invalid row should not prevent the import of valid ones.
        $this->assertNotEmpty($summary['errors']);
        // The two valid rows share the same employer base -> should be 1.
        $this->assertSame(1, PilaEmployer::query()->count());

        $this->assertSame(2, PilaAffiliation::query()->count());

        // PILA credentials upserted by (employer_id, pila_operator) => 1 record.
        $this->assertSame(1, PilaCredential::query()->count());

        $pilaCred = PilaCredential::query()->firstOrFail();
        $this->assertNotNull($pilaCred->password_encrypted);
        $this->assertSame($pilaSecret, Crypt::decryptString($pilaCred->password_encrypted));

        // Portal credentials: 2 affiliations x (ARL/CCF/EPS/AFP) => 8 records (configured).
        $this->assertSame(8, PortalCredential::query()->count());
        $this->assertSame(
            8,
            PortalCredential::query()->where('is_not_applicable', 0)->count(),
        );

        $epsCred = PortalCredential::query()->where('entity_type', 'EPS')->first();
        $this->assertNotNull($epsCred);
        $this->assertSame($epsSecret, Crypt::decryptString($epsCred->password_encrypted));

        // Notes: 2 affiliations x 2 note types (affiliation + payment).
        $this->assertSame(4, \DB::table('affiliate_notes')->count());

        // Sanity: the failure reason should include "sin dígitos".
        $hasDigitsError = false;
        foreach ($summary['errors'] as $err) {
            if (isset($err[3]) && is_string($err[3]) && str_contains($err[3], 'sin dígitos')) {
                $hasDigitsError = true;
                break;
            }
        }
        $this->assertTrue($hasDigitsError);
    }

    private function excelRow(
        int $row,
        string $affiliateDoc,
        string $fullName,
        string $employerDoc,
        string $pilaUsername,
        string $pilaSecret,
        string $arlSecret,
        string $ccfSecret,
        string $epsSecret,
        string $afpSecret,
    ): array {
        // Columns used by AffiliateImportService::parseRows()
        // 1 status, 2 client_type, 3 cotizante, 4 doc_type, 5 doc_number, 6 full_name
        // 7 gender, 8 birth_date, 9 address, 10 city, 11 department, 12 phone, 13 email
        // 14 employer name, 15 employer doc_type, 16 employer doc_number_raw, 22 payment_business_day
        // 24 last_novelty_type, 25 last_novelty_date, 26 pila_operator
        // 27 pila username, 28 pila password
        // 29 ibc, 30 arl_code, 31 risk_class_level
        // 32 arl user, 33 arl pass
        // 34 ccf_code, 35 ccf user, 36 ccf pass
        // 37 eps_code, 38 eps user, 39 eps pass
        // 40 afp_code, 41 afp user, 42 afp pass
        // 43 pays_parafiscales, 44 notes affiliation, 45 payment_periodicity, 46 billing_type
        // 47 last_document_number, 48 last_payment_period, 49 payment_status
        // 50 notes payment

        return [
            $row => [
                1 => 'ACTIVO',
                2 => 'DEPENDIENTE',
                3 => '03 - Independiente',
                4 => 'CC',
                5 => $affiliateDoc,
                6 => $fullName,
                7 => 'M',
                8 => '01/01/2000',
                9 => 'Calle 1',
                10 => 'Medellin',
                11 => 'Antioquia',
                12 => '3001234567',
                13 => 'test@example.com',
                14 => 'EMPRESA ACME',
                15 => 'NIT',
                16 => $employerDoc,
                17 => 'Calle Empleador 1',
                18 => 'Bogota',
                19 => 'Cundinamarca',
                20 => '3201234567',
                21 => 'empleador@example.com',
                22 => 5,
                24 => 'ING',
                25 => '01/01/2024',
                26 => 'ARUS',
                27 => $pilaUsername,
                28 => $pilaSecret,
                29 => '$1.000.000',
                30 => '14-23',
                31 => 'I',
                32 => 'user-arl',
                33 => $arlSecret,
                34 => 'CCF43',
                35 => 'user-ccf',
                36 => $ccfSecret,
                37 => 'EPS005 - SANITAS',
                38 => 'user-eps',
                39 => $epsSecret,
                40 => '230301 - PORVENIR',
                41 => 'user-afp',
                42 => $afpSecret,
                43 => 'SI',
                44 => 'NOTA IMPORTANTE',
                45 => 'ACTUAL',
                46 => 'RECIBO_CAJA',
                47 => 'DOC-123',
                48 => 'SEPTIEMBRE',
                49 => 'SI',
                50 => 'NOTA PAGO',
            ],
        ];
    }

    private function buildExcelPath(): string
    {
        $dir = storage_path('app/testing');
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir . DIRECTORY_SEPARATOR . 'pila-import-test-2025-' . Str::uuid() . '.xlsx';
    }

    private function createTestExcel(string $path, array $rowPayloads): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DATA ACTUALIZADA 2025');

        // Build header row (optional) + data rows.
        foreach ($rowPayloads as $payload) {
            foreach ($payload as $rowIdx => $cells) {
                foreach ($cells as $colIdx => $value) {
                    $sheet->setCellValueByColumnAndRow((int) $colIdx, (int) $rowIdx, $value);
                }
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }
}

