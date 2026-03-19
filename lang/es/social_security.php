<?php

/**
 * Traducciones del módulo de Seguridad Social.
 * Todos los labels, mensajes de validación y descripciones del módulo SS se definen aquí.
 * Los enums y servicios usan __('social_security.xxx') para obtener textos traducibles.
 */
return [

    // --- Estados de afiliado ---
    'affiliate_status' => [
        'ACTIVO' => 'Activo',
        'INACTIVO' => 'Inactivo',
        'SUSPENDIDO' => 'Suspendido',
    ],

    // --- Estados de planilla ---
    'payroll_status' => [
        'PENDING' => 'Pendiente',
        'SETTLED' => 'Liquidada',
        'SENT_TO_CLIENT' => 'Enviada al cliente',
        'PAID' => 'Pagada',
        'OVERDUE' => 'En mora',
    ],

    // --- Periodicidad de pago ---
    'payment_periodicity' => [
        'CURRENT' => 'Actual',
        'OVERDUE' => 'Vencido',
    ],

    // --- Clase de riesgo ARL ---
    'arp_risk_class' => [
        '0' => 'No aplica',
        '1' => 'Clase I - Riesgo mínimo',
        '2' => 'Clase II - Riesgo bajo',
        '3' => 'Clase III - Riesgo medio',
        '4' => 'Clase IV - Riesgo alto',
        '5' => 'Clase V - Riesgo máximo',
    ],

    // --- Tipo de documento ---
    'document_type' => [
        'cc' => 'Cédula de Ciudadanía',
        'ti' => 'Tarjeta de Identidad',
        'ce' => 'Cédula de Extranjería',
        'pa' => 'Pasaporte',
        'rc' => 'Registro Civil',
        'nit' => 'NIT',
        'ppt' => 'Permiso por Protección Temporal',
        'ptt' => 'Permiso Temporal de Permanencia',
    ],

    // --- Tipo de afiliado ---
    'patient_type' => [
        'cotizante' => 'Cotizante',
        'beneficiario' => 'Beneficiario',
    ],

    // --- Tipo de proveedor (credenciales de operador) ---
    'provider_type' => [
        'PAYMENT_OPERATOR' => 'Operador de pago',
        'ARL' => 'ARL',
        'CCF' => 'Caja de Compensación',
        'EPS' => 'EPS',
        'AFP' => 'AFP',
    ],

    // --- Mensajes de validación del módulo ---
    'validation' => [
        'affiliate_inactive' => 'No se pueden crear solicitudes de cita para un afiliado inactivo.',
        'ibc_min' => 'El IBC no puede ser menor a :min.',
        'ibc_max' => 'El IBC no puede ser mayor a :max.',
        'payment_day_range' => 'El día de pago debe estar entre :min y :max.',
        'contributor_type_unsupported' => 'El tipo de cotizante :code no tiene reglas configuradas.',
        'duplicate_payroll' => 'Ya existe una planilla para el afiliado en el período :month/:year.',
        'profile_required' => 'El afiliado debe tener un perfil de seguridad social.',
        'payer_required_for_contracts' => 'Los contratos independientes requieren un pagador asignado.',
        'invalid_risk_class' => 'La clase de riesgo ARL debe ser un valor entre 0 y 5.',
        'eps_required' => 'La EPS es obligatoria para cotizantes con aporte a salud.',
        'afp_required' => 'La AFP es obligatoria para cotizantes con aporte a pensión.',
    ],

    // --- Mensajes de éxito ---
    'success' => [
        'payroll_settled' => 'Planilla liquidada correctamente.',
        'payroll_batch_settled' => ':count planilla(s) liquidada(s) correctamente.',
        'affiliate_created' => 'Afiliado creado correctamente.',
        'affiliate_updated' => 'Afiliado actualizado correctamente.',
        'payer_created' => 'Pagador creado correctamente.',
        'payer_updated' => 'Pagador actualizado correctamente.',
        'novelty_registered' => 'Novedad registrada correctamente.',
    ],

    // --- Mensajes de error ---
    'error' => [
        'settlement_failed' => 'Error al liquidar la planilla: :reason.',
        'missing_parameters' => 'No se encontraron parámetros de contribución vigentes para el período :period.',
        'missing_smlmv' => 'No se encontró el SMLMV vigente para el período :period.',
    ],
];
