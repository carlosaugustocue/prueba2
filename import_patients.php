<?php

/**
 * Script de importación de pacientes desde storage/app/patients.csv
 * CSV delimitado por ; con encabezados.
 * Ejecutar: php import_patients.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\Eps;
use Illuminate\Support\Str;

$path = storage_path('app/patients.csv');
if (!is_readable($path)) {
    echo "No se encontró storage/app/patients.csv\n";
    exit(1);
}

$content = file_get_contents($path);
$content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1'], true));
$lines = preg_split('/\r\n|\r|\n/', $content, -1, PREG_SPLIT_NO_EMPTY);

$rows = array_map(function ($line) {
    return str_getcsv($line, ';');
}, $lines);

$header = array_shift($rows);
$header = array_map('trim', $header);

$docTypeMap = ['CC' => 'cc', 'CE' => 'ce', 'PT' => 'ti', 'PA' => 'pa', 'TI' => 'ti'];

$epsList = Eps::all();
$epsByName = [];
foreach ($epsList as $e) {
    $key = strtoupper(preg_replace('/\s*(EPS|E\.P\.S\.?|S\.A\.?)\s*/i', '', $e->name));
    $epsByName[$key] = $e->id;
    $epsByName[Str::slug($key, '')] = $e->id;
}
// Mapeos directos por nombre en CSV
$epsByName['COMPENSAR'] = $epsList->firstWhere('name', 'like', '%Compensar%')?->id ?? null;
$epsByName['SANITAS'] = $epsList->firstWhere('name', 'like', '%Sanitas%')?->id ?? null;
$epsByName['SURA'] = $epsList->firstWhere('name', 'like', '%Sura%')?->id ?? null;
$epsByName['NUEVAE.P.S.S.A.'] = $epsList->firstWhere('name', 'like', '%Nueva%')?->id ?? null;
$epsByName['SALUDTOTAL'] = $epsList->firstWhere('name', 'like', '%Salud Total%')?->id ?? null;
$epsByName['FAMISANAR'] = $epsList->firstWhere('name', 'like', '%Famisanar%')?->id ?? null;

$imported = 0;
$skipped = 0;
$errors = [];

foreach ($rows as $i => $row) {
    $num = $i + 2;
    $row = array_pad($row, count($header), '');
    $data = array_combine($header, $row);
    if ($data === false) {
        $errors[] = "Fila $num: número de columnas no coincide";
        $skipped++;
        continue;
    }

    $docNum = trim($data['Documento de identidad del afiliado'] ?? '');
    if ($docNum === '') {
        $errors[] = "Fila $num: documento vacío";
        $skipped++;
        continue;
    }

    if (Affiliate::withTrashed()->where('document_number', $docNum)->exists()) {
        $errors[] = "Fila $num: documento $docNum ya existe";
        $skipped++;
        continue;
    }

    $docTypeRaw = strtoupper(trim($data['Tipo de documento afiliado'] ?? 'CC'));
    $docType = $docTypeMap[$docTypeRaw] ?? 'cc';

    $birthRaw = trim($data['Fecha de nacimiento'] ?? '');
    $birthDate = null;
    if ($birthRaw !== '') {
        $d = \DateTime::createFromFormat('d/m/Y', $birthRaw);
        $birthDate = $d ? $d->format('Y-m-d') : null;
    }

    $epsName = trim($data['Nombre EPS'] ?? '');
    $epsId = null;
    if ($epsName !== '') {
        $key = strtoupper(preg_replace('/[\s\.]+/', '', $epsName));
        $epsId = $epsByName[$key] ?? $epsList->firstWhere(function ($e) use ($epsName) {
            return stripos($e->name, $epsName) !== false || stripos($epsName, $e->name) !== false;
        })?->id;
    }

    $firstName = trim($data['Primer nombre afiliado'] ?? '');
    $lastName = trim($data['Primer apellido afiliado'] ?? '');
    if ($firstName === '' || $lastName === '') {
        $errors[] = "Fila $num: nombre o apellido vacío";
        $skipped++;
        continue;
    }

    try {
        Affiliate::create([
            'document_type' => $docType,
            'document_number' => $docNum,
            'first_name' => $firstName,
            'second_name' => trim($data['Segundo nombre afiliado'] ?? '') ?: null,
            'last_name' => $lastName,
            'second_last_name' => trim($data['Segundo apellido afiliado'] ?? '') ?: null,
            'birth_date' => $birthDate,
            'gender' => trim($data['Sexo'] ?? '') ?: null,
            'address' => trim($data['Dirección del afiliado'] ?? '') ?: null,
            'neighborhood' => trim($data['Barrio'] ?? '') ?: null,
            'phone' => trim($data['Teléfono 1'] ?? '') ?: null,
            'phone_2' => trim($data['Tel2'] ?? '') ?: null,
            'whatsapp' => trim($data['Número de Celular'] ?? '') ?: null,
            'email' => trim($data['Correo electrónico'] ?? '') ?: null,
            'eps_id' => $epsId,
            'afp_name' => trim($data['Nombre AFP'] ?? '') ?: null,
            'arp_name' => trim($data['Nombre ARP'] ?? '') ?: null,
            'arp_risk_class' => trim($data['Clase de Riesgo ARP'] ?? '') ?: null,
            'status' => trim($data['Descripcion'] ?? 'ACTIVO') ?: 'ACTIVO',
            'patient_type' => 'cotizante',
            'holder_id' => null,
            'relationship_type' => null,
            'created_by' => null,
        ]);
        $imported++;
    } catch (\Throwable $e) {
        $errors[] = "Fila $num: " . $e->getMessage();
        $skipped++;
    }
}

echo "Importados: $imported\nOmitidos/errores: $skipped\n";
if (!empty($errors)) {
    echo "\nDetalle:\n" . implode("\n", array_slice($errors, 0, 20)) . "\n";
    if (count($errors) > 20) {
        echo "... y " . (count($errors) - 20) . " más.\n";
    }
}
