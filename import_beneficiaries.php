<?php

/**
 * Script de importación de beneficiarios desde storage/app/beneficiaries.csv
 * CSV delimitado por ; con encabezados.
 *
 * Columnas esperadas:
 *   documento_cotizante  (obligatorio) - Nº documento del cotizante titular
 *   tipo_documento       (obligatorio) - cc, ti, ce, pa, rc
 *   documento            (obligatorio) - Nº documento del beneficiario
 *   primer_nombre        (obligatorio)
 *   segundo_nombre       (opcional)
 *   primer_apellido      (obligatorio)
 *   segundo_apellido     (opcional)
 *   parentesco           (obligatorio) - conyuge, companero_permanente, hijo_menor, hijo_estudiante, hijo_discapacidad, padre, madre, hermano_discapacidad
 *   fecha_nacimiento     (opcional) - d/m/Y o Y-m-d
 *   sexo                 (opcional)
 *   telefono             (opcional)
 *   telefono_2           (opcional)
 *   celular_whatsapp     (opcional)
 *   email                (opcional)
 *   direccion            (opcional)
 *   barrio               (opcional)
 *   estado               (opcional) - por defecto ACTIVO
 *
 * Ejecutar:
 *   php import_beneficiaries.php
 *   php import_beneficiaries.php 123456789   (si documento_cotizante viene vacío en el CSV, usa este número para todas las filas)
 */

require __DIR__ . '/vendor/autoload.php';

// Opcional: documento del cotizante por defecto cuando la columna documento_cotizante está vacía en el CSV
$defaultHolderDoc = isset($argv[1]) && trim($argv[1]) !== '' ? trim($argv[1]) : null;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Enums\RelationshipType;

$path = storage_path('app/beneficiaries.csv');
if (!is_readable($path)) {
    echo "No se encontró storage/app/beneficiaries.csv\n";
    echo "Cree el archivo con los encabezados indicados en el comentario de este script.\n";
    exit(1);
}

$content = file_get_contents($path);
$content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1'], true));
$lines = preg_split('/\r\n|\r|\n/', $content, -1, PREG_SPLIT_NO_EMPTY);

$rows = array_map(function ($line) {
    return str_getcsv($line, ';');
}, $lines);

$header = array_shift($rows);
$header = array_map(function ($h) {
    return trim(mb_strtolower(str_replace([' ', '-'], '_', $h)));
}, $header);

$docTypeMap = ['cc' => 'cc', 'ti' => 'ti', 'ce' => 'ce', 'pa' => 'pa', 'rc' => 'rc', 'CC' => 'cc', 'TI' => 'ti', 'CE' => 'ce', 'PA' => 'pa', 'RC' => 'rc'];

$relationshipValues = array_map(fn ($case) => $case->value, RelationshipType::cases());

$imported = 0;
$skipped = 0;
$errors = [];

foreach ($rows as $i => $row) {
    $num = $i + 2;
    $row = array_pad($row, count($header), '');
    $data = array_combine($header, $row);
    if ($data === false) {
        $errors[] = "Fila $num: número de columnas no coincide con encabezados";
        $skipped++;
        continue;
    }

    $get = function ($key, $default = '') use ($data) {
        $v = $data[$key] ?? $data[str_replace('_', ' ', $key)] ?? $default;
        return is_string($v) ? trim($v) : $default;
    };

    $holderDoc = $get('documento_cotizante') ?: $defaultHolderDoc;
    if ($holderDoc === '' || $holderDoc === null) {
        $errors[] = "Fila $num: documento_cotizante vacío. Use la columna en el CSV o ejecute: php import_beneficiaries.php <documento_cotizante>";
        $skipped++;
        continue;
    }

    $holder = Affiliate::where('document_number', $holderDoc)
        ->where('patient_type', 'cotizante')
        ->first();

    if (!$holder) {
        $errors[] = "Fila $num: no existe cotizante con documento $holderDoc";
        $skipped++;
        continue;
    }

    if (!$holder->eps_id) {
        $errors[] = "Fila $num: cotizante $holderDoc no tiene EPS asignada";
        $skipped++;
        continue;
    }

    $docNum = $get('documento');
    if ($docNum === '') {
        $errors[] = "Fila $num: documento del beneficiario vacío";
        $skipped++;
        continue;
    }

    if (Affiliate::withTrashed()->where('document_number', $docNum)->exists()) {
        $errors[] = "Fila $num: documento $docNum ya existe";
        $skipped++;
        continue;
    }

    $docTypeRaw = $get('tipo_documento', 'cc');
    $docType = $docTypeMap[$docTypeRaw] ?? 'cc';

    $firstName = $get('primer_nombre');
    $lastName = $get('primer_apellido');
    if ($firstName === '' || $lastName === '') {
        $errors[] = "Fila $num: primer_nombre o primer_apellido vacío";
        $skipped++;
        continue;
    }

    $relationshipRaw = $get('parentesco');
    if ($relationshipRaw === '') {
        $errors[] = "Fila $num: parentesco vacío";
        $skipped++;
        continue;
    }
    $relationshipType = RelationshipType::tryFrom(strtolower($relationshipRaw));
    if ($relationshipType === null) {
        $valid = implode(', ', $relationshipValues);
        $errors[] = "Fila $num: parentesco '$relationshipRaw' no válido. Valores: $valid";
        $skipped++;
        continue;
    }

    $birthRaw = $get('fecha_nacimiento');
    $birthDate = null;
    if ($birthRaw !== '') {
        $d = \DateTime::createFromFormat('d/m/Y', $birthRaw) ?: \DateTime::createFromFormat('Y-m-d', $birthRaw);
        $birthDate = $d ? $d->format('Y-m-d') : null;
    }

    $status = $get('estado', 'ACTIVO');
    if ($status === '') {
        $status = 'ACTIVO';
    }

    try {
        Affiliate::create([
            'document_type' => $docType,
            'document_number' => $docNum,
            'first_name' => $firstName,
            'second_name' => $get('segundo_nombre') ?: null,
            'last_name' => $lastName,
            'second_last_name' => $get('segundo_apellido') ?: null,
            'birth_date' => $birthDate,
            'gender' => $get('sexo') ?: null,
            'address' => $get('direccion') ?: null,
            'neighborhood' => $get('barrio') ?: null,
            'phone' => $get('telefono') ?: null,
            'phone_2' => $get('telefono_2') ?: null,
            'whatsapp' => $get('celular_whatsapp') ?: null,
            'email' => $get('email') ?: null,
            'eps_id' => $holder->eps_id,
            'patient_type' => 'beneficiario',
            'holder_id' => $holder->id,
            'relationship_type' => $relationshipType->value,
            'status' => $status,
            'created_by' => null,
        ]);
        $imported++;
    } catch (\Throwable $e) {
        $errors[] = "Fila $num: " . $e->getMessage();
        $skipped++;
    }
}

echo "Beneficiarios importados: $imported\n";
echo "Omitidos/errores: $skipped\n";
if (!empty($errors)) {
    echo "\nDetalle:\n" . implode("\n", array_slice($errors, 0, 30)) . "\n";
    if (count($errors) > 30) {
        echo "... y " . (count($errors) - 30) . " más.\n";
    }
}
