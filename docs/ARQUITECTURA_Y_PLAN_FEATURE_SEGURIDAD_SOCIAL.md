# Arquitectura y plan — Feature Seguridad Social

**Objetivo:** Referencia única para la **estructura**, **buenas prácticas**, **normativa vigente** y **orden de implementación** del módulo de Seguridad Social de Serviconli, alineada con la realidad operativa colombiana y los casos de éxito del mercado (SOI, Mi Planilla, Aportes en Línea, Enlace Operativo).

**Documentos de referencia (obligatorios):**
- [PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md](PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md) — Fases 0–3, orden de ejecución
- [TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md) — Tareas concretas y criterios de aceptación
- [NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md](NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md) — Parametrización, cálculos, PILA

**Fecha de referencia normativa:** Enero 2026 — SMLMV $1.750.905, UVT $52.374.

---

## 1. Análisis de mercado: aplicaciones de referencia en Colombia

### 1.1 Operadores PILA autorizados

Los seis operadores autorizados por el Ministerio de Protección Social son modelos funcionales a seguir:

| Operador | Funcionalidades clave relevantes para Serviconli |
|----------|--------------------------------------------------|
| **SOI (ACH Colombia)** | Planilla electrónica y asistida; calculadora de aportes integrada; consulta y descarga de certificados de pago; histórico de planillas; validación de datos antes de generación. |
| **Mi Planilla** | Liquidación automática por tipo cotizante; generación de planilla con pre-validación; módulo de corrección de errores (planilla N); soporte multi-aportante. |
| **Aportes en Línea** | Filtros por estado de planilla (sin generar, en proceso, con egreso); edición por empleado; historial de cambios; generación de egresos; reinicio de procesos mensuales. |
| **Enlace Operativo** | Gestión de contratistas; liquidación masiva (batch); seguimiento de estados; integración con bancos para PSE. |
| **Asopagos** | Planilla asistida para independientes de bajos ingresos; validaciones simplificadas. |
| **Pago Simple** | Interfaz simplificada para microempresas; cálculo automático de IBC. |

### 1.2 Funcionalidades del mercado que Serviconli debe implementar

Del análisis de los operadores exitosos, estas son las funcionalidades críticas para el módulo:

| Funcionalidad de mercado | Prioridad | Implementación en Serviconli |
|--------------------------|-----------|------------------------------|
| **Calculadora de aportes** (SOI, Mi Planilla) | ALTA | `PayrollService::calculateAmounts()` — cálculo automático a partir de IBC, tipo cotizante, clase de riesgo ARL, parámetros vigentes de BD. |
| **Pre-validación antes de liquidar** (Aportes en Línea) | ALTA | Validaciones en `PayrollService` antes de crear planilla: IBC en rango, tipo cotizante válido, entidades activas, clase ARL asignada. |
| **Estados de planilla con transiciones claras** (todos) | ALTA | Máquina de estados: PENDING → SETTLED → SENT_TO_CLIENT → PAID / OVERDUE. Cada transición deja traza. |
| **Histórico y certificados** (SOI) | MEDIA | Planillas con `calculation_metadata` (JSON) que registre los parámetros usados; descarga de resumen PDF. |
| **Generación batch** (Enlace Operativo) | MEDIA | Comando artisan + botón en UI "Generar planillas del mes" para todos los afiliados activos. |
| **Corrección de planillas** (Mi Planilla, planilla N) | BAJA (post-MVP) | Tipo planilla N para correcciones; referencia a planilla original. |
| **Multi-aportante** (Mi Planilla) | BAJA (post-MVP) | Un afiliado con múltiples contratos que debe reportar todo en una sola planilla. |

### 1.3 Diferenciador de Serviconli

Serviconli no es un operador PILA: es una **plataforma de administración de pagos de seguridad social** para intermediarios que gestionan independientes y contratistas. El valor diferencial está en:

- **Gestión de vencimientos y alertas** (saber "qué debo hacer hoy").
- **Trazabilidad completa** (quién hizo qué, cuándo, con qué valores).
- **Administración multi-pagador** (varios NITs con sus afiliados).
- **Acompañamiento operativo** (novedades, credenciales, soportes).

---

## 2. Marco normativo vigente (2026)

### 2.1 Valores base 2026

| Concepto | Valor | Fuente |
|----------|-------|--------|
| **SMLMV** | $1.750.905 | Decreto 1469/2025 |
| **Auxilio de transporte** | $249.095 | Decreto 1469/2025 |
| **UVT** | $52.374 | Resolución DIAN 000238/2025 |
| **IBC mínimo** | $1.750.905 (1 SMLMV) | — |
| **IBC máximo** | $43.772.625 (25 SMLMV) | — |
| **Umbral pilar contributivo** | 2,3 SMLMV = $4.027.082 | Ley 2381/2024 |

### 2.2 Porcentajes de aportes vigentes

#### Salud (12,5% total)

| Componente | Porcentaje | Quién paga |
|-----------|-----------|------------|
| Empleador | 8,5% | Empresa |
| Empleado | 4,0% | Trabajador |
| **Independiente** | **12,5%** | **Cotizante** |

#### Pensión (16% total)

| Componente | Porcentaje | Quién paga |
|-----------|-----------|------------|
| Empleador | 12,0% | Empresa |
| Empleado | 4,0% | Trabajador |
| **Independiente** | **16,0%** | **Cotizante** |

**Distribución post-reforma pensional (Ley 2381/2024):**
- IBC ≤ 2,3 SMLMV → 100% a Colpensiones (Prima Media)
- IBC > 2,3 SMLMV → 16% de los primeros 2,3 SMLMV a Colpensiones + 13,2% del excedente a ACCAI seleccionada

#### ARL (100% empleador; 100% cotizante para independientes)

| Clase | Riesgo | Tarifa |
|-------|--------|--------|
| I | Mínimo | 0,522% |
| II | Bajo | 1,044% |
| III | Medio | 2,436% |
| IV | Alto | 4,350% |
| V | Máximo | 6,960% |

#### Parafiscales (solo para empleadores que apliquen)

| Concepto | Porcentaje |
|----------|-----------|
| SENA | 2,0% |
| ICBF | 3,0% |
| CCF (Caja de Compensación) | 4,0% |

#### Fondo de Solidaridad Pensional (FSP) — Aporte adicional

| Rango salarial (SMLMV) | Porcentaje adicional |
|------------------------|---------------------|
| 4 a < 16 | 1,0% |
| 16 a < 17 | 1,2% |
| 17 a < 18 | 1,4% |
| 18 a < 19 | 1,6% |
| 19 a < 20 | 1,8% |
| ≥ 20 | 2,0% |

### 2.3 Tipos de cotizante PILA (códigos principales)

| Código | Descripción | Relevancia para Serviconli |
|--------|-------------|---------------------------|
| 01 | Dependiente | Empleados bajo contrato laboral |
| 02 | Servicio doméstico | Empleados de servicio doméstico |
| 03 | Independiente | Independiente por cuenta propia |
| 12 | Aprendiz SENA etapa lectiva | Solo salud |
| 19 | Aprendiz SENA etapa productiva | Salud + ARL |
| 23 | Estudiantes (decreto ley 055/2015) | — |
| 40 | Beneficiario UPC adicional | — |
| 51 | Independiente con aportes flexibles por semanas (Circular 093/2025) | Cotización proporcional |
| 57 | Independiente voluntario | Solo pensión voluntaria |
| 59 | Independiente con contrato de prestación de servicios | A partir de jul/2025, empresa asume cálculo y pago |

### 2.4 Tipos de planilla PILA

| Código | Tipo | Uso |
|--------|------|-----|
| E | Planilla empleados | Empresas con nómina |
| I | Planilla independientes | Independientes por cuenta propia |
| S | Servicio doméstico | Empleadores de servicio doméstico |
| Y | Planilla independiente empresarial | Empresas que pagan por contratistas |
| N | Correcciones | Corrección de planilla ya radicada |
| A | Mora con intereses | Pago extemporáneo |

### 2.5 Reforma pensional (Ley 2381/2024)

Cambios con impacto directo en el módulo:

1. **Componentes del pilar contributivo:** Prima Media (Colpensiones) + Ahorro Individual (ACCAI).
2. **AFP → ACCAI:** Las AFP se transforman en Administradoras del Componente Complementario de Ahorro Individual: Porvenir, Protección, Colfondos, Skandia.
3. **Distribución de aportes a pensión:** Depende del nivel de IBC vs 2,3 SMLMV.
4. **Tipo cotizante 59 (contratistas):** A partir de julio 2025, el contratante asume el cálculo del IBC y el descuento/pago de aportes del contratista.
5. **Tipo cotizante 51:** Aportes flexibles por semanas para independientes con ingresos < 1 SMLMV.

**Implicación en el sistema:** El `PayrollService` debe considerar el tipo de cotizante para determinar quién paga (independiente vs empresa) y cómo se distribuye la pensión.

### 2.6 Calendario PILA — Tabla de vencimiento

| Últimos 2 dígitos del documento | Día hábil de vencimiento |
|---------------------------------|-------------------------|
| 00–07 | 2 |
| 08–14 | 3 |
| 15–21 | 4 |
| 22–28 | 5 |
| 29–35 | 6 |
| 36–42 | 7 |
| 43–49 | 8 |
| 50–56 | 9 |
| 57–63 | 10 |
| 64–69 | 11 |
| 70–75 | 12 |
| 76–81 | 13 |
| 82–87 | 14 |
| 88–93 | 15 |
| 94–99 | 16 |

Ya implementado en `DueDateCalculator`.

---

## 3. Principios de diseño del módulo

### 3.1 Capas y responsabilidades

| Capa | Responsabilidad | No debe |
|------|-----------------|--------|
| **Controller** | HTTP: validar request, llamar al servicio, devolver respuesta (Inertia/JSON/redirect). | Contener lógica de negocio, cálculos, reglas PILA. |
| **Service** | Lógica de negocio: cálculos de planilla, vencimientos, transiciones de estado, creación de novedades. Usar modelos y parámetros de BD. | Acceder a `Request`; asumir valores hardcodeados. |
| **Model** | Datos, relaciones, scopes, accessors. Validaciones de integridad (ej. unique). | Contener reglas de negocio complejas (delegar a Service). |
| **FormRequest** | Validación de entrada HTTP. Reglas alineadas con catálogos y rangos oficiales. | Contener lógica de negocio ni acceder a servicios. |
| **Resource** | Transformación modelo → array para API/Inertia. Sin lógica de negocio. | Realizar consultas ni cálculos. |
| **Repository** (opcional) | Consultas complejas reutilizables (listados con muchos filtros, reportes). | Sustituir a Eloquent en flujos simples. |

- **Una entrada por caso de uso:** cada acción (crear planilla, liquidar, marcar pagada) tiene un método en un **Service**; el Controller solo orquesta.
- **Parámetros en BD:** porcentajes de aportes (salud, pensión, ARL, CCF, parafiscales, FSP) y topes (IBC min/max, SMLMV, UVT) viven en tablas con **vigencia** (valid_from/valid_to). El código no contiene constantes de porcentajes.
- **Trazabilidad:** cambios importantes (estado de planilla, cambios de perfil SS) generan registros en `novelties`, `payroll_trackings` o `communication_logs`.

### 3.2 Estructura de carpetas del módulo

```
app/Modules/SocialSecurity/
├── Controllers/
│   ├── PayerController.php          # Ya existe
│   ├── NoveltyController.php        # Ya existe
│   ├── PayrollController.php        # Por implementar
│   ├── DashboardController.php      # Por implementar
│   └── ContributionParameterController.php  # Por implementar (CRUD admin)
├── Services/
│   ├── DueDateCalculator.php        # Ya existe ✓
│   ├── PayrollService.php           # Por implementar (CRÍTICO)
│   ├── ContributionCalculator.php   # Por implementar (lógica de cálculo pura)
│   ├── PayrollBatchService.php      # Por implementar (generación masiva)
│   └── ImportService.php            # Opcional Fase 1: DataSegura
├── Models/                          # Todos ya existen ✓
│   ├── SocialSecurityProfile.php
│   ├── Payer.php
│   ├── Novelty.php
│   ├── Payroll.php
│   ├── OperatorCredential.php
│   ├── SupportDocument.php
│   ├── CommunicationLog.php
│   ├── PayrollTracking.php
│   ├── ContributionParameter.php    # Por implementar (CRÍTICO)
│   ├── Afp.php, Arp.php, Ccf.php   # Catálogos ✓
│   ├── PaymentOperator.php          # ✓
│   ├── AccountingRegistry.php       # ✓
│   ├── ClientType.php               # ✓
│   ├── ContributorType.php          # ✓
│   └── NoveltyType.php              # ✓
├── Enums/
│   ├── PayrollStatus.php            # Ya existe ✓
│   ├── ArlRiskClass.php             # Por implementar
│   └── ContributionType.php         # Por implementar
├── Requests/
│   ├── StorePayerRequest.php        # ✓
│   ├── UpdatePayerRequest.php       # ✓
│   ├── StoreNoveltyRequest.php      # ✓
│   ├── StorePayrollRequest.php      # Por implementar
│   └── StoreContributionParameterRequest.php  # Por implementar
├── Resources/
│   ├── PayerResource.php            # ✓
│   ├── PayrollResource.php          # Por implementar
│   └── ContributionParameterResource.php  # Por implementar
├── routes.php                       # ✓ (parcial)
└── Events/                          # Opcional
    └── PayrollStatusChanged.php     # Para listeners futuros
```

### 3.3 Patrones a seguir

#### Patrón 1: Servicio por dominio con separación de cálculo

```
PayrollService          → Orquesta: crea planilla, gestiona estados, coordina cálculo
ContributionCalculator  → Cálculo puro: recibe IBC + parámetros → devuelve montos
DueDateCalculator       → Fechas: recibe documento/período → devuelve fecha vencimiento
```

Separar el cálculo puro (`ContributionCalculator`) del servicio que orquesta (`PayrollService`) permite:
- Testear los cálculos unitariamente sin BD ni dependencias.
- Reutilizar la lógica de cálculo en otros contextos (simulación, reportes, correcciones).
- Aislar el impacto de cambios normativos en un solo punto.

#### Patrón 2: Parámetros con vigencia (inspirado en cómo los operadores PILA manejan reformas)

```php
// Tabla contribution_parameters: type, subtype, value, valid_from, valid_to, metadata
// Modelo con scope:
ContributionParameter::validForDate($date)->where('type', 'HEALTH_TOTAL')->first();
```

Los operadores PILA (SOI, Mi Planilla) manejan los cambios normativos actualizando tablas de parámetros sin tocar código. Serviconli debe replicar este patrón.

#### Patrón 3: Máquina de estados con trazabilidad (inspirado en Aportes en Línea)

Aportes en Línea maneja estados de planilla con filtros claros y transiciones auditadas. Replicar:

```
PENDING ──(liquidar)──→ SETTLED ──(enviar)──→ SENT_TO_CLIENT ──(pagar)──→ PAID
   │                       │                        │
   └──(vencimiento)──→ OVERDUE ←──(vencimiento)─────┘
```

Cada transición genera un registro en `payroll_trackings` con: usuario, timestamp, estado anterior, estado nuevo, metadata (parámetros usados si aplica).

#### Patrón 4: DTO de cálculo para trazabilidad

```php
class ContributionBreakdown
{
    public readonly float $ibc;
    public readonly float $healthTotal;
    public readonly float $healthEmployer;
    public readonly float $healthEmployee;
    public readonly float $pensionTotal;
    public readonly float $pensionEmployer;
    public readonly float $pensionEmployee;
    public readonly float $arlAmount;
    public readonly int $arlRiskClass;
    public readonly float $ccfAmount;
    public readonly float $senaAmount;
    public readonly float $icbfAmount;
    public readonly float $fspAmount;
    public readonly float $totalEmployer;
    public readonly float $totalEmployee;
    public readonly float $grandTotal;
    public readonly array $parametersUsed; // Para auditoría
    public readonly string $periodDate;    // Fecha del período calculado
}
```

Este DTO se usa para:
1. Mostrar desglose en la UI antes de confirmar.
2. Guardar en `calculation_metadata` (JSON) de la planilla para auditoría.
3. Reproducir exactamente el cálculo en el futuro.

#### Patrón 5: Eventos internos para efectos secundarios

```php
// Al marcar planilla como PAID:
event(new PayrollStatusChanged($payroll, 'SETTLED', 'PAID', $user));

// Listeners:
// - LogPayrollStatusChange → inserta en payroll_trackings
// - NotifyPayrollPaid → (futuro) envía notificación
```

Evita inflar el Service con efectos secundarios.

---

## 4. Estado actual (lo que ya está hecho)

| Componente | Estado | Notas |
|------------|--------|-------|
| Fase 0 (Affiliates + perfil SS) | **Hecho** | Tablas `affiliates`, `social_security_profiles`, `payers`; catálogos con FKs en perfil. |
| Catálogos (AFP, ARP, CCF, operadores, etc.) | **Hecho** | Tablas, modelos, AdminConfig CRUD, seeders. |
| DueDateCalculator | **Hecho** | Día hábil PILA según documento; usa `colombian_holidays`. Incluye tabla oficial 00–99 → día 2–16. |
| PayerController + Vue Payers | **Hecho** | CRUD pagadores; listado con búsqueda y filtro activo. |
| Perfil SS en Affiliates | **Hecho** | Create/Edit/Show afiliado con perfil SS completo; próximo vencimiento PILA en Show. |
| Novelties | **Hecho** | Registro desde ficha afiliado; listado en Show. |
| Modelos Payroll, Novelty, etc. | **Hecho** | Migraciones y relaciones creadas. |
| PayrollStatus enum | **Hecho** | PENDING, SETTLED, SENT_TO_CLIENT, PAID, OVERDUE. |
| **Parametrización de aportes** | **PENDIENTE** | No existe tabla `contribution_parameters`. Porcentajes no están en BD. |
| **ContributionCalculator** | **PENDIENTE** | No existe servicio de cálculo de aportes. |
| **PayrollService** | **PENDIENTE** | No existe; cálculos de montos y creación de planilla no implementados. |
| **PayrollController + Vue Planillas** | **PENDIENTE** | No hay listado, detalle ni transiciones de estado. |
| **Dashboard SS** | **PENDIENTE** | No hay página ni controlador con métricas y vencimientos. |
| Rutas SS centralizadas | **Parcial** | Solo `payers` y `novelties` en rutas. |
| ImportService DataSegura | **PENDIENTE** | Opcional para cierre Fase 1. |

---

## 5. Diseño detallado de la parametrización de aportes

### 5.1 Tabla `contribution_parameters`

Esta es la tabla más crítica del módulo. Debe almacenar todos los valores normativos que cambian con el tiempo.

```sql
CREATE TABLE contribution_parameters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,          -- Categoría: HEALTH, PENSION, ARL, CCF, SENA, ICBF, FSP, SYSTEM
    subtype VARCHAR(50) NOT NULL,       -- Subcategoría: TOTAL, EMPLOYER, EMPLOYEE, RISK_1..5, THRESHOLD_4_16, etc.
    value DECIMAL(12,4) NOT NULL,       -- Valor (porcentaje como decimal, ej. 12.5 para 12.5%; o monto absoluto)
    value_type VARCHAR(20) NOT NULL DEFAULT 'PERCENTAGE', -- PERCENTAGE | AMOUNT | MULTIPLIER
    valid_from DATE NOT NULL,           -- Inicio de vigencia
    valid_to DATE NULL,                 -- Fin de vigencia (NULL = vigente indefinidamente)
    description VARCHAR(255) NULL,      -- Descripción legible
    legal_reference VARCHAR(255) NULL,  -- Referencia normativa (ej. "Decreto 1469/2025")
    metadata JSON NULL,                 -- Datos adicionales si se requieren
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_type_subtype (type, subtype),
    INDEX idx_valid_from (valid_from),
    INDEX idx_valid_to (valid_to)
);
```

### 5.2 Tipos y subtipos a cargar en el seeder

```
type=HEALTH         subtype=TOTAL            value=12.5     value_type=PERCENTAGE
type=HEALTH         subtype=EMPLOYER         value=8.5      value_type=PERCENTAGE
type=HEALTH         subtype=EMPLOYEE         value=4.0      value_type=PERCENTAGE
type=HEALTH         subtype=INDEPENDENT      value=12.5     value_type=PERCENTAGE

type=PENSION        subtype=TOTAL            value=16.0     value_type=PERCENTAGE
type=PENSION        subtype=EMPLOYER         value=12.0     value_type=PERCENTAGE
type=PENSION        subtype=EMPLOYEE         value=4.0      value_type=PERCENTAGE
type=PENSION        subtype=INDEPENDENT      value=16.0     value_type=PERCENTAGE

type=ARL            subtype=RISK_1           value=0.522    value_type=PERCENTAGE
type=ARL            subtype=RISK_2           value=1.044    value_type=PERCENTAGE
type=ARL            subtype=RISK_3           value=2.436    value_type=PERCENTAGE
type=ARL            subtype=RISK_4           value=4.350    value_type=PERCENTAGE
type=ARL            subtype=RISK_5           value=6.960    value_type=PERCENTAGE

type=CCF            subtype=TOTAL            value=4.0      value_type=PERCENTAGE
type=SENA           subtype=TOTAL            value=2.0      value_type=PERCENTAGE
type=ICBF           subtype=TOTAL            value=3.0      value_type=PERCENTAGE

type=FSP            subtype=THRESHOLD_4_16   value=1.0      value_type=PERCENTAGE
type=FSP            subtype=THRESHOLD_16_17  value=1.2      value_type=PERCENTAGE
type=FSP            subtype=THRESHOLD_17_18  value=1.4      value_type=PERCENTAGE
type=FSP            subtype=THRESHOLD_18_19  value=1.6      value_type=PERCENTAGE
type=FSP            subtype=THRESHOLD_19_20  value=1.8      value_type=PERCENTAGE
type=FSP            subtype=THRESHOLD_20_PLUS value=2.0     value_type=PERCENTAGE

type=SYSTEM         subtype=SMLMV            value=1750905  value_type=AMOUNT
type=SYSTEM         subtype=TRANSPORT_AID    value=249095   value_type=AMOUNT
type=SYSTEM         subtype=UVT              value=52374    value_type=AMOUNT
type=SYSTEM         subtype=IBC_MIN_SMLMV    value=1        value_type=MULTIPLIER
type=SYSTEM         subtype=IBC_MAX_SMLMV    value=25       value_type=MULTIPLIER
type=SYSTEM         subtype=PENSION_PILLAR_THRESHOLD_SMLMV value=2.3 value_type=MULTIPLIER
```

Todos con `valid_from = '2026-01-01'`, `valid_to = NULL`, `legal_reference` apropiada.

### 5.3 Modelo `ContributionParameter`

```php
class ContributionParameter extends Model
{
    protected $fillable = [
        'type', 'subtype', 'value', 'value_type',
        'valid_from', 'valid_to', 'description',
        'legal_reference', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'metadata' => 'array',
        ];
    }

    /**
     * Scope: parámetros vigentes para una fecha dada.
     */
    public function scopeValidForDate($query, $date)
    {
        return $query
            ->where('valid_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date));
    }

    /**
     * Obtener un valor específico vigente.
     */
    public static function getValueForDate(string $type, string $subtype, $date): ?float
    {
        $param = static::validForDate($date)
            ->where('type', $type)
            ->where('subtype', $subtype)
            ->first();

        return $param?->value;
    }

    /**
     * Obtener todos los parámetros vigentes agrupados por tipo.
     */
    public static function getAllValidForDate($date): array
    {
        return static::validForDate($date)
            ->get()
            ->groupBy('type')
            ->map(fn ($items) => $items->keyBy('subtype'))
            ->toArray();
    }
}
```

---

## 6. Diseño del ContributionCalculator

### 6.1 Responsabilidad

Cálculo **puro** de aportes. Recibe datos ya validados y devuelve el desglose de montos. No accede a BD directamente, recibe los parámetros como argumento.

### 6.2 Lógica de cálculo

```php
class ContributionCalculator
{
    /**
     * Calcula el desglose de aportes para un afiliado dado su perfil y parámetros vigentes.
     *
     * @param float  $ibc              Ingreso Base de Cotización
     * @param int    $arlRiskClass     Clase de riesgo ARL (1-5, 0 si no aplica)
     * @param string $contributorCode  Código tipo cotizante PILA (01, 03, 59, etc.)
     * @param bool   $hasParafiscales  Si el empleador paga parafiscales
     * @param array  $params           Parámetros vigentes de contribution_parameters
     * @param float  $smlmv            Salario mínimo vigente
     */
    public function calculate(
        float $ibc,
        int $arlRiskClass,
        string $contributorCode,
        bool $hasParafiscales,
        array $params,
        float $smlmv
    ): ContributionBreakdown {
        // 1. Validar IBC en rango
        // 2. Determinar si es independiente (03, 51, 57, 59) o dependiente (01, 02)
        // 3. Calcular salud según quién paga
        // 4. Calcular pensión según quién paga
        // 5. Calcular ARL según clase de riesgo
        // 6. Calcular CCF si aplica
        // 7. Calcular parafiscales (SENA + ICBF) si has_parafiscales
        // 8. Calcular FSP si IBC >= 4 SMLMV
        // 9. Retornar DTO con desglose y parámetros usados
    }

    /**
     * Determina el porcentaje de FSP según el número de SMLMV que representa el IBC.
     */
    private function fspPercentage(float $ibc, float $smlmv, array $params): float
    {
        $smlmvCount = $ibc / $smlmv;

        if ($smlmvCount < 4) return 0;
        if ($smlmvCount < 16) return $params['FSP']['THRESHOLD_4_16'] ?? 1.0;
        if ($smlmvCount < 17) return $params['FSP']['THRESHOLD_16_17'] ?? 1.2;
        if ($smlmvCount < 18) return $params['FSP']['THRESHOLD_17_18'] ?? 1.4;
        if ($smlmvCount < 19) return $params['FSP']['THRESHOLD_18_19'] ?? 1.6;
        if ($smlmvCount < 20) return $params['FSP']['THRESHOLD_19_20'] ?? 1.8;

        return $params['FSP']['THRESHOLD_20_PLUS'] ?? 2.0;
    }
}
```

### 6.3 Reglas de negocio del cálculo según tipo de cotizante

| Tipo cotizante | Salud | Pensión | ARL | CCF | Parafiscales | FSP |
|---------------|-------|---------|-----|-----|-------------|-----|
| 01 (Dependiente) | 12,5% (split empleador/empleado) | 16% (split empleador/empleado) | Según clase (empleador) | 4% (empleador) | Si aplica (empleador) | Si IBC ≥ 4 SMLMV |
| 02 (S. doméstico) | 12,5% (split) | 16% (split) | Según clase (empleador) | 4% (empleador) | Exento | Si IBC ≥ 4 SMLMV |
| 03 (Independiente) | 12,5% (cotizante) | 16% (cotizante) | Voluntario, según clase | No aplica | No aplica | Si IBC ≥ 4 SMLMV |
| 51 (Flexible) | 12,5% proporcional | 16% proporcional | Voluntario | No aplica | No aplica | No |
| 59 (Contratista CPS) | 12,5% (empresa descuenta) | 16% (empresa descuenta) | Según clase (empresa) | No aplica directo | No aplica directo | Si IBC ≥ 4 SMLMV |

**Nota sobre tipo 59 post-reforma:** A partir de julio 2025, la empresa contratante calcula el IBC del contratista (40% del valor del contrato, mínimo 1 SMLMV) y descuenta los aportes de los honorarios antes de pagar. Serviconli debe soportar este flujo ya que es el caso más común para sus clientes.

---

## 7. Diseño del PayrollService

### 7.1 Interfaz pública

```php
class PayrollService
{
    public function __construct(
        private ContributionCalculator $calculator,
        private DueDateCalculator $dueDateCalculator
    ) {}

    /**
     * Obtiene o crea la planilla de un afiliado para un período.
     * Si no existe, la crea con estado PENDING y calcula due_date.
     */
    public function getOrCreatePayroll(Affiliate $affiliate, int $year, int $month): Payroll;

    /**
     * Calcula los montos de la planilla usando los parámetros vigentes para el período.
     * Guarda los montos y registra los parámetros usados en calculation_metadata.
     * Transiciona estado a SETTLED si estaba PENDING.
     */
    public function settle(Payroll $payroll): ContributionBreakdown;

    /**
     * Pre-calcula montos sin guardar (para preview/simulación en UI).
     */
    public function preview(SocialSecurityProfile $profile, int $year, int $month): ContributionBreakdown;

    /**
     * Transiciona el estado de la planilla y registra en payroll_trackings.
     */
    public function transitionStatus(Payroll $payroll, PayrollStatus $newStatus, ?User $user = null): void;

    /**
     * Marca planillas vencidas como OVERDUE.
     * Ejecutar diariamente vía scheduler.
     */
    public function markOverduePayrolls(): int;

    /**
     * Valida que el perfil del afiliado tenga los datos mínimos para generar planilla.
     * Retorna array de errores (vacío = válido).
     */
    public function validateProfileForPayroll(SocialSecurityProfile $profile): array;
}
```

### 7.2 Pre-validaciones antes de liquidar (inspirado en operadores PILA)

Antes de crear o liquidar una planilla, validar:

1. **IBC en rango:** $1.750.905 ≤ IBC ≤ $43.772.625 (1–25 SMLMV vigente).
2. **Tipo cotizante asignado:** No puede estar vacío.
3. **Clase ARL asignada:** Si el tipo cotizante requiere ARL (01, 02, 03, 59), debe tener clase 1–5.
4. **EPS activa:** Si el tipo cotizante requiere salud, debe tener EPS asignada y activa.
5. **AFP/ACCAI:** Si el tipo cotizante cotiza a pensión, debe tener AFP/ACCAI asignada.
6. **Parámetros vigentes:** Deben existir parámetros de tipo HEALTH, PENSION, ARL en BD para la fecha del período.
7. **No duplicidad:** No puede existir planilla con estado PAID para el mismo afiliado/año/mes.

### 7.3 Flujo de liquidación

```
1. Operador selecciona afiliado + período (año/mes) en la UI
2. Frontend llama POST /payrolls/preview → PayrollService::preview()
3. UI muestra desglose de montos (sin guardar) → operador revisa
4. Operador confirma → POST /payrolls → PayrollService::getOrCreatePayroll() + settle()
5. Planilla queda en estado SETTLED con montos y calculation_metadata
6. Operador envía al cliente → POST /payrolls/{id}/send → transitionStatus(SENT_TO_CLIENT)
7. Cliente paga → POST /payrolls/{id}/pay → transitionStatus(PAID), registra paid_at
```

### 7.4 Campo `calculation_metadata` en planilla

Agregar columna `calculation_metadata JSON NULL` a la tabla `payrolls` (migración nueva). Este campo almacena un snapshot de los parámetros usados en el cálculo:

```json
{
    "calculated_at": "2026-01-28T10:30:00Z",
    "calculated_by": 5,
    "ibc": 3500000,
    "contributor_type": "03",
    "arl_risk_class": 1,
    "smlmv": 1750905,
    "parameters": {
        "health_rate": 12.5,
        "pension_rate": 16.0,
        "arl_rate": 0.522,
        "ccf_rate": 0,
        "fsp_rate": 0
    },
    "breakdown": {
        "health_total": 437500,
        "pension_total": 560000,
        "arl_total": 18270,
        "ccf_total": 0,
        "fsp_total": 0,
        "grand_total": 1015770
    }
}
```

Esto permite:
- Auditar exactamente cómo se calculó cada planilla.
- No recalcular con reglas nuevas para períodos antiguos.
- Responder a fiscalizaciones de la UGPP con datos precisos.

---

## 8. Diseño de la UI de Planillas (inspirado en Aportes en Línea)

### 8.1 Listado de Planillas (Index)

Inspirado en Aportes en Línea que permite filtrar por estado (sin generar, en proceso, con egreso):

- **Filtros:** Año, Mes, Estado (todos los de `PayrollStatus`), Pagador, Búsqueda por nombre/documento de afiliado.
- **Columnas:** Afiliado (nombre + documento), Pagador, Período (mes/año), Vencimiento, Estado (badge con color), Total aportes, Acciones.
- **Colores de estado:** PENDING (gris), SETTLED (azul), SENT_TO_CLIENT (amarillo), PAID (verde), OVERDUE (rojo).
- **Acciones rápidas:** Ver detalle, Cambiar estado (según flujo permitido).
- **Acción masiva:** "Generar planillas del mes" → `PayrollBatchService`.

### 8.2 Detalle de Planilla (Show)

- **Datos del afiliado:** Nombre, documento, tipo cotizante, pagador.
- **Período:** Mes/año liquidado, fecha de vencimiento PILA.
- **Desglose de aportes:** Tabla con concepto, base (IBC), porcentaje, monto (empleador/empleado/independiente).
- **Estado actual** con indicador visual y fecha del último cambio.
- **Historial de cambios** (payroll_trackings): tabla con fecha, evento, usuario, estado anterior → nuevo.
- **Botones de acción** según estado actual: "Liquidar" (si PENDING), "Marcar enviada" (si SETTLED), "Marcar pagada" (si SENT_TO_CLIENT).
- **Soportes:** Lista de documentos adjuntos con opción de subir.

### 8.3 Simulación / Preview (inspirado en calculadora SOI)

- **Formulario:** Seleccionar afiliado → auto-llenar IBC, tipo cotizante, clase ARL desde perfil.
- **Botón "Simular"** → llama a endpoint preview → muestra desglose sin guardar.
- **Botón "Confirmar y crear planilla"** → crea planilla real con los montos.

---

## 9. Diseño del Dashboard SS (inspirado en operadores de gestión)

### 9.1 Métricas en tarjetas

| Tarjeta | Dato | Color |
|---------|------|-------|
| Afiliados activos con perfil SS | COUNT | Azul |
| Planillas pendientes (PENDING + SETTLED) | COUNT | Amarillo |
| Planillas en mora (OVERDUE) | COUNT | Rojo |
| Planillas pagadas este mes | COUNT | Verde |
| Monto total en mora | SUM(total) WHERE OVERDUE | Rojo |

### 9.2 Listas operativas

- **"Vencimientos hoy":** Planillas cuyo `due_date` es hoy y estado ≠ PAID. Columnas: afiliado, pagador, monto, estado. Link a detalle.
- **"Próximos 7 días":** Planillas con `due_date` en los próximos 7 días y estado ≠ PAID. Mismas columnas.
- **"En mora":** Planillas OVERDUE ordenadas por antigüedad (due_date más antigua primero).

### 9.3 Accesos rápidos

- Crear planilla → Formulario de nueva planilla
- Ver afiliados → Listado de afiliados
- Ver pagadores → Listado de pagadores
- Generar planillas del mes → Batch

---

## 10. Orden recomendado de implementación

### Paso 1 — Parametrización de aportes (BASE PARA TODO LO DEMÁS)

**Prioridad: CRÍTICA. Sin esto, los cálculos no pueden hacerse.**

| # | Tarea | Criterio de aceptación |
|---|-------|------------------------|
| 1.1 | Crear migración `create_contribution_parameters_table` con estructura de §5.1 | Tabla creada sin error |
| 1.2 | Crear modelo `ContributionParameter` con scope `validForDate` y métodos `getValueForDate`, `getAllValidForDate` | Modelo funcional con tests manuales |
| 1.3 | Crear `ContributionParameterSeeder` con todos los valores de §5.2, con `valid_from = '2026-01-01'` y referencias legales | Después de seed, hay ~30 registros con tipos HEALTH, PENSION, ARL, CCF, SENA, ICBF, FSP, SYSTEM |
| 1.4 | Crear migración para agregar `calculation_metadata JSON NULL` y `parafiscal_amount DECIMAL(12,2) NULL`, `fsp_amount DECIMAL(12,2) NULL`, `total_amount DECIMAL(12,2) NULL` a tabla `payrolls` | Planillas pueden almacenar metadata y montos adicionales |
| 1.5 | Agregar CRUD admin para `contribution_parameters` (en Admin/Config o ruta dedicada) | Admin puede ver, crear, editar parámetros con vigencia |

### Paso 2 — ContributionCalculator + PayrollService

**Prioridad: CRÍTICA. Core de la lógica de negocio.**

| # | Tarea | Criterio de aceptación |
|---|-------|------------------------|
| 2.1 | Crear enum `ContributionType` (HEALTH, PENSION, ARL, CCF, SENA, ICBF, FSP, SYSTEM) y `ArlRiskClass` (RISK_1..5) | Enums definidos y usables |
| 2.2 | Crear DTO `ContributionBreakdown` según §6.2 | Clase inmutable con todos los campos |
| 2.3 | Implementar `ContributionCalculator::calculate()` según §6.2 y §6.3 | Dado un IBC de $3.500.000, tipo 03, ARL clase 1, sin parafiscales → salud $437.500, pensión $560.000, ARL $18.270, total $1.015.770 |
| 2.4 | Implementar `PayrollService::getOrCreatePayroll()` | Crea planilla con due_date correcta, estado PENDING, sin montos |
| 2.5 | Implementar `PayrollService::preview()` | Retorna `ContributionBreakdown` sin guardar en BD |
| 2.6 | Implementar `PayrollService::settle()` | Guarda montos en planilla, `calculation_metadata` con snapshot, transiciona a SETTLED |
| 2.7 | Implementar `PayrollService::transitionStatus()` | Cambia estado, registra en `payroll_trackings`, valida transiciones permitidas |
| 2.8 | Implementar `PayrollService::validateProfileForPayroll()` | Valida IBC, tipo cotizante, ARL, EPS, AFP según §7.2 |
| 2.9 | Implementar `PayrollService::markOverduePayrolls()` | Marca planillas vencidas como OVERDUE; preparar para scheduler |

### Paso 3 — PayrollController + Vue Planillas

| # | Tarea | Criterio de aceptación |
|---|-------|------------------------|
| 3.1 | Crear `PayrollController` con acciones: index, show, store, preview, markSettled, markSent, markPaid | Rutas funcionales en `SocialSecurity/routes.php` |
| 3.2 | Crear `PayrollResource` para transformación API/Inertia | Incluye affiliate, período, montos, estado, metadata |
| 3.3 | Crear Vue `Payrolls/Index.vue` con filtros (año, mes, estado, pagador, búsqueda) y listado según §8.1 | Listado funcional con paginación y filtros |
| 3.4 | Crear Vue `Payrolls/Show.vue` con desglose de aportes, historial de estados, acciones según §8.2 | Detalle completo con botones de transición |
| 3.5 | Crear Vue `Payrolls/Create.vue` con simulación/preview según §8.3 | Flujo: seleccionar afiliado → preview → confirmar |
| 3.6 | Agregar rutas a `SocialSecurity/routes.php` bajo middleware `auth` + `role:seguridad_social,admin` | Rutas protegidas y funcionales |
| 3.7 | Agregar enlace "Planillas" en el menú/layout para roles autorizados | Visible solo para roles SS/admin |

### Paso 4 — Dashboard Seguridad Social

| # | Tarea | Criterio de aceptación |
|---|-------|------------------------|
| 4.1 | Crear `DashboardController` con métricas según §9.1 | Retorna conteos y listas a Inertia |
| 4.2 | Crear Vue `SocialSecurity/Dashboard.vue` con tarjetas y listas según §9.1–§9.3 | Dashboard funcional con datos reales |
| 4.3 | Agregar ruta y enlace en menú | Solo visible para roles autorizados |

### Paso 5 — Batch y scheduler

| # | Tarea | Criterio de aceptación |
|---|-------|------------------------|
| 5.1 | Implementar `PayrollBatchService::generateMonthlyPayrolls()` | Crea planillas para todos los afiliados activos con perfil SS válido |
| 5.2 | Crear comando artisan `payroll:generate-monthly {--year=} {--month=}` | Ejecutable desde terminal y scheduler |
| 5.3 | Crear comando artisan `payroll:mark-overdue` | Marca planillas vencidas como OVERDUE; programar en scheduler diario |
| 5.4 | Botón en Dashboard "Generar planillas del mes" que ejecute el batch | Feedback visual de resultado (N creadas, N omitidas, errores) |

### Paso 6 (opcional) — Cierre Fase 1

| # | Tarea | Criterio de aceptación |
|---|-------|------------------------|
| 6.1 | ImportService DataSegura según mapeo | Excel de prueba importa sin errores |
| 6.2 | Revisión de roles/middleware en todas las rutas SS | 403 para usuarios sin rol |
| 6.3 | Prueba E2E completa: afiliado → perfil SS → planilla → liquidar → pagar → dashboard | Flujo sin errores |

---

## 11. Consideraciones de seguridad y cumplimiento

### 11.1 Protección de datos

- **Credenciales de operadores:** Siempre cifradas con `Laravel Crypt`. No se exponen en logs ni en respuestas API.
- **IBC y montos:** Datos sensibles; acceso solo para roles autorizados.
- **Auditoría:** Cada acceso a datos de SS debe ser trazable (usuario, IP, timestamp).

### 11.2 Integridad de cálculos

- **Inmutabilidad de planillas pagadas:** Una vez en estado PAID, la planilla no se puede modificar. Para correcciones, crear planilla tipo N (post-MVP).
- **Snapshot de parámetros:** Los parámetros usados en el cálculo se guardan en `calculation_metadata`. Si cambian los porcentajes (reforma, decreto), las planillas históricas conservan los valores con los que se calcularon.
- **Validación doble:** Frontend muestra preview, backend re-calcula al confirmar. No confiar en montos enviados desde el frontend.

### 11.3 Preparación para fiscalización UGPP

La UGPP (Unidad de Gestión Pensional y Parafiscales) puede fiscalizar los aportes. El sistema debe poder responder:
- ¿Con qué IBC se liquidó? → Guardado en planilla y en perfil.
- ¿Qué porcentajes se usaron? → En `calculation_metadata`.
- ¿Cuándo se pagó? → `paid_at` en planilla.
- ¿Quién liquidó/pagó? → En `payroll_trackings`.
- ¿Se pagó a tiempo? → Comparar `paid_at` vs `due_date`.

---

## 12. Checklist de buenas prácticas antes de tocar código

- [ ] ¿La lógica de negocio está en un Service (no en Controller ni Model)?
- [ ] ¿Los porcentajes y topes vienen de BD con vigencia (`contribution_parameters`)?
- [ ] ¿Se guardan los parámetros usados en el cálculo (`calculation_metadata`)?
- [ ] ¿Los cambios de estado de planilla dejan trazabilidad (`payroll_trackings`)?
- [ ] ¿Las validaciones de entrada están en FormRequest y alineadas con rangos oficiales?
- [ ] ¿Las rutas del módulo SS están bajo el mismo middleware de rol?
- [ ] ¿Se valida el perfil antes de liquidar (IBC, tipo cotizante, ARL, EPS, AFP)?
- [ ] ¿Las planillas pagadas son inmutables?
- [ ] ¿El cálculo de FSP considera los rangos escalonados por SMLMV?
- [ ] ¿Se consideró la reforma pensional (distribución Colpensiones/ACCAI) para tipo 59?

---

## 13. Resumen

- **Ya tenemos:** Affiliates + perfil SS, catálogos, payers, novedades, DueDateCalculator, modelos de planilla y tablas complementarias, PayrollStatus enum.
- **Falta (orden crítico):**
  1. Parametrización de aportes (`contribution_parameters` + seeder + CRUD admin)
  2. ContributionCalculator + PayrollService (lógica de cálculo y orquestación)
  3. PayrollController + Vue Planillas (CRUD, preview, transiciones)
  4. Dashboard SS (métricas, vencimientos, accesos rápidos)
  5. Batch y scheduler (generación masiva, marcado de mora)
  6. (Opcional) ImportService DataSegura, cierre y prueba E2E
- **Alineado con mercado:** Calculadora de aportes (SOI), pre-validación (Aportes en Línea), estados con trazabilidad (todos), histórico de parámetros (Mi Planilla), generación batch (Enlace Operativo).
- **Alineado con normativa 2026:** SMLMV $1.750.905, reforma pensional Ley 2381/2024, tipos cotizante 51/59, tabla FSP escalonada, clases ARL, calendario PILA.

Seguir este documento y los tres de referencia garantiza una base sólida, auditable y mantenible para el módulo de Seguridad Social. Actualizar este archivo cuando se complete cada paso o cuando cambie la normativa.
