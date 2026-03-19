<?php

return [
    'deadline' => [
        'min_business_day' => 2,
        'max_business_day' => 16,
        'default_business_day' => 2,

        // Decreto 1990 de 2016: últimos 2 dígitos del documento → ordinal del día hábil (2..16)
        // Se mantiene en config (no en código) para evitar "magic numbers" y permitir ajuste controlado.
        'last_two_digits_to_business_day' => [
            ['min' => 0, 'max' => 7, 'day' => 2],
            ['min' => 8, 'max' => 14, 'day' => 3],
            ['min' => 15, 'max' => 21, 'day' => 4],
            ['min' => 22, 'max' => 28, 'day' => 5],
            ['min' => 29, 'max' => 35, 'day' => 6],
            ['min' => 36, 'max' => 42, 'day' => 7],
            ['min' => 43, 'max' => 49, 'day' => 8],
            ['min' => 50, 'max' => 56, 'day' => 9],
            ['min' => 57, 'max' => 63, 'day' => 10],
            ['min' => 64, 'max' => 69, 'day' => 11],
            ['min' => 70, 'max' => 75, 'day' => 12],
            ['min' => 76, 'max' => 81, 'day' => 13],
            ['min' => 82, 'max' => 87, 'day' => 14],
            ['min' => 88, 'max' => 93, 'day' => 15],
            ['min' => 94, 'max' => 99, 'day' => 16],
        ],
    ],
    'employer' => [
        // Tipos de documento permitidos para aportante/empleador.
        // Se mantiene en config para evitar magic strings y permitir ajustes normativos.
        'allowed_document_types' => ['NIT', 'CC', 'CE'],
    ],

    // Valores operativos provenientes del Excel (fuente primaria en esta versión).
    // Se mantienen en config para evitar hardcodeo en UI/Requests.
    'affiliation' => [
        'payment_statuses' => ['current', 'overdue', 'anticipated'],
        'payment_periodicities' => ['mensual', 'bimestral', 'trimestral', 'semestral', 'anual', 'otro'],
        'billing_types' => ['recibo_caja', 'factura_electronica', 'transferencia', 'consignacion', 'otro'],
        'novelties' => [
            'ING' => 'Ingreso',
            'RET' => 'Retiro',
            'LMA' => 'Lic. maternidad',
            'IGE' => 'Incapacidad',
            'VAC' => 'Vacaciones',
            'SLN' => 'Susp. sin pago',
            'VSP' => 'Susp. con pago',
            'TDE' => 'Traslado EPS',
        ],
        // Valores permitidos para `pila_affiliations.pila_operator`.
        // Nota: los valores se guardan tal cual en BD, por eso se lista el catálogo real.
        'pila_operators' => [
            'arus' => 'Arus',
            'simple' => 'Simple',
            'asopagos' => 'Asopagos',
            'aportesenlinea' => 'Aportes en línea',
            'soi' => 'SOI',
            'miplanilla' => 'Mi planilla',
        ],
    ],
];

