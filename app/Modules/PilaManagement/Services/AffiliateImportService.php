<?php

namespace App\Modules\PilaManagement\Services;

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Enums\AffiliateStatus;
use App\Modules\Affiliates\Enums\DocumentType;
use App\Modules\Affiliates\Enums\PatientType;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\PilaManagement\Models\PilaCotizanteType;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCredential;
use App\Modules\PilaManagement\Models\PilaRiskClass;
use App\Modules\PilaManagement\Models\PortalCredential;
use App\Modules\PilaManagement\Enums\CredentialAction;
use App\Modules\PilaManagement\Enums\CredentialKind;
use App\Modules\PilaManagement\Services\PilaAffiliationSyncService;
use App\Modules\SocialSecurity\Models\Afp;
use App\Modules\SocialSecurity\Models\Arp;
use App\Modules\SocialSecurity\Models\Ccf;
use App\Modules\SocialSecurity\Models\ClientType;
use App\Modules\SocialSecurity\Models\ContributorType;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AffiliateImportService
{
    private const DEFAULT_DATA_SHEET_NAME = 'DATA ACTUALIZADA 2025';

    public function import(string $path, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $skipCredentials = (bool) ($options['skip_credentials'] ?? false);
        $onProgress = is_callable($options['progress'] ?? null) ? $options['progress'] : null;

        $summary = [
            'employers_created' => 0,
            'employers_updated' => 0,
            'affiliates_created' => 0,
            'affiliates_updated' => 0,
            'credentials_encrypted' => 0,
            'notes_created' => 0,
            'errors' => [],
        ];

        if (! $dryRun) {
            $this->runImport($path, [
                'skip_credentials' => $skipCredentials,
                'on_progress' => $onProgress,
                'summary' => &$summary,
            ]);

            return $summary;
        }

        // En modo dry-run simulamos toda la importación dentro de una transacción
        // y luego hacemos rollback para no persistir nada.
        try {
            DB::transaction(function () use ($path, $skipCredentials, $onProgress, &$summary) {
                $this->runImport($path, [
                    'skip_credentials' => $skipCredentials,
                    'on_progress' => $onProgress,
                    'summary' => &$summary,
                    'dry_run' => true,
                ]);

                throw new DryRunRollback();
            });
        } catch (DryRunRollback) {
            // Intencional: rollback controlado.
        }

        return $summary;
    }

    private function runImport(string $path, array $ctx): void
    {
        $skipCredentials = (bool) ($ctx['skip_credentials'] ?? false);
        $onProgress = $ctx['on_progress'] ?? null;
        $summary = &$ctx['summary'];
        $dryRun = (bool) ($ctx['dry_run'] ?? false);

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName(self::DEFAULT_DATA_SHEET_NAME) ?? $spreadsheet->getSheet(0);

        $rows = $this->parseRows($sheet, $path, $summary);

        $this->phase1Catalogs($rows, [
            'dry_run' => $dryRun,
            'summary' => &$summary,
        ]);

        // ── Fase 2: Empleadores ────────────────────────────────────────────────
        $employersByKey = $this->phase2Employers($rows, [
            'on_progress' => $onProgress,
            'summary' => &$summary,
        ]);

        // ── Fase 3: Afiliados ───────────────────────────────────────────────────
        $affiliatesByDocKey = $this->phase3Affiliates($rows, $employersByKey, [
            'on_progress' => $onProgress,
            'summary' => &$summary,
        ]);

        // ── Fase 4: Afiliaciones ───────────────────────────────────────────────
        $affiliationIdsByAffiliateDocKey = $this->phase4Affiliations($rows, $affiliatesByDocKey, [
            'on_progress' => $onProgress,
            'summary' => &$summary,
        ]);

        // ── Fase 5: Credenciales PILA ─────────────────────────────────────────
        if (! $skipCredentials) {
            $this->phase5PilaCredentials($rows, $affiliationIdsByAffiliateDocKey, [
                'on_progress' => $onProgress,
                'summary' => &$summary,
            ]);
        }

        // ── Fase 6: Credenciales portales ─────────────────────────────────────
        if (! $skipCredentials) {
            $this->phase6PortalCredentials($rows, $affiliationIdsByAffiliateDocKey, [
                'on_progress' => $onProgress,
                'summary' => &$summary,
            ]);
        }

        // ── Fase 7: Observaciones ─────────────────────────────────────────────
        $this->phase7Notes($rows, $affiliatesByDocKey, [
            'on_progress' => $onProgress,
            'summary' => &$summary,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>> parsed rows
     */
    private function parseRows($sheet, string $path, array &$summary): array
    {
        $rows = [];

        $highestRow = (int) $sheet->getHighestRow();
        $baseYear = $this->inferYearFromPath($path);

        // Se asume: fila 1 = encabezados.
        for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
            $affiliateStatusRaw = $this->cellString($sheet, $rowIdx, 1);
            $clientTypeRaw = $this->cellString($sheet, $rowIdx, 2);
            $affiliateDocumentTypeRaw = $this->cellString($sheet, $rowIdx, 4);
            $affiliateDocumentNumberRaw = $this->cellString($sheet, $rowIdx, 5);
            $fullNameRaw = $this->cellString($sheet, $rowIdx, 6);

            $isEmptyLine = $affiliateStatusRaw === '' && $clientTypeRaw === '' && $affiliateDocumentNumberRaw === '' && $fullNameRaw === '';
            if ($isEmptyLine) {
                continue;
            }

            // Si el archivo tiene "huecos" al final, cortamos.
            if ($affiliateDocumentNumberRaw === '' && $fullNameRaw === '' && $rowIdx > 2) {
                $futureEmptyCount = 0;
                // No buscamos mucho: evita bucles.
                for ($look = 0; $look < 10; $look++) {
                    $nextFullName = $this->cellString($sheet, $rowIdx + $look, 6);
                    $nextDoc = $this->cellString($sheet, $rowIdx + $look, 5);
                    if ($nextFullName === '' && $nextDoc === '') {
                        $futureEmptyCount++;
                    }
                }
                if ($futureEmptyCount >= 8) {
                    break;
                }
            }

            $row = [
                'excel_row' => $rowIdx,
                'affiliate' => [
                    'is_active' => $this->parseAffiliateActiveStatus($affiliateStatusRaw),
                    'client_type_raw' => $clientTypeRaw,
                    'document_type' => $this->normalizeAffiliateDocumentType($affiliateDocumentTypeRaw),
                    'document_number_raw' => $affiliateDocumentNumberRaw,
                    'document_number' => $this->onlyDigits($affiliateDocumentNumberRaw),
                    'full_name' => strtoupper(trim($fullNameRaw)),
                    'gender' => $this->cellString($sheet, $rowIdx, 7),
                    'birth_date' => $this->parseExcelDate($this->cellString($sheet, $rowIdx, 8)),
                    'address' => $this->cellString($sheet, $rowIdx, 9),
                    'city' => $this->cellString($sheet, $rowIdx, 10),
                    'department' => $this->cellString($sheet, $rowIdx, 11),
                    'phone' => $this->normalizePhone($this->cellString($sheet, $rowIdx, 12)),
                    'email' => $this->cellString($sheet, $rowIdx, 13),
                ],
                'employer' => [
                    'document_type' => $this->cellString($sheet, $rowIdx, 15),
                    'document_number_raw' => $this->cellString($sheet, $rowIdx, 16),
                    'name' => $this->cellString($sheet, $rowIdx, 14),
                    'address' => $this->cellString($sheet, $rowIdx, 17),
                    'city' => $this->cellString($sheet, $rowIdx, 18),
                    'department' => $this->cellString($sheet, $rowIdx, 19),
                    'phone' => $this->normalizePhone($this->cellString($sheet, $rowIdx, 20)),
                    'email' => $this->cellString($sheet, $rowIdx, 21),
                    'payment_business_day' => $this->cellIntInRange($sheet, $rowIdx, 22, 2, 16, 5),
                ],
                'affiliation' => [
                    'cotizante_type_code' => $this->cotizanteTypeCode($this->cellString($sheet, $rowIdx, 3)),
                    'pila_operator' => $this->normalizeOperator($this->cellString($sheet, $rowIdx, 26)),
                    'last_novelty_type' => $this->cellString($sheet, $rowIdx, 24),
                    'last_novelty_date' => $this->parseExcelDate($this->cellString($sheet, $rowIdx, 25)),
                    'ibc' => $this->parseSalary($this->cellString($sheet, $rowIdx, 29)),
                    'pays_parafiscales' => $this->parseYesNo($this->cellString($sheet, $rowIdx, 43), false),
                    // RN-E02: dependiente del "cliente tipo" de Excel (INDEPENDIENTE => self_employed).
                    'self_employed' => $this->isIndependent($clientTypeRaw),
                    'arl_code' => $this->extractCatalogCode($this->cellString($sheet, $rowIdx, 30)),
                    'risk_class_level' => $this->parseRiskClassLevel($this->cellString($sheet, $rowIdx, 31)),
                    'ccf_code' => $this->extractCatalogCode($this->cellString($sheet, $rowIdx, 34)),
                    'eps_code' => $this->extractCatalogCode($this->cellString($sheet, $rowIdx, 37)),
                    'afp_code' => $this->extractCatalogCode($this->cellString($sheet, $rowIdx, 40)),
                    'payment_periodicity' => $this->paymentPeriodicity($this->cellString($sheet, $rowIdx, 45)),
                    'billing_type' => $this->billingType($this->cellString($sheet, $rowIdx, 46)),
                    'last_document_number' => $this->cellString($sheet, $rowIdx, 47),
                    'last_payment_period' => $this->paymentPeriodAAAAMM($this->cellString($sheet, $rowIdx, 48), $baseYear),
                    'payment_status' => $this->paymentStatus($this->cellString($sheet, $rowIdx, 49)),
                    'is_current' => true,
                ],
                'pila_credentials' => [
                    'username' => $this->cellString($sheet, $rowIdx, 27),
                    'password' => $this->cellString($sheet, $rowIdx, 28),
                ],
                'portal_credentials' => [
                    'arl' => [
                        'username' => $this->cellString($sheet, $rowIdx, 32),
                        'password' => $this->cellString($sheet, $rowIdx, 33),
                    ],
                    'ccf' => [
                        'username' => $this->cellString($sheet, $rowIdx, 35),
                        'password' => $this->cellString($sheet, $rowIdx, 36),
                    ],
                    'eps' => [
                        'username' => $this->cellString($sheet, $rowIdx, 38),
                        'password' => $this->cellString($sheet, $rowIdx, 39),
                    ],
                    'afp' => [
                        'username' => $this->cellString($sheet, $rowIdx, 41),
                        'password' => $this->cellString($sheet, $rowIdx, 42),
                    ],
                ],
                'notes' => [
                    'affiliation' => $this->cellString($sheet, $rowIdx, 44),
                    'payment' => $this->cellString($sheet, $rowIdx, 50),
                ],
            ];

            // Para independientes, el empleador es el mismo afiliado: copia datos y cambia DV.
            if ($row['affiliation']['self_employed'] === true) {
                [$empDocNumber, $empCheckDigit] = $this->splitDocumentNumberForEmployer($row['affiliate']['document_number_raw']);

                $row['employer']['document_type'] = $row['affiliate']['document_type'] !== '' ? strtoupper($row['affiliate']['document_type']) : $row['employer']['document_type'];
                $row['employer']['document_number_raw'] = $empDocNumber . ($empCheckDigit ? '-' . $empCheckDigit : '');
                $row['employer']['name'] = $row['affiliate']['full_name'];
                $row['employer']['address'] = $row['affiliate']['address'];
                $row['employer']['city'] = $row['affiliate']['city'];
                $row['employer']['department'] = $row['affiliate']['department'];
                $row['employer']['phone'] = $row['affiliate']['phone'];
                $row['employer']['email'] = $row['affiliate']['email'];
            }

            // Filtro defensivo: si document_number no tiene dígitos, saltamos la fila.
            if ($row['affiliate']['document_number'] === '') {
                $summary['errors'][] = [
                    $rowIdx,
                    'document_number',
                    $affiliateDocumentNumberRaw,
                    'No se pudo obtener el número de documento del afiliado (sin dígitos).',
                ];
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function phase1Catalogs(array $rows, array $ctx): void
    {
        $dryRun = (bool) ($ctx['dry_run'] ?? false);
        $summary = &$ctx['summary'];

        // Solo se verifica; la importación de catálogos se hace con seeders ya existentes.
        $cotizanteTypesEmpty = ! PilaCotizanteType::query()->exists();
        $riskClassesEmpty = ! PilaRiskClass::query()->exists();
        $epsEmpty = ! Eps::query()->exists();
        $afpEmpty = ! Afp::query()->exists();
        $arpEmpty = ! Arp::query()->exists();
        $ccfEmpty = ! Ccf::query()->exists();

        if ($cotizanteTypesEmpty) {
            if ($dryRun) {
                $summary['errors'][] = [0, 'pila_cotizante_types', null, 'Cotizante types vacío (dry-run): se esperaba seeding antes del import.'];
            } else {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PilaCotizanteTypeSeeder']);
            }
        }

        if ($riskClassesEmpty) {
            if ($dryRun) {
                $summary['errors'][] = [0, 'pila_risk_classes', null, 'Risk classes vacío (dry-run): se esperaba seeding antes del import.'];
            } else {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PilaRiskClassSeeder']);
            }
        }

        if ($epsEmpty || $afpEmpty || $arpEmpty || $ccfEmpty) {
            if ($dryRun) {
                $summary['errors'][] = [0, 'social_entities', null, 'Catálogos sociales incompletos (dry-run): se esperaba seeding antes del import.'];
            } else {
                if ($epsEmpty) {
                    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\EpsSeeder']);
                }
                if ($afpEmpty) {
                    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\AfpSeeder']);
                }
                if ($arpEmpty) {
                    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ArpSeeder']);
                }
                if ($ccfEmpty) {
                    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CcfSeeder']);
                }
            }
        }
    }

    /**
     * @return array<string, int> employerId by doc key
     */
    private function phase2Employers(array $rows, array $ctx): array
    {
        $onProgress = $ctx['on_progress'] ?? null;
        $summary = &$ctx['summary'];

        // Deduplicación por documento base (sin dígito verificador).
        $byKey = [];
        foreach ($rows as $r) {
            $docRaw = $r['employer']['document_number_raw'] ?? '';
            [$docNumberBase, $checkDigit] = $this->splitDocumentNumberForEmployer($docRaw);
            if ($docNumberBase === '') {
                $summary['errors'][] = [$r['excel_row'], 'employer.document_number', $docRaw, 'No se pudo obtener NIT/CC del empleador.'];
                continue;
            }

            $docType = strtoupper(trim((string) ($r['employer']['document_type'] ?? '')));
            $key = $docNumberBase;

            if (! isset($byKey[$key])) {
                $byKey[$key] = [
                    'document_type' => $docType !== '' ? $docType : 'NIT',
                    'document_number' => $docNumberBase,
                    'check_digit' => $checkDigit,
                    'name' => $r['employer']['name'] ?? '',
                    'address' => $r['employer']['address'] ?? null,
                    'city' => $r['employer']['city'] ?? null,
                    'department' => $r['employer']['department'] ?? null,
                    'phone' => $r['employer']['phone'] ?? null,
                    'email' => $r['employer']['email'] ?? null,
                    'payment_business_day' => $r['employer']['payment_business_day'] ?? 5,
                    'is_self_employed' => (bool) ($r['affiliation']['self_employed'] ?? false),
                    'is_active' => $r['affiliate']['is_active'] ?? true,
                ];
            } else {
                $byKey[$key]['is_self_employed'] = $byKey[$key]['is_self_employed'] || (bool) ($r['affiliation']['self_employed'] ?? false);
                $byKey[$key]['is_active'] = $byKey[$key]['is_active'] && (bool) ($r['affiliate']['is_active'] ?? true);
            }
        }

        $total = count($byKey);
        $processed = 0;

        $employersByKey = [];
        foreach ($byKey as $key => $data) {
            $processed++;
            if ($onProgress) {
                $onProgress('FASE 2 — Empleadores', $processed, $total);
            }

            try {
                $existing = PilaEmployer::query()
                    ->where('document_type', $data['document_type'])
                    ->where('document_number', $data['document_number'])
                    ->first();

                $payload = [
                    'check_digit' => $data['check_digit'],
                    'name' => $data['name'] ?? $data['document_number'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'department' => $data['department'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'payment_business_day' => $data['payment_business_day'],
                    'is_active' => (bool) $data['is_active'],
                    'is_self_employed' => (bool) $data['is_self_employed'],
                    'notes' => null,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $summary['employers_updated']++;
                    $employersByKey[$key] = $existing->id;
                } else {
                    $created = PilaEmployer::query()->create([
                        'document_type' => $data['document_type'],
                        'document_number' => $data['document_number'],
                        ...$payload,
                    ]);
                    $summary['employers_created']++;
                    $employersByKey[$key] = $created->id;
                }
            } catch (\Throwable $e) {
                $summary['errors'][] = [0, 'employers', $data['document_number'], 'Error al guardar empleador: ' . $e->getMessage()];
            }
        }

        return $employersByKey;
    }

    /**
     * @return array<string, int> affiliatesByDocKey document_number => affiliate_id
     */
    private function phase3Affiliates(array $rows, array $employersByKey, array $ctx): array
    {
        $onProgress = $ctx['on_progress'] ?? null;
        $summary = &$ctx['summary'];

        $byDocKey = [];
        $total = count($rows);
        $processed = 0;

        foreach ($rows as $row) {
            $processed++;
            if ($onProgress) {
                $onProgress('FASE 3 — Afiliados', $processed, $total);
            }

            $excelRow = $row['excel_row'];
            $aff = $row['affiliate'];
            $docType = $aff['document_type'];
            $docNumber = $aff['document_number'];

            try {
                $existing = Affiliate::query()
                    ->where('document_type', $docType)
                    ->where('document_number', $docNumber)
                    ->first();

                // La BD tiene unicidad por `document_number` (independiente del tipo).
                // Si encontramos duplicidad pero por `document_type` no coincidimos, hacemos fallback.
                if (! $existing) {
                    $existing = Affiliate::query()
                        ->where('document_number', $docNumber)
                        ->first();
                    if ($existing) {
                        $existing->document_type = $docType;
                    }
                }

                [$firstName, $secondName, $lastName, $secondLastName] = $this->splitFullName($aff['full_name'] ?? '');

                $payload = [
                    'document_type' => $docType,
                    'document_number' => $docNumber,
                    'patient_type' => PatientType::COTIZANTE,
                    'first_name' => $firstName,
                    'second_name' => $secondName,
                    'last_name' => $lastName,
                    'second_last_name' => $secondLastName,
                    'gender' => $this->normalizeGender($aff['gender'] ?? null),
                    'birth_date' => $aff['birth_date'],
                    'address' => $aff['address'],
                    'city' => $aff['city'],
                    'department' => $aff['department'],
                    'phone' => $aff['phone'],
                    'whatsapp' => null,
                    'email' => $aff['email'],
                    'neighborhood' => null,
                    'status' => $aff['is_active'] ? AffiliateStatus::ACTIVO : AffiliateStatus::INACTIVO,
                    'created_by' => null,
                    'updated_by' => null,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $summary['affiliates_updated']++;
                    $affiliateId = $existing->id;
                } else {
                    $created = Affiliate::query()->create($payload);
                    $summary['affiliates_created']++;
                    $affiliateId = $created->id;
                }

                $byDocKey[$docNumber] = $affiliateId;

                // Guardamos el linkage employer_id via memoria (para fase 4).
                // Si no existe en cache todavía, lo resolvemos en fase 4 con el doc number base del empleador.
                $employerDocRaw = $row['employer']['document_number_raw'] ?? '';
                [$employerDocBase, $checkDigit] = $this->splitDocumentNumberForEmployer($employerDocRaw);

                if ($employerDocBase !== '') {
                    $row['__employer_doc_base'] = $employerDocBase;
                    $row['__employer_id'] = $employersByKey[$employerDocBase] ?? null;
                }
            } catch (\Throwable $e) {
                $summary['errors'][] = [$excelRow, 'affiliates', $docNumber, 'Error al guardar afiliado: ' . $e->getMessage()];
            }
        }

        return $byDocKey;
    }

    /**
     * @return array<string, int> affiliationIdsByAffiliateDocKey affiliate document_number => pila_affiliation_id
     */
    private function phase4Affiliations(array $rows, array $affiliatesByDocKey, array $ctx): array
    {
        $onProgress = $ctx['on_progress'] ?? null;
        $summary = &$ctx['summary'];

        $byAffDocKey = [];
        $total = count($rows);
        $processed = 0;

        $cotizanteTypeCache = [];
        $riskClassCache = [];
        $epsCache = [];
        $afpCache = [];
        $arpCache = [];
        $ccfCache = [];

        $syncService = app(PilaAffiliationSyncService::class);

        // Cache de empleadores para resolver FKs.
        $employerCache = [];

        foreach ($rows as $row) {
            $processed++;
            if ($onProgress) {
                $onProgress('FASE 4 — Afiliaciones', $processed, $total);
            }

            $excelRow = $row['excel_row'];
            $aff = $row['affiliate'];
            $affDocNumber = $aff['document_number'];
            $affiliateId = $affiliatesByDocKey[$affDocNumber] ?? null;

            if (! $affiliateId) {
                $summary['errors'][] = [$excelRow, 'affiliation.affiliate_id', $affDocNumber, 'No se encontró el afiliado en la fase 3.'];
                continue;
            }

            try {
                [$employerDocBase] = $this->splitDocumentNumberForEmployer($row['employer']['document_number_raw'] ?? '');
                $employerDocType = strtoupper(trim((string) ($row['employer']['document_type'] ?? '')));
                $cacheKey = $employerDocType . ':' . $employerDocBase;
                $employerId = $employerCache[$cacheKey] ?? null;
                if ($employerId === null && $employerDocBase !== '') {
                    $employerId = PilaEmployer::query()
                        ->where('document_type', $employerDocType)
                        ->where('document_number', $employerDocBase)
                        ->value('id');
                    $employerCache[$cacheKey] = $employerId;
                }

                $cotizanteTypeId = null;
                $cotCode = $row['affiliation']['cotizante_type_code'] ?? '';
                if ($cotCode !== '') {
                    if (! isset($cotizanteTypeCache[$cotCode])) {
                        $cotizanteTypeCache[$cotCode] = PilaCotizanteType::query()->where('code', $cotCode)->value('id');
                    }
                    $cotizanteTypeId = $cotizanteTypeCache[$cotCode];
                    if (! $cotizanteTypeId) {
                        $summary['errors'][] = [$excelRow, 'cotizante_type_code', $cotCode, 'Cotizante type no existe en el catálogo.'];
                    }
                }

                $riskClassId = null;
                $level = $row['affiliation']['risk_class_level'] ?? null;
                if ($level !== null) {
                    if (! isset($riskClassCache[(string) $level])) {
                        $riskClassCache[(string) $level] = PilaRiskClass::query()->where('level', $level)->value('id');
                    }
                    $riskClassId = $riskClassCache[(string) $level];
                    if (! $riskClassId && $level !== 0) {
                        $summary['errors'][] = [$excelRow, 'risk_class_level', (string) $level, 'Risk class no existe en el catálogo.'];
                    }
                }

                $epsId = null;
                $epsCode = $row['affiliation']['eps_code'] ?? '';
                if ($epsCode !== '') {
                    if (! isset($epsCache[$epsCode])) {
                        $epsCache[$epsCode] = Eps::query()->where('code', $epsCode)->value('id');
                    }
                    $epsId = $epsCache[$epsCode];
                    if (! $epsId) {
                        $summary['errors'][] = [$excelRow, 'eps_code', $epsCode, 'EPS no existe en el catálogo.'];
                    }
                }

                $afpId = null;
                $afpCode = $row['affiliation']['afp_code'] ?? '';
                if ($afpCode !== '') {
                    if (! isset($afpCache[$afpCode])) {
                        $afpCache[$afpCode] = Afp::query()->where('code', $afpCode)->value('id');
                    }
                    $afpId = $afpCache[$afpCode];
                    if (! $afpId) {
                        $summary['errors'][] = [$excelRow, 'afp_code', $afpCode, 'AFP no existe en el catálogo.'];
                    }
                }

                $arpId = null;
                $arpCode = $row['affiliation']['arl_code'] ?? '';
                if ($arpCode !== '') {
                    if (! isset($arpCache[$arpCode])) {
                        $arpCache[$arpCode] = Arp::query()->where('code', $arpCode)->value('id');
                    }
                    $arpId = $arpCache[$arpCode];
                    if (! $arpId) {
                        $summary['errors'][] = [$excelRow, 'arl_code', $arpCode, 'ARL no existe en el catálogo.'];
                    }
                }

                $ccfId = null;
                $ccfCode = $row['affiliation']['ccf_code'] ?? '';
                if ($ccfCode !== '') {
                    if (! isset($ccfCache[$ccfCode])) {
                        $ccfCache[$ccfCode] = Ccf::query()->where('code', $ccfCode)->value('id');
                    }
                    $ccfId = $ccfCache[$ccfCode];
                    if (! $ccfId) {
                        $summary['errors'][] = [$excelRow, 'ccf_code', $ccfCode, 'CCF no existe en el catálogo.'];
                    }
                }

                $payload = [
                    'affiliate_id' => $affiliateId,
                    'employer_id' => $employerId,
                    'cotizante_type_id' => $cotizanteTypeId,
                    'pila_operator' => $row['affiliation']['pila_operator'] ?? null,
                    'last_novelty_type' => $this->normalizeLastNoveltyType($row['affiliation']['last_novelty_type'] ?? null),
                    'last_novelty_date' => $row['affiliation']['last_novelty_date'],
                    'ibc' => $row['affiliation']['ibc'],
                    'pays_parafiscales' => (bool) ($row['affiliation']['pays_parafiscales'] ?? false),
                    'self_employed' => (bool) ($row['affiliation']['self_employed'] ?? false),
                    'risk_class_id' => $riskClassId,
                    'eps_id' => $epsId,
                    'afp_id' => $afpId,
                    'arp_id' => $arpId,
                    'ccf_id' => $ccfId,
                    'payment_periodicity' => $row['affiliation']['payment_periodicity'] ?? null,
                    'billing_type' => $row['affiliation']['billing_type'] ?? null,
                    'last_document_number' => $this->normalizeLastDocumentNumber($row['affiliation']['last_document_number'] ?? null),
                    'last_payment_period' => $row['affiliation']['last_payment_period'] ?? null,
                    'payment_status' => $row['affiliation']['payment_status'] ?? null,
                    'is_current' => true,
                ];

                // RN: exterior -> entidades nulas (ARL y CCF).
                $clientTypeRaw = strtoupper(trim((string) ($row['affiliate']['client_type_raw'] ?? '')));
                if ($clientTypeRaw === 'EXTERIOR' || $clientTypeRaw === 'FOREIGN_RESIDENT') {
                    $payload['arp_id'] = null;
                    $payload['ccf_id'] = null;
                }

                $affiliation = PilaAffiliation::query()->updateOrCreate(
                    ['affiliate_id' => $affiliateId],
                    $payload
                );

                $byAffDocKey[$affDocNumber] = $affiliation->id;

                // Compatibilidad con módulos legacy: crea/sincroniza SocialSecurityProfile.
                $syncService->syncToSocialSecurityProfile(
                    $affiliation->affiliate,
                    $affiliation
                );

                // Nota: last_payment_period es clave para ordenamiento; si es null, se registra advertencia.
                if (empty($payload['last_payment_period'])) {
                    $summary['errors'][] = [$excelRow, 'last_payment_period', $row['affiliation']['last_payment_period'], 'No se pudo normalizar el mes de pago.'];
                }
            } catch (\Throwable $e) {
                $summary['errors'][] = [$excelRow, 'affiliations', $affDocNumber, 'Error al guardar afiliación: ' . $e->getMessage()];
            }
        }

        return $byAffDocKey;
    }

    private function phase5PilaCredentials(array $rows, array $affiliationIdsByAffiliateDocKey, array $ctx): void
    {
        $onProgress = $ctx['on_progress'] ?? null;
        $summary = &$ctx['summary'];
        $svc = app(CredentialService::class);

        $total = count($rows);
        $processed = 0;

        foreach ($rows as $row) {
            $processed++;
            if ($onProgress) {
                $onProgress('FASE 5 — Credenciales PILA', $processed, $total);
            }

            $excelRow = $row['excel_row'];
            $affiliateDoc = $row['affiliate']['document_number'];
            $affiliationId = $affiliationIdsByAffiliateDocKey[$affiliateDoc] ?? null;

            if (! $affiliationId) {
                continue;
            }

            $affiliation = PilaAffiliation::query()->find($affiliationId);
            if (! $affiliation || ! $affiliation->employer_id) {
                $summary['errors'][] = [$excelRow, 'pila_credentials.employer_id', null, 'No hay employer_id en afiliación.'];
                continue;
            }

            try {
                $username = trim((string) ($row['pila_credentials']['username'] ?? ''));
                $password = (string) ($row['pila_credentials']['password'] ?? '');

                if ($username === '' || $password === '' || $this->isNotApplicableValue($username) || $this->isNotApplicableValue($password)) {
                    continue;
                }

                $operator = $affiliation->pila_operator;
                if ($operator === null || $operator === '') {
                    $summary['errors'][] = [$excelRow, 'pila_operator', $operator, 'Falta pila_operator para cifrar credenciales PILA.'];
                    continue;
                }

                $encrypted = Crypt::encryptString($password);

                $payload = [
                    'username' => $username,
                    'password_encrypted' => $encrypted,
                    'is_active' => true,
                    'password_updated_at' => now(),
                ];

                // Upsert por (employer_id, operator) por la restricción de tabla.
                $existing = PilaCredential::query()
                    ->where('employer_id', $affiliation->employer_id)
                    ->where('operator', $operator)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $svc->audit(
                        CredentialKind::PILA,
                        $existing->id,
                        CredentialAction::UPDATED,
                        null,
                        null,
                        ['operator' => $operator, 'employer_id' => $affiliation->employer_id]
                    );
                } else {
                    $created = PilaCredential::query()->create([
                        'employer_id' => $affiliation->employer_id,
                        'operator' => $operator,
                        ...$payload,
                    ]);
                    $svc->audit(
                        CredentialKind::PILA,
                        $created->id,
                        CredentialAction::CREATED,
                        null,
                        null,
                        ['operator' => $operator, 'employer_id' => $affiliation->employer_id]
                    );
                }

                $summary['credentials_encrypted']++;
            } catch (\Throwable $e) {
                $summary['errors'][] = [$excelRow, 'pila_credentials', $row['pila_credentials']['username'] ?? null, 'Error al cifrar/guardar credencial PILA: ' . $e->getMessage()];
            }
        }
    }

    private function phase6PortalCredentials(array $rows, array $affiliationIdsByAffiliateDocKey, array $ctx): void
    {
        $onProgress = $ctx['on_progress'] ?? null;
        $summary = &$ctx['summary'];

        $total = count($rows);
        $processed = 0;

        foreach ($rows as $row) {
            $processed++;
            if ($onProgress) {
                $onProgress('FASE 6 — Credenciales Portales', $processed, $total);
            }

            $excelRow = $row['excel_row'];
            $affiliateDoc = $row['affiliate']['document_number'];
            $affiliationId = $affiliationIdsByAffiliateDocKey[$affiliateDoc] ?? null;

            if (! $affiliationId) {
                continue;
            }

            $affiliation = PilaAffiliation::query()->with('affiliate')->find($affiliationId);
            if (! $affiliation) {
                continue;
            }

            try {
                $this->upsertPortalCredentialFromExcel(
                    $affiliation,
                    $excelRow,
                    'ARL',
                    'arl',
                    $row['portal_credentials']['arl'] ?? [],
                    $affiliation->arp_id,
                    'arp_id',
                    $summary
                );
                $this->upsertPortalCredentialFromExcel(
                    $affiliation,
                    $excelRow,
                    'CCF',
                    'ccf',
                    $row['portal_credentials']['ccf'] ?? [],
                    $affiliation->ccf_id,
                    'ccf_id',
                    $summary
                );
                $this->upsertPortalCredentialFromExcel(
                    $affiliation,
                    $excelRow,
                    'EPS',
                    'eps',
                    $row['portal_credentials']['eps'] ?? [],
                    $affiliation->eps_id,
                    'eps_id',
                    $summary
                );
                $this->upsertPortalCredentialFromExcel(
                    $affiliation,
                    $excelRow,
                    'AFP',
                    'afp',
                    $row['portal_credentials']['afp'] ?? [],
                    $affiliation->afp_id,
                    'afp_id',
                    $summary
                );
            } catch (\Throwable $e) {
                $summary['errors'][] = [$excelRow, 'portal_credentials', null, 'Error al guardar credenciales portales: ' . $e->getMessage()];
            }
        }
    }

    private function upsertPortalCredentialFromExcel(
        PilaAffiliation $affiliation,
        int $excelRow,
        string $entityType,
        string $excelKey,
        array $excelCred,
        $catalogId,
        string $fkColumn,
        array &$summary
    ): void {
        static $svc = null;
        if ($svc === null) {
            $svc = app(CredentialService::class);
        }

        $usernameRaw = trim((string) ($excelCred['username'] ?? ''));
        $passwordRaw = (string) ($excelCred['password'] ?? '');

        // "Sin datos" => no creamos credencial.
        if ($usernameRaw === '' && $passwordRaw === '') {
            return;
        }

        $isNotApplicable = $this->isNotApplicableValue($usernameRaw) || $this->isNotApplicableValue($passwordRaw);

        // Si hay inconsistencia (faltan usuario/contraseña) pero no es "N/A", se omite.
        if (! $isNotApplicable && ($usernameRaw === '' || $passwordRaw === '')) {
            $summary['errors'][] = [$excelRow, "portal.$entityType.$excelKey", $usernameRaw . '/' . $passwordRaw, 'Credencial incompleta (faltan usuario/contraseña).'];
            return;
        }

        $search = [
            'affiliate_id' => $affiliation->affiliate_id,
            'entity_type' => $entityType,
        ];
        if ($catalogId) {
            $search[$fkColumn] = $catalogId;
        }

        $payload = [
            'employer_id' => $affiliation->employer_id,
            'affiliate_id' => $affiliation->affiliate_id,
            'entity_type' => $entityType,
            $fkColumn => $catalogId,
            'is_active' => true,
            'is_not_applicable' => $isNotApplicable,
            'username' => null,
            'password_encrypted' => null,
            'password_updated_at' => null,
        ];

        if (! $isNotApplicable) {
            $payload['username'] = $usernameRaw;
            $payload['password_encrypted'] = Crypt::encryptString($passwordRaw);
            $payload['password_updated_at'] = now();

            if (! $catalogId) {
                $summary['errors'][] = [$excelRow, "portal.$entityType.{$fkColumn}", $usernameRaw, 'Entidad no existe en catálogo: FK quedará en null.'];
            }

            $summary['credentials_encrypted']++;
        }

        $existing = PortalCredential::query()->where($search)->first();
        if ($existing) {
            $existing->update($payload);
            $svc->audit(
                CredentialKind::PORTAL,
                $existing->id,
                CredentialAction::UPDATED,
                null,
                null,
                ['entity_type' => $entityType, 'employer_id' => $affiliation->employer_id, 'affiliate_id' => $affiliation->affiliate_id]
            );
        } else {
            $created = PortalCredential::query()->create($payload);
            $svc->audit(
                CredentialKind::PORTAL,
                $created->id,
                CredentialAction::CREATED,
                null,
                null,
                ['entity_type' => $entityType, 'employer_id' => $affiliation->employer_id, 'affiliate_id' => $affiliation->affiliate_id]
            );
        }

    }

    private function phase7Notes(array $rows, array $affiliatesByDocKey, array $ctx): void
    {
        $onProgress = $ctx['on_progress'] ?? null;
        $summary = &$ctx['summary'];

        $total = count($rows);
        $processed = 0;

        foreach ($rows as $row) {
            $processed++;
            if ($onProgress) {
                $onProgress('FASE 7 — Observaciones', $processed, $total);
            }

            $excelRow = $row['excel_row'];
            $affiliateDoc = $row['affiliate']['document_number'];
            $affiliateId = $affiliatesByDocKey[$affiliateDoc] ?? null;

            if (! $affiliateId) {
                continue;
            }

            try {
                foreach (['affiliation' => 'affiliation', 'payment' => 'payment'] as $excelKey => $type) {
                    $content = trim((string) ($row['notes'][$excelKey] ?? ''));
                    if ($content === '') {
                        continue;
                    }

                    $isPinned = $this->isPinnedNote($content);

                    // Se evita duplicación por contenido + tipo + affiliate_id.
                    $existing = DB::table('affiliate_notes')
                        ->where('affiliate_id', $affiliateId)
                        ->where('type', $type)
                        ->where('content', $content)
                        ->first();

                    if ($existing) {
                        continue;
                    }

                    DB::table('affiliate_notes')->insert([
                        'affiliate_id' => $affiliateId,
                        'type' => $type,
                        'content' => $content,
                        'is_pinned' => $isPinned,
                        'created_by' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $summary['notes_created']++;
                }
            } catch (\Throwable $e) {
                $summary['errors'][] = [$excelRow, 'affiliate_notes', null, 'Error al guardar nota: ' . $e->getMessage()];
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers de transformación
    // ──────────────────────────────────────────────────────────────────────────

    private function cellString($sheet, int $rowIdx, int $colIdx): string
    {
        try {
            $value = $sheet->getCellByColumnAndRow($colIdx, $rowIdx)->getValue();
        } catch (\Throwable) {
            return '';
        }

        if ($value === null) {
            return '';
        }

        // PhpSpreadsheet a veces retorna float para celdas numéricas.
        if (is_float($value) || is_int($value)) {
            $value = (string) $value;
        }

        $value = (string) $value;
        $value = str_replace("\xc2\xa0", ' ', $value); // &nbsp;

        $value = trim($value);
        if ($value === '-' || $value === '—') {
            return '';
        }

        return $value;
    }

    private function cellIntInRange($sheet, int $rowIdx, int $colIdx, int $min, int $max, int $default): int
    {
        $raw = $this->cellString($sheet, $rowIdx, $colIdx);
        $digitsOnly = preg_replace('/\D/', '', $raw);
        if ($digitsOnly === '') {
            return $default;
        }
        $int = (int) $digitsOnly;
        if ($int < $min || $int > $max) {
            return $default;
        }
        return $int;
    }

    private function parseAffiliateActiveStatus(string $raw): bool
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return true;
        }
        if (str_contains($v, 'INACT')) {
            return false;
        }
        if (str_contains($v, 'SUSP')) {
            // Consideramos suspendido como inactivo para operación.
            return false;
        }
        return true;
    }

    private function normalizeAffiliateDocumentType(string $raw): string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return 'cc';
        }

        $map = [
            'CC' => 'cc',
            'TI' => 'ti',
            'CE' => 'ce',
            'PA' => 'pa',
            'NIT' => 'nit',
            'PPT' => 'ppt',
            'PTT' => 'ptt',
            'RC' => 'rc',
        ];

        return $map[$v] ?? strtolower($v);
    }

    private function splitFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['', null, '', null];
        }

        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        $count = count($parts);

        if ($count === 1) {
            return [$parts[0], null, $parts[0], null];
        }

        // Heurística: 2-4 palabras.
        $first = $parts[0];
        $second = $count >= 3 ? $parts[1] : null;
        $last = $parts[$count - 1];
        $secondLast = $count >= 4 ? $parts[$count - 2] : null;

        return [$first, $second, $last, $secondLast];
    }

    private function normalizeGender(?string $gender): ?string
    {
        if ($gender === null) {
            return null;
        }
        $g = strtoupper(trim($gender));
        if ($g === '') {
            return null;
        }
        if (in_array($g, ['M', 'F'], true)) {
            return $g;
        }
        return $g;
    }

    private function isIndependent(string $clientTypeRaw): bool
    {
        $v = strtoupper(trim($clientTypeRaw));
        return $v === 'INDEPENDIENTE' || $v === 'INDEPENDENT';
    }

    private function normalizeOperator(string $raw): ?string
    {
        $v = strtoupper(trim($raw));
        if ($v === '' || $this->isNotApplicableValue($v)) {
            return null;
        }

        // El Excel a veces trae texto extra como "ARUS (enlace operativo)".
        // Nos quedamos con el token principal.
        if (str_contains($v, '(')) {
            $v = trim(explode('(', $v, 2)[0]);
        }
        if (str_contains($v, ',')) {
            $v = trim(explode(',', $v, 2)[0]);
        }

        $v = preg_replace('/[^A-Z0-9_-]/', '', trim($v));
        if ($v === '') {
            return null;
        }

        $v = strtolower($v);
        return mb_substr($v, 0, 30);
    }

    private function cotizanteTypeCode(string $raw): string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return '';
        }

        if (preg_match('/\b(\d{2})\b/', $v, $m)) {
            return $m[1];
        }

        // Fallback: primeras 2 cifras.
        $digits = preg_replace('/\D/', '', $v);
        return substr($digits . '  ', 0, 2) ?: '';
    }

    private function parseYesNo(string $raw, bool $default): bool
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return $default;
        }
        if (str_contains($v, 'SI')) {
            return true;
        }
        if (str_contains($v, 'NO')) {
            return false;
        }
        return $default;
    }

    private function parseRiskClassLevel(string $raw): ?int
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return null;
        }

        $romanMap = [
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            'V' => 5,
        ];

        foreach ($romanMap as $roman => $level) {
            if (str_contains($v, $roman)) {
                return $level;
            }
        }

        if (preg_match('/\b([0-5])\b/', $v, $m)) {
            return (int) $m[1];
        }

        // Fallback: primeras cifras.
        $digits = preg_replace('/\D/', '', $v);
        if ($digits === '') {
            return null;
        }
        $lvl = (int) substr($digits, -1);
        return $lvl >= 0 && $lvl <= 5 ? $lvl : null;
    }

    private function extractCatalogCode(string $raw): string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return '';
        }

        $upper = strtoupper($v);
        // Marcadores comunes de "no aplica" en el Excel.
        if (in_array($upper, [
            'N/A',
            'NA',
            'NO APLICA',
            'NO APLICA.',
            'SIN INFORMACION',
            'SIN INFORMACION.',
            'SIN INFORMACIÓN',
            'SIN INFORMACIÓN.',
        ], true)) {
            return '';
        }

        // Códigos que llegan como ceros (p.ej. "000-0", "0000") deben tratarse como "vacío".
        if (preg_match('/^0+([\\- ]0+)?$/', $upper)) {
            return '';
        }

        // Separa por " - " si viene con nombre.
        if (str_contains($v, ' - ')) {
            $v = trim(explode(' - ', $v, 2)[0]);
        } elseif (preg_match('/^([A-Z0-9\\-]+)\\s+/', strtoupper($v), $m)) {
            $v = $m[1];
        } else {
            $v = preg_split('/\\s+/', trim($v))[0] ?? $v;
        }

        $v = strtoupper(trim($v));

        // Reaplicar normalización luego del recorte (por si el "token" quedó en ceros).
        if ($v === '' || preg_match('/^0+([\\- ]0+)?$/', $v)) {
            return '';
        }

        return $v;
    }

    private function billingType(string $raw): ?string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return null;
        }
        if (str_contains($v, 'FACT')) {
            return 'factura_electronica';
        }
        if (str_contains($v, 'RECIB')) {
            return 'recibo_caja';
        }
        return null;
    }

    private function paymentPeriodicity(string $raw): ?string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return null;
        }
        if (str_contains($v, 'VENC')) {
            return 'vencido';
        }
        if (str_contains($v, 'ACTU')) {
            return 'actual';
        }
        return null;
    }

    private function paymentStatus(string $raw): ?string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return null;
        }
        if (str_contains($v, 'ANTIC')) {
            return 'anticipated';
        }
        if ($v === 'SI' || str_contains($v, 'SI')) {
            return 'current';
        }
        if ($v === 'NO' || str_contains($v, 'NO')) {
            return 'overdue';
        }
        return null;
    }

    private function paymentPeriodAAAAMM(string $raw, int $fallbackYear): ?string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return null;
        }

        $year = null;
        if (preg_match('/(20\\d{2})/', $v, $m)) {
            $year = (int) $m[1];
        } else {
            $year = $fallbackYear ?: now()->year;
        }

        $monthMap = [
            'ENERO' => '01',
            'FEBRERO' => '02',
            'MARZO' => '03',
            'ABRIL' => '04',
            'MAYO' => '05',
            'JUNIO' => '06',
            'JULIO' => '07',
            'AGOSTO' => '08',
            'SEPTIEMBRE' => '09',
            'OCTUBRE' => '10',
            'NOVIEMBRE' => '11',
            'DICIEMBRE' => '12',
        ];

        foreach ($monthMap as $monthName => $monthNum) {
            if (str_contains($v, $monthName)) {
                return sprintf('%04d%s', $year, $monthNum);
            }
        }

        // Si viene en formato YYYYMM o MM/YYYY.
        if (preg_match('/\\b(20\\d{2})(\\d{2})\\b/', $v, $m)) {
            return $m[1] . $m[2];
        }

        return null;
    }

    private function parseSalary(string $raw): ?string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return null;
        }

        // Quitar "$", ".", "," -> decimal puro (sin separadores).
        $digitsAndDot = str_replace(['$', '.', ','], '', $v);
        $digitsAndDot = preg_replace('/\\s+/', '', $digitsAndDot);
        if ($digitsAndDot === '') {
            return null;
        }

        // Guardamos como string con 2 decimales: el DB se encargará.
        return (string) (int) $digitsAndDot . '.00';
    }

    private function parseExcelDate(string $raw): ?string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return null;
        }

        // Si es número de Excel (serial date).
        if (is_numeric($v)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $v);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                // noop
            }
        }

        // DD/MM/YYYY.
        try {
            return Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d');
        } catch (\Throwable) {
            // Fallback: intento parse flexible.
        }

        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDateOptional(string $raw): ?string
    {
        return $this->parseExcelDate($raw);
    }

    private function inferYearFromPath(string $path): int
    {
        $filename = strtoupper(basename($path));
        if (preg_match('/(20\\d{2})/', $filename, $m)) {
            return (int) $m[1];
        }
        return now()->year;
    }

    private function isNotApplicableValue(string $value): bool
    {
        $v = strtoupper(trim($value));
        if ($v === '') {
            return false;
        }
        if ($v === 'N/A' || $v === 'NA') {
            return true;
        }

        if (str_contains($v, 'SIN INFORMACION') || str_contains($v, 'SIN INFORMACIÓN')) {
            return true;
        }

        return str_contains($v, 'NO APLICA');
    }

    private function normalizePhone(?string $raw): ?string
    {
        $v = trim((string) ($raw ?? ''));
        if ($v === '') {
            return null;
        }

        if ($this->isNotApplicableValue($v)) {
            return null;
        }

        // Normaliza números de WhatsApp/telefonía con texto extra:
        // - conserva solo dígitos y un posible prefijo '+'
        // - recorta al tamaño máximo del campo en BD (20)
        $hasPlus = str_contains($v, '+');
        $digits = preg_replace('/\\D/', '', $v);

        if ($digits === '') {
            return null;
        }

        $normalized = $hasPlus ? ('+' . $digits) : $digits;

        return mb_substr($normalized, 0, 20);
    }

    private function normalizeLastNoveltyType(?string $raw): ?string
    {
        $v = $this->normalizeOptionalUpper($raw);
        if ($v === null || $v === '') {
            return null;
        }

        // Ejemplos esperados desde Excel:
        // - "ING: INGRESO."   => "ING"
        // - "RET1: RETIRO."  => "RET1"
        if (str_contains($v, ':')) {
            $v = trim(explode(':', $v, 2)[0]);
        }

        // Conserva solo letras/números para cumplir el límite y evitar ":"/"."
        $v = preg_replace('/[^A-Z0-9]/', '', $v);

        if ($v === '') {
            return null;
        }

        return mb_substr($v, 0, 10);
    }

    private function normalizeLastDocumentNumber(?string $raw): ?string
    {
        $v = $this->normalizeOptionalUpper($raw);
        if ($v === null || $v === '') {
            return null;
        }

        if ($this->isNotApplicableValue($v)) {
            return null;
        }

        // Si viene como listado/rango numérico: "40920-40921-40922..." nos quedamos con el primero.
        if (preg_match('/^([0-9]+)-/', $v, $m)) {
            return mb_substr($m[1], 0, 30);
        }

        // Quedarnos con el primer token (por si el Excel trae texto extra con espacios o comas).
        if (str_contains($v, ',')) {
            $v = trim(explode(',', $v, 2)[0]);
        }
        if (str_contains($v, ' ')) {
            $v = trim(explode(' ', $v, 2)[0]);
        }

        // Permite guiones para formatos como "FVS-1754".
        $v = preg_replace('/[^A-Z0-9\\-]/', '', $v);

        if ($v === '') {
            return null;
        }

        return mb_substr($v, 0, 30);
    }

    private function isPinnedNote(string $content): bool
    {
        $u = strtoupper($content);
        foreach (['IMPORTANTE', 'SIEMPRE', 'OBLIGATORIO'] as $word) {
            if (str_contains($u, $word)) {
                return true;
            }
        }
        return false;
    }

    private function onlyDigits(string $raw): string
    {
        $digits = preg_replace('/\\D/', '', (string) $raw);
        return $digits ?? '';
    }

    /**
     * Para empleadores: separamos documento base y dígito verificador si viene como NIT-<DV>.
     *
     * @return array{0: string, 1: string|null} [document_number, check_digit]
     */
    private function splitDocumentNumberForEmployer(string $raw): array
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return ['', null];
        }

        // Variante NIT: "901776975-4"
        if (str_contains($s, '-')) {
            $parts = explode('-', $s, 2);
            $base = preg_replace('/\\D/', '', $parts[0] ?? '');
            $dv = preg_replace('/\\D/', '', $parts[1] ?? '');
            $dv = $dv !== '' ? substr($dv, -1) : null;
            return [$base, $dv];
        }

        // Si no hay "-", asumimos que no trae DV.
        $digitsOnly = preg_replace('/\\D/', '', $s);
        if ($digitsOnly === '') {
            return ['', null];
        }
        return [$digitsOnly, null];
    }

    private function normalizeOptionalUpper(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string) $value);
        return $v === '' ? null : strtoupper($v);
    }
}

class DryRunRollback extends \RuntimeException
{
}

