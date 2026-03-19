# PLAN MAESTRO DE IMPLEMENTACIÓN — MÓDULO GESTIÓN PILA

**Versión:** 1.0 | **Fecha:** Marzo 2026 | **Proyecto:** Serviconli  
**Rama base:** `feat/pila-affiliations-versioning`  
**Objetivo:** Reemplazar por completo el archivo Excel `DataSegura SERVICONLI 2025 (1).xlsx` y el aplicativo en Access (`AplicativoV6.accdb`) por un módulo web unificado dentro del sistema Laravel de Serviconli.

---

## TABLA DE CONTENIDOS

1. [Contexto y fuentes a reemplazar](#1-contexto)
2. [Estado actual de la implementación](#2-estado-actual)
3. [Arquitectura objetivo](#3-arquitectura-objetivo)
4. [Plan de implementación por sprints](#4-plan-por-sprints)
5. [Estrategia de migración de datos](#5-migracion-datos)
6. [Consolidación SocialSecurity → PilaManagement](#6-consolidacion)
7. [Reglas de negocio obligatorias](#7-reglas-negocio)
8. [Principios de desarrollo](#8-principios)
9. [Riesgos y mitigaciones](#9-riesgos)
10. [Criterios de aceptación final](#10-criterios)

---

# 1. CONTEXTO Y FUENTES A REEMPLAZAR {#1-contexto}

## 1.1 El Excel — DataSegura SERVICONLI 2025

El archivo `DataSegura SERVICONLI 2025 (1).xlsx` es la herramienta operativa diaria. Concentra:

| Bloque | Columnas | Datos | Destino en Laravel |
|--------|----------|-------|--------------------|
| A | 1–13 | Datos personales del afiliado | `affiliates` |
| B | 14–21 | Empleador / pagador | `pila_employers` |
| C | 22–28 | Operación PILA (operador, credenciales) | `pila_affiliations` + `pila_credentials` |
| D | 29–33 | Datos laborales y ARL | `pila_affiliations` + `portal_credentials` |
| E | 34–36 | Caja de Compensación (CCF) | `pila_affiliations` + `portal_credentials` |
| F | 37–39 | EPS | `pila_affiliations` + `portal_credentials` |
| G | 40–42 | AFP | `pila_affiliations` + `portal_credentials` |
| H | 43–50 | Facturación y seguimiento | `pila_affiliations` + `affiliate_notes` |

**Dimensiones:** 891 filas, 50 columnas útiles, ~2,121 credenciales en texto plano, 5 hojas con datos de referencia.

## 1.2 El Access — AplicativoV6.accdb

El aplicativo Access maneja procesos que el Excel no cubre:

| Entidad Access | Datos | Destino en Laravel |
|----------------|-------|--------------------|
| 001 - Aportante | Empleadores con historial | `pila_employers` (consolidar con Excel) |
| 002 - Datos Generales del Asociado | Afiliados con más detalle | `affiliates` (consolidar con Excel) |
| 004 - Datos de Afiliación Asociado | Afiliaciones operativas | `pila_affiliations` (consolidar con Excel) |
| 019 - Observaciones | Notas operativas | `affiliate_notes` |
| Historial de aportes PILA | Pagos históricos por período | `payrolls` (ya existe en SocialSecurity) |
| Recibos de caja | Cobros y pagos | Nueva tabla `receipts` + `receipt_details` |
| Cuentas de cobro | Facturación a clientes | Nueva tabla `billing_accounts` |
| Movimientos de caja | Flujo de caja | Nueva tabla `cash_movements` |
| Beneficiarios | Dependientes del afiliado | `affiliates` (patient_type = beneficiario) |
| CompruebaOrigen | Transiciones automáticas de pago al cambio de mes | `PaymentStatusService` (job mensual) |

## 1.3 Datos que están solo en Excel (no en Access)

- Credenciales de portales (PILA, ARL, EPS, AFP, CCF)
- Tipo de cliente (SERVICONLI / DEPENDIENTE / INDEPENDIENTE / EXTERIOR)
- Estado de pago actualizado (al día / mora / anticipado)
- Periodicidad de pago y tipo de comprobante
- Último documento y mes de pago

## 1.4 Datos que están solo en Access (no en Excel)

- Historial completo de aportes PILA por período
- Recibos de caja con detalle
- Cuentas de cobro
- Movimientos de caja
- Historial de incapacidades
- Beneficiarios con parentesco

## 1.5 Datos duplicados que requieren conciliación

- Datos personales del afiliado (nombre, documento, dirección)
- Datos del empleador
- Tipo de cotizante y entidades (EPS, AFP, ARL, CCF)
- Salario / IBC

**Regla de conciliación:** El Excel es fuente primaria para datos operativos actuales. El Access es fuente primaria para datos históricos. Los conflictos se resuelven durante la fase de migración.

---

# 2. ESTADO ACTUAL DE LA IMPLEMENTACIÓN {#2-estado-actual}

## 2.1 Módulo PilaManagement (nuevo — parcialmente implementado)

| Componente | Estado | Archivos |
|------------|--------|----------|
| **Modelos** | Implementado | `PilaEmployer`, `PilaAffiliation`, `PilaCotizanteType`, `PilaRiskClass`, `PilaCredential`, `PortalCredential`, `CredentialAuditLog` |
| **Controladores** | Parcial | `PilaEmployerController` (CRUD completo), `PilaAffiliationController` (CRUD completo) |
| **Servicios** | Parcial | `DeadlineService` (completo), `CredentialService` (backend completo), `PilaAffiliationVersioningService` (completo), `PilaAffiliationSyncService` (completo), `PaymentStatusService` (esqueleto) |
| **Form Requests** | Implementado | Store/Update para employers y affiliations |
| **Resources** | Implementado | `PilaEmployerResource`, `PilaAffiliationResource` |
| **Enums** | Implementado | `CredentialAction`, `CredentialKind` |
| **Migraciones** | Implementado | 3 migraciones (tablas core, FKs a catálogos existentes, limpieza legacy) |
| **Tests** | Parcial | `DeadlineServiceTest`, `PilaAffiliationVersioningTest`, `AffiliateServicePilaIntegrationTest` |
| **Frontend** | Implementado | CRUD completo de Employers y Affiliations (Vue/Inertia) |

### Lo que falta en PilaManagement:

| Componente | Estado | Descripción |
|------------|--------|-------------|
| `CredentialController` | No implementado | UI para ver/editar credenciales con auditoría |
| `DashboardController` | No implementado | Panel operativo diario (reemplazo directo del Excel) |
| `ReportController` | No implementado | Exportaciones y reportes |
| `AffiliateImportService` | No implementado | Importación masiva desde Excel |
| `PaymentStatusService` | Esqueleto | Transiciones automáticas de estado de pago |
| Policies | No implementado | Control de acceso granular por rol |
| Seeders PILA | No implementado | Seeders dedicados para `pila_cotizante_types` y `pila_risk_classes` |
| Ficha completa del afiliado | No implementado | Vista con pestañas (datos, empleador, afiliación, credenciales, novedades, notas) |
| Campos de facturación/seguimiento | Parcial | Faltan: `billing_type`, `last_document_number`, `last_payment_period`, `payment_status` en affiliations |

## 2.2 Módulo SocialSecurity (existente — funcional)

| Componente | Estado | Reusable en PilaManagement |
|------------|--------|---------------------------|
| `PayrollService` + `PayrollBatchService` | Funcional | Sí — consumirá datos de `pila_affiliations` |
| `ContributionCalculator` | Funcional | Sí — sin cambios, lee de `ContributionParametersResolver` |
| `ContributionParametersResolver` | Funcional | Sí — parámetros en BD con vigencia |
| `ContributorTypeRules` | Funcional | Sí — reglas desde BD con cache |
| `DueDateCalculator` | Funcional | Consolidar con `DeadlineService` de PilaManagement |
| `IndependentContractIbcService` | Funcional | Sí — para contratos múltiples |
| `Payer` (modelo) | Funcional | Reemplazar por `PilaEmployer` |
| `SocialSecurityProfile` | Funcional | Reemplazar por `PilaAffiliation` (con sync service) |
| `OperatorCredential` | Funcional | Reemplazar por `PilaCredential` + `PortalCredential` |
| Catálogos (`Eps`, `Afp`, `Arp`, `Ccf`, etc.) | Funcional | Reusar tal cual |
| Seeders (EPS, AFP, ARP, CCF, PaymentOperator, etc.) | Funcional | Reusar tal cual |
| `Payroll` (modelo + estados) | Funcional | Reusar, actualizando fuente de datos |
| Novelties | Funcional | Reusar |
| Dashboard SS | Funcional | Base para nuevo dashboard PilaManagement |

## 2.3 Módulo Patients/Affiliates (existente — base)

| Componente | Estado | Notas |
|------------|--------|-------|
| `Affiliate` modelo | Funcional | Ya tiene relación a `pilaAffiliation` y `pilaAffiliations` |
| CRUD Afiliados | Funcional | Formularios ya simplifican SS y redirigen a PilaManagement |
| `AffiliateService` | Refactorizado | No crea PilaAffiliation ni SocialSecurityProfile para cotizantes |
| Form Requests | Refactorizado | Sin reglas SS para cotizantes; EPS solo para beneficiarios |

---

# 3. ARQUITECTURA OBJETIVO {#3-arquitectura-objetivo}

## 3.1 Módulos y sus responsabilidades post-consolidación

```
app/Modules/
├── Patients/               ← Datos personales del afiliado (cotizante + beneficiario)
│   ├── Affiliate CRUD
│   └── AffiliateService (solo datos personales)
│
├── PilaManagement/         ← TODO lo operativo de seguridad social
│   ├── Employers/          ← CRUD empleadores (reemplaza Payers)
│   ├── Affiliations/       ← Datos laborales y entidades SS
│   ├── Credentials/        ← Credenciales cifradas + auditoría
│   ├── Dashboard/          ← Panel operativo diario
│   ├── Payrolls/           ← Planillas y liquidación (migrado desde SocialSecurity)
│   ├── Novelties/          ← Novedades PILA (migrado desde SocialSecurity)
│   ├── Reports/            ← Exportaciones y reportes
│   ├── Import/             ← Importación Excel + Access
│   └── Services/           ← DeadlineService, CredentialService, PaymentStatusService, etc.
│
├── CashManagement/         ← NUEVO: Recibos, cuentas de cobro, movimientos de caja (Access)
│   ├── Receipts/
│   ├── BillingAccounts/
│   └── CashMovements/
│
├── SocialSecurity/         ← DEPRECATED: se migra progresivamente a PilaManagement
│   └── (mantener temporalmente para backward compatibility)
│
├── Appointments/           ← Sin cambios
├── AppointmentRequests/    ← Sin cambios
├── Authorizations/         ← Sin cambios
└── Integrations/           ← WhatsApp, etc.
```

## 3.2 Tablas de base de datos — Estado final

### Tablas nuevas/modificadas de PilaManagement (ya creadas)

| Tabla | Origen | Estado |
|-------|--------|--------|
| `pila_employers` | Excel bloque B + Access 001 | Migración creada |
| `pila_affiliations` | Excel bloques C-H + Access 004 | Migración creada (ampliar campos) |
| `pila_credentials` | Excel cols 27-28 | Migración creada |
| `portal_credentials` | Excel cols 32-42 | Migración creada |
| `affiliate_notes` | Excel cols 44, 50 + Access 019 | Migración creada |
| `pila_cotizante_types` | Excel hoja 2 | Migración creada (falta seeder) |
| `pila_risk_classes` | Excel hoja 4 | Migración creada (falta seeder) |
| `credential_audit_logs` | Auditoría nueva | Migración creada |

### Tablas existentes que se reutilizan

| Tabla | Módulo actual | Uso en PilaManagement |
|-------|---------------|----------------------|
| `affiliates` | Patients | Entidad central (sin cambios) |
| `eps` | SocialSecurity | Catálogo EPS (reutilizado via FK) |
| `afps` | SocialSecurity | Catálogo AFP (reutilizado via FK) |
| `arps` | SocialSecurity | Catálogo ARL (reutilizado via FK) |
| `ccfs` | SocialSecurity | Catálogo CCF (reutilizado via FK) |
| `contributor_types` | SocialSecurity | Tipos de cotizante con reglas |
| `payment_operators` | SocialSecurity | Operadores de pago |
| `client_types` | SocialSecurity | Tipos de cliente |
| `contribution_parameters` | SocialSecurity | Parámetros con vigencia |
| `payrolls` | SocialSecurity | Planillas (fuente de datos cambia a pila_affiliations) |
| `colombian_holidays` | SocialSecurity | Festivos para cálculo de días hábiles |
| `novelties` / `novelty_types` | SocialSecurity | Novedades PILA |

### Tablas nuevas por crear (Access)

| Tabla | Origen Access | Sprint |
|-------|--------------|--------|
| `receipts` | Recibos de caja | Sprint 5 |
| `receipt_details` | Detalle de recibos | Sprint 5 |
| `billing_accounts` | Cuentas de cobro | Sprint 5 |
| `cash_movements` | Movimientos de caja | Sprint 5 |

---

# 4. PLAN DE IMPLEMENTACIÓN POR SPRINTS {#4-plan-por-sprints}

## Resumen de sprints

| Sprint | Nombre | Meta principal | Duración estimada |
|--------|--------|----------------|-------------------|
| **1** | Consolidación y datos de referencia | BD completa, seeders, campos faltantes | 1 semana |
| **2** | Panel operativo y ficha del afiliado | Reemplazar la vista diaria del Excel | 2 semanas |
| **3** | Credenciales seguras + importación Excel | Migrar 2,121 credenciales cifradas desde Excel | 2 semanas |
| **4** | Consolidación de planillas y estados de pago | Unificar Payrolls con PilaManagement | 1.5 semanas |
| **5** | Módulo Caja (Access) y automatizaciones | Recibos, cuentas de cobro, jobs automáticos | 2 semanas |
| **6** | Dashboard, reportes y puesta en producción | Panel completo, exportaciones, capacitación | 1.5 semanas |

**Total estimado:** 10 semanas (~2.5 meses)

---

## Sprint 1 — Consolidación y datos de referencia

**Meta:** Tener la estructura de BD completa con todos los campos del Excel y Access, seeders cargados, y eliminar brechas entre lo implementado y lo requerido.

### Rama: `feat/pila-sprint-1-consolidation`

| # | Tarea | Detalle | Criterio de aceptación |
|---|-------|---------|------------------------|
| 1.1 | Ampliar migración de `pila_affiliations` | Añadir campos faltantes del Excel: `pila_operator` (enum), `last_novelty_type`, `last_novelty_date`, `salary` (IBC), `pays_parafiscales`, `self_employed`, `risk_class_id`, `payment_periodicity`, `billing_type`, `last_document_number`, `last_payment_period` (AAAAMM), `payment_status` (enum: current/overdue/anticipated) | Migración corre sin error; modelo actualizado con `$fillable` y casts |
| 1.2 | Ampliar migración de `pila_employers` | Verificar que tiene: `is_self_employed`, `check_digit`, `payment_business_day` (2-16), `city`, `department` | Campos existen y tienen validaciones |
| 1.3 | Crear seeder `PilaCotizanteTypeSeeder` | Cargar 19 tipos de cotizante desde la Hoja 2 del Excel (01, 02, 03, 04, 12, 16, 18-23, 30, 31, 40, 51, 56, 57, 59) | `php artisan db:seed --class=PilaCotizanteTypeSeeder` carga datos |
| 1.4 | Crear seeder `PilaRiskClassSeeder` | 6 clases de riesgo ARL (niveles 0-5 con tasas) desde Hoja 4 del Excel | Seeder ejecuta sin error |
| 1.5 | Actualizar modelo `PilaAffiliation` | Añadir relaciones: `riskClass()`, `cotizanteType()`, scopes: `scopeCurrent()`, `scopeOverdue()`, `scopeByOperator()`, accessors para `deadline` (usando DeadlineService) | Relaciones y scopes funcionan en tests |
| 1.6 | Actualizar modelo `PilaEmployer` | Añadir relaciones: `affiliations()`, `pilaCredentials()`, `affiliates()` (through affiliations), scope `scopeActive()` | Relaciones funcionan correctamente |
| 1.7 | Consolidar `DeadlineService` con `DueDateCalculator` | Verificar que ambos usan la misma lógica. El `DeadlineService` de PilaManagement es la fuente de verdad; `DueDateCalculator` de SocialSecurity debe delegarle o ser un alias | Una sola implementación; tests pasan |
| 1.8 | Migración: añadir `access_legacy_id` a `affiliates` | Campo nullable para trazabilidad durante migración desde Access | Campo existe |
| 1.9 | Tests para modelos y seeders | Tests que verifiquen que seeders cargan datos correctos, relaciones entre modelos funcionan, scopes filtran correctamente | Tests pasan |

**Entregable:** BD lista con estructura completa, seeders cargados, modelos con todas las relaciones.

---

## Sprint 2 — Panel operativo y ficha del afiliado

**Meta:** Los asesores pueden consultar y gestionar afiliados desde la web, reemplazando la vista diaria del Excel.

### Rama: `feat/pila-sprint-2-dashboard-ficha`

| # | Tarea | Detalle | Criterio de aceptación |
|---|-------|---------|------------------------|
| 2.1 | Ficha completa del afiliado (Show) | Vista con pestañas: (1) Datos personales, (2) Empleador, (3) Afiliación y entidades, (4) Estado de pago, (5) Credenciales [placeholder], (6) Novedades, (7) Observaciones/Notas | Desde la ficha se ve toda la info que tiene el Excel para un afiliado |
| 2.2 | Panel de listado principal (Index avanzado) | Tabla con columnas: afiliado, empleador, operador PILA, día hábil, estado de pago. Filtros: estado, tipo de cliente, operador, día hábil, EPS, AFP, ARL, CCF, estado de pago, mes de pago | Reemplaza el "scrollear por el Excel" |
| 2.3 | Búsqueda instantánea | Búsqueda por nombre, documento, nombre de empleador en tiempo real (debounce) | Encuentra afiliados en <500ms |
| 2.4 | CRUD de notas (`affiliate_notes`) | En la ficha del afiliado, sección para ver/crear/editar notas. Tipos: affiliation, payment, general. Flag `is_pinned` | Notas se guardan con `created_by` |
| 2.5 | Indicadores visuales de estado | Colores/badges: verde (al día), rojo (en mora), azul (anticipado). Fecha de vencimiento PILA visible junto al afiliado | Estado visible sin hacer clic |
| 2.6 | Exportación básica a Excel | Botón "Exportar" en el listado que genere un Excel con los datos visibles (filtros aplicados) | El asesor puede descargar un Excel actualizado |
| 2.7 | Actualización de `PilaAffiliationController` | Soportar los nuevos campos (salary, payment_status, billing_type, etc.) en store/update | Formularios incluyen todos los campos del Excel |
| 2.8 | Vista de calendario de vencimientos | Vista mensual que muestra cuántos clientes vencen por día hábil (2-16) | Permite planificar la carga de trabajo diaria |
| 2.9 | Tests de integración | Tests para el listado con filtros, la ficha completa, y el calendario de vencimientos | Tests pasan |

**Entregable:** Los asesores pueden gestionar afiliados desde la web con toda la info del Excel. Exportan a Excel para referencia.

---

## Sprint 3 — Credenciales seguras + importación desde Excel

**Meta:** Migrar las 2,121 credenciales del Excel de forma segura y ofrecer UI para gestionarlas.

### Rama: `feat/pila-sprint-3-credentials-import`

| # | Tarea | Detalle | Criterio de aceptación |
|---|-------|---------|------------------------|
| 3.1 | `CredentialController` | Endpoints: listar credenciales por afiliado/empleador, ver contraseña (con auditoría), crear/editar credencial. Contraseñas siempre cifradas con `Crypt::encryptString()` | Contraseña nunca visible en logs ni BD |
| 3.2 | UI de credenciales (Pestaña 5 de la ficha) | Listar credenciales por tipo (PILA, ARL, EPS, AFP, CCF). Contraseñas ocultas (●●●●●). Botón "Ver contraseña" que llama al backend y registra auditoría. Formulario editar/crear. Indicador "No aplica" vs "Sin credencial" | Asesor puede consultar y gestionar credenciales de forma segura |
| 3.3 | Policies para credenciales | `CredentialPolicy`: admin/supervisor ven todo; agent ve solo sus asignados; viewer no ve contraseñas. Middleware aplicado a rutas | Acceso denegado si no tiene permiso |
| 3.4 | Log de auditoría visible | Pantalla (para admin/supervisor) con historial: quién vio/editó qué credencial, cuándo, desde qué IP | Supervisores pueden auditar accesos |
| 3.5 | `AffiliateImportService` (Excel) | Importador que lea el Excel fila por fila siguiendo el mapeo documentado: (1) Deduplicar/crear employer, (2) Crear/actualizar affiliate, (3) Crear affiliation con FKs a catálogos, (4) Cifrar y guardar credenciales PILA, (5) Cifrar y guardar credenciales de portales, (6) Guardar notas. Log de errores por fila | Excel importado; datos verificables en la app |
| 3.6 | Comando artisan `pila:import-excel` | Comando CLI que ejecute el `AffiliateImportService`. Parámetros: ruta del archivo, modo (dry-run o ejecutar). Reporte: filas procesadas, creadas, actualizadas, errores | `php artisan pila:import-excel docs/DataSegura...xlsx --dry-run` funciona |
| 3.7 | Transformaciones de datos | Implementar todas las transformaciones del mapeo: split de nombres, limpieza de documentos, conversión de meses (SEPTIEMBRE → 202509), normalización de N/A, extracción de códigos PILA de entidades | Datos importados son consistentes con catálogos |
| 3.8 | Validación post-importación | Script de verificación que compare totales: 891 afiliados, ~400-500 empleadores únicos, ~2,121 credenciales, estadísticas de estado de pago | Conteos coinciden con el Excel |
| 3.9 | Tests del importador | Tests con datos de prueba (CSV/Excel mock) que verifiquen el flujo completo de importación, deduplicación de employers, cifrado de credenciales, manejo de errores | Tests pasan; errores correctamente reportados |

**Entregable:** Credenciales migradas, cifradas y auditadas. El Excel ya no es necesario para consultar contraseñas.

---

## Sprint 4 — Consolidación de planillas y estados de pago

**Meta:** Unificar el sistema de planillas para que use `pila_affiliations` como fuente de verdad, e implementar las transiciones automáticas de estado que hacía el Access.

### Rama: `feat/pila-sprint-4-payrolls-consolidation`

| # | Tarea | Detalle | Criterio de aceptación |
|---|-------|---------|------------------------|
| 4.1 | `PaymentStatusService` completo | Implementar: `runMonthlyTransitions(period)` — actualiza `payment_status` de todos los afiliados activos según el nuevo mes. `markAsPaid(affiliate, period)` — marca pagado. `getClientsDueToday()` — afiliados cuya fecha límite es hoy. `getClientsDueThisWeek()` | Transiciones de estado correctas |
| 4.2 | Scheduled Job mensual | Artisan command `pila:monthly-transitions` + registro en `Console/Kernel.php` para ejecutar el día 1 de cada mes. Replica la lógica de `CompruebaOrigen` del Access | Job se ejecuta y actualiza estados |
| 4.3 | Refactorizar `PayrollService` | Que lea datos desde `PilaAffiliation` (IBC, tipo cotizante, entidades) en lugar de `SocialSecurityProfile`. Fallback a `SocialSecurityProfile` durante transición | Planillas se calculan correctamente |
| 4.4 | Refactorizar `PayrollBatchService` | Que use `PilaAffiliation` para determinar afiliados elegibles, employer, IBC, etc. | Generación masiva funciona con nuevos datos |
| 4.5 | Migrar rutas de Payrolls | Mover rutas de planillas bajo el prefijo `/pila` o mantener `/payrolls` pero que el controlador consuma PilaManagement | Rutas funcionan sin romper existentes |
| 4.6 | Tipos de planilla PILA | Tabla/enum `payroll_type`: E (electrónica ordinaria), I (corrección), S (empleados), Y (mora), N (corrección mora), A (adicional). Campo `payroll_type` en `payrolls` | Planillas tipadas según normativa |
| 4.7 | Vista de planillas integrada | En la ficha del afiliado, pestaña o sección con historial de planillas: período, estado, montos, acciones | Asesor ve pagos desde la ficha |
| 4.8 | Tests de integración | Tests de generación de planillas desde `PilaAffiliation`, transiciones de estado, y job mensual | Tests pasan |

**Entregable:** Planillas unificadas con PilaManagement; transiciones automáticas de estado replican la lógica del Access.

---

## Sprint 5 — Módulo Caja (Access) y automatizaciones

**Meta:** Migrar las funcionalidades de caja del Access y automatizar recordatorios.

### Rama: `feat/pila-sprint-5-cash-automations`

| # | Tarea | Detalle | Criterio de aceptación |
|---|-------|---------|------------------------|
| 5.1 | Modelo de datos de Caja | Migraciones: `receipts` (recibos de caja), `receipt_details` (detalle), `billing_accounts` (cuentas de cobro), `cash_movements` (movimientos) | Tablas creadas con FKs correctas |
| 5.2 | CRUD de recibos de caja | Crear, listar, ver recibos asociados a afiliados. Número de recibo autogenerado. Detalle con conceptos y montos | Recibos se pueden crear y consultar |
| 5.3 | CRUD de cuentas de cobro | Gestión de facturación: crear cuenta de cobro, asociar a afiliado/empleador, marcar como pagada/pendiente | Flujo de cobro funcional |
| 5.4 | `AccessImportService` | Servicio para importar datos históricos del Access: (1) Empleadores, (2) Afiliados (con `access_legacy_id`), (3) Historial de aportes → `payrolls`, (4) Recibos, (5) Cuentas de cobro | Datos del Access migrados a MySQL |
| 5.5 | Conciliación Excel ↔ Access | Servicio que compare datos importados del Excel con datos del Access. Generar reporte de discrepancias: diferencias en nombre, documento, EPS, AFP, etc. | Reporte exportable con discrepancias |
| 5.6 | Notificaciones de vencimiento | Recordatorios automáticos via WhatsApp/email X días antes del vencimiento. Usa templates existentes del módulo Integrations. Registra en `communication_logs` | Recordatorios se envían automáticamente |
| 5.7 | Alertas en dashboard | Alertas visibles: vencimiento hoy, vencimiento mañana, en mora, credencial sin actualizar >180 días, afiliado sin EPS | Alertas visibles en el panel |
| 5.8 | Tests | Tests para recibos, cuentas de cobro, importación Access, conciliación | Tests pasan |

**Entregable:** Funcionalidad de caja del Access migrada; automatizaciones operativas funcionando.

---

## Sprint 6 — Dashboard, reportes y puesta en producción

**Meta:** El panel operativo reemplaza completamente el Excel y el Access.

### Rama: `feat/pila-sprint-6-dashboard-production`

| # | Tarea | Detalle | Criterio de aceptación |
|---|-------|---------|------------------------|
| 6.1 | Dashboard operativo completo | KPIs: activos, inactivos, en mora, anticipados. Vencimientos hoy/esta semana. Distribución por operador PILA. Distribución por día hábil. Gráfico de tendencia de pagos | Dashboard muestra toda la info operativa |
| 6.2 | Reportes exportables | Por empleador (afiliados y estado), por período (planillas), listado general con filtros. Formatos: Excel y PDF | Exportaciones funcionales |
| 6.3 | Roles y permisos completos | Políticas para todos los recursos. Matriz de permisos: admin (todo), supervisor (todo + auditoría), agent (consultar + editar asignados), viewer (solo lectura) | Acceso controlado por rol |
| 6.4 | Deprecar módulo SocialSecurity | Mover lo que falta a PilaManagement. Mantener rutas antiguas con redirects temporales. Documentar qué se migró | Sin dependencias al módulo antiguo |
| 6.5 | Migración de datos en producción | Ejecutar importación Excel + Access en entorno de producción. Verificar integridad. Congelar Excel (solo lectura) | Datos migrados y verificados |
| 6.6 | Pruebas de usuario (UAT) | El equipo operativo prueba todos los flujos: buscar afiliado, ver credenciales, generar planilla, ver dashboard, exportar | Aprobación del equipo |
| 6.7 | Documentación operativa | Guía de usuario: cómo usar el módulo (con capturas). Guía técnica: arquitectura, servicios, migraciones | Documentación entregada |
| 6.8 | Capacitación y Go-Live | Capacitación al equipo. Período de operación dual (1 semana). Go-Live definitivo | Equipo opera exclusivamente desde Laravel |

**Entregable:** El Excel se congela. El Access se archiva. El equipo opera exclusivamente desde la aplicación web.

---

# 5. ESTRATEGIA DE MIGRACIÓN DE DATOS {#5-migracion-datos}

## 5.1 Orden obligatorio (por dependencias de FK)

```
FASE 1 — Catálogos (seeders, sin dependencias externas):
  1. pila_risk_classes          (6 registros, Hoja 4 Excel)
  2. pila_cotizante_types       (19 registros, Hoja 2 Excel)
  3. Verificar: eps, afps, arps, ccfs, payment_operators, client_types (seeders existentes)

FASE 2 — Entidades base del Excel:
  4. pila_employers             (~400-500 únicos, deduplicados de bloque B)
  5. affiliates                 (891 registros, bloque A — actualizar existentes o crear)

FASE 3 — Datos operativos del Excel:
  6. pila_affiliations          (891 registros, bloques C-H, con FKs)
  7. pila_credentials           (~777 registros, cols 27-28, cifradas)
  8. portal_credentials         (~1,344 registros, cols 32-42, cifradas)
  9. affiliate_notes            (cols 44, 50 → notas con is_pinned)

FASE 4 — Datos históricos del Access:
  10. Conciliación employers (Access 001 vs pila_employers)
  11. Conciliación affiliates (Access 002 vs affiliates)
  12. payrolls (historial de aportes desde Access)
  13. receipts + receipt_details
  14. billing_accounts
  15. cash_movements
```

## 5.2 Transformaciones clave durante importación

| Dato original | Transformación | Servicio responsable |
|---------------|---------------|---------------------|
| `"JOHN JAIRO HERRERA ESCOBAR"` | Split en first_name, second_name, last_name, second_last_name | `AffiliateImportService::splitName()` |
| `"901776975-4"` | document_number: `901776975`, check_digit: `4` | `AffiliateImportService::parseNit()` |
| `"01 -Dependiente."` | Extraer código: `01` → buscar en `pila_cotizante_types` | `AffiliateImportService::parseCotizanteType()` |
| `"EPS005 -EPS SANITAS"` | Extraer código: `EPS005` → buscar en `eps` por code | `AffiliateImportService::parseEntity()` |
| `"$1.423.500"` | Limpiar a decimal: `1423500.00` | `AffiliateImportService::parseSalary()` |
| `"MiClave123"` (credencial) | `Crypt::encryptString("MiClave123")` | `CredentialService::encrypt()` |
| `"SEPTIEMBRE"` (mes de pago) | `202509` (AAAAMM) | `AffiliateImportService::parsePaymentPeriod()` |
| `"SI"` / `"NO"` | `true` / `false` | Cast directo |
| `"NO APLICA"` / `"N/A"` | `is_not_applicable = true`, `username = null` | `AffiliateImportService::parseCredential()` |
| `"VENCIDO"` / `"ACTUAL"` | `overdue` / `current` | Enum cast |

## 5.3 Deduplicación de empleadores

1. Agrupar filas del Excel por `document_number` del pagador (col P)
2. Verificar consistencia de datos entre filas del mismo empleador
3. Crear un solo registro en `pila_employers` por documento único
4. Si `document_number` del pagador = `document_number` del afiliado → `is_self_employed = true`
5. Vincular todos los afiliados a ese empleador via `pila_affiliations.employer_id`

---

# 6. CONSOLIDACIÓN SocialSecurity → PilaManagement {#6-consolidacion}

## 6.1 Servicios a migrar/consolidar

| Servicio actual (SocialSecurity) | Acción | Servicio destino (PilaManagement) |
|----------------------------------|--------|-----------------------------------|
| `DueDateCalculator` | Deprecar | `DeadlineService` (ya existe, es el mismo concepto) |
| `PayrollService` | Migrar | `PilaManagement/Services/PayrollService` |
| `PayrollBatchService` | Migrar | `PilaManagement/Services/PayrollBatchService` |
| `ContributionCalculator` | Migrar | `PilaManagement/Services/ContributionCalculator` |
| `ContributionParametersResolver` | Migrar | `PilaManagement/Services/ContributionParametersResolver` |
| `ContributorTypeRules` | Migrar | `PilaManagement/Services/ContributorTypeRules` |
| `IndependentContractIbcService` | Migrar | `PilaManagement/Services/IndependentContractIbcService` |

## 6.2 Modelos a migrar/reemplazar

| Modelo actual | Acción | Modelo destino |
|---------------|--------|----------------|
| `Payer` | Reemplazar | `PilaEmployer` (mapeo de FKs con migración) |
| `SocialSecurityProfile` | Reemplazar | `PilaAffiliation` (sync bidireccional durante transición) |
| `OperatorCredential` | Reemplazar | `PilaCredential` + `PortalCredential` |
| `Payroll` | Mantener | Se mantiene, solo cambia la fuente de datos |
| `ContributorType` | Mantener | Se reutiliza; `PilaCotizanteType` es complementario |
| `Novelty` / `NoveltyType` | Mantener | Se reutilizan sin cambios |
| `Eps`, `Afp`, `Arp`, `Ccf` | Mantener | Se reutilizan como catálogos compartidos |

## 6.3 Rutas a consolidar

| Ruta actual | Acción | Ruta nueva |
|-------------|--------|------------|
| `/payers/*` | Redirect → | `/pila/employers/*` |
| `/payrolls/*` | Mantener o mover | `/pila/payrolls/*` |
| `/dashboard-ss` | Reemplazar | `/pila/dashboard` |
| `/pila/employers/*` | Mantener | Sin cambio |
| `/pila/affiliations/*` | Mantener | Sin cambio |

## 6.4 Orden de consolidación (gradual)

1. **Sprint 1-3:** PilaManagement funciona en paralelo con SocialSecurity
2. **Sprint 4:** PayrollService lee de PilaAffiliation con fallback a SocialSecurityProfile
3. **Sprint 5:** Todas las dependencias usan PilaManagement
4. **Sprint 6:** SocialSecurity se depreca; rutas antiguas redirigen

---

# 7. REGLAS DE NEGOCIO OBLIGATORIAS {#7-reglas-negocio}

| ID | Regla | Implementación | Sprint |
|----|-------|----------------|--------|
| RN-01 | Tipo SERVICONLI diferenciado en reportes y operación | `client_type = 'serviconli'` en `affiliates`; filtros en dashboard y listados | 2 |
| RN-02 | Independiente = empleador es el mismo afiliado | `is_self_employed = true` en `pila_affiliations`; copiar datos del afiliado al crear employer | 1, 3 |
| RN-03 | Credenciales PILA pertenecen al empleador, no al afiliado | `pila_credentials.employer_id` (no affiliate_id); al mostrar credenciales de afiliado, buscar via employer | 3 |
| RN-04 | Fecha límite se calcula, no se almacena | `DeadlineService::calculate()` dinámico; nunca columna `due_date` en affiliations | Ya implementado |
| RN-05 | Colombianos en exterior: ARL y CCF nulos | Bloquear selección de ARL/CCF cuando `client_type = 'exterior'`; `is_not_applicable = true` en portal_credentials | 2 |
| RN-06 | Contraseñas NUNCA en texto plano | `Crypt::encryptString()` en toda escritura; `CredentialService` como único punto de cifrado/descifrado | Ya implementado |
| RN-07 | Toda consulta de contraseña genera auditoría | `CredentialService::decryptAndAudit()` registra siempre en `credential_audit_logs` | Ya implementado |
| RN-08 | Observaciones importantes → notas fijadas | Al importar, detectar IMPORTANTE/SIEMPRE/OBLIGATORIO → `is_pinned = true` | 3 |
| RN-09 | `last_payment_period` en formato AAAAMM | Validación de formato; conversión desde texto libre del Excel durante importación | 1, 3 |
| RN-10 | "No aplica" ≠ "Sin credencial" | `is_not_applicable = true` vs `username = null AND is_not_applicable = false`; UI diferenciada | 3 |
| RN-11 | Día hábil 5 concentra 54.8% de clientes | Alerta de alto volumen en dashboard; permitir filtrar/priorizar por día hábil | 2 |
| RN-12 | Transiciones automáticas al cambio de mes | Scheduled Job `pila:monthly-transitions` el día 1 de cada mes | 4 |

---

# 8. PRINCIPIOS DE DESARROLLO {#8-principios}

## 8.1 Cero hardcodeo — Fuente única de verdad

| Tipo de dato | Fuente | Prohibido |
|--------------|--------|-----------|
| Porcentajes (salud, pensión, ARL) | `contribution_parameters` con vigencia | Constantes en código |
| Tipos de cotizante | `contributor_types` / `pila_cotizante_types` | Arrays PHP |
| Entidades (EPS, AFP, ARL, CCF) | Tablas de catálogo | Strings en controladores |
| Estados (payroll, payment, affiliate) | Enums PHP que reflejan valores en BD | Strings mágicos |
| Mensajes y textos | `lang/es/*.php` | Textos embebidos en vistas |
| Configuración por entorno | `.env` + `config/*.php` | Valores fijos en código |
| Día de pago (2-16) | `DeadlineService` + Decreto 1990 en config | Magic numbers |

## 8.2 Clean Code

- **Controladores delgados:** validar, llamar servicio, devolver respuesta
- **Servicios:** lógica de negocio; leen parámetros de BD/config
- **Form Requests:** validaciones derivadas de catálogos, no literales
- **Modelos:** relaciones, scopes, accessors; sin lógica pesada
- **DTOs:** para resultados compuestos (desglose de aportes)
- **Transacciones:** `DB::transaction()` para operaciones que tocan múltiples tablas
- **Events/Listeners:** para auditoría y efectos secundarios

## 8.3 Anti-patrones prohibidos

| Anti-patrón | Qué evitar | Qué hacer |
|-------------|-----------|-----------|
| God Object | Modelo o servicio que hace todo | Servicios específicos por responsabilidad |
| Spaghetti Code | Lógica de negocio en controladores o vistas | Extraer a servicios |
| Magic Numbers | `0.125`, `1750905`, `5` en código | Leer de `ContributionParametersResolver` o config |
| Magic Strings | `'ACTIVO'`, `'serviconli'` en queries | Usar enums: `AffiliateStatus::ACTIVO` |
| Lava Flow | Código muerto del módulo SocialSecurity | Eliminar en Sprint 6 |
| Big Ball of Mud | Todo en un módulo sin separación | Módulos con responsabilidades claras |

## 8.4 Checklist por PR

- [ ] Sin números mágicos ni porcentajes literales
- [ ] Sin strings de negocio en lógica (usar enums/catálogos)
- [ ] Mensajes en `lang/` (no en controladores/vistas)
- [ ] Controladores delgados (<30 líneas por método)
- [ ] Servicios no inyectan `Request`
- [ ] Operaciones multi-tabla en `DB::transaction()`
- [ ] Tests para lógica nueva
- [ ] Sin código muerto ni imports sin usar

---

# 9. RIESGOS Y MITIGACIONES {#9-riesgos}

| # | Riesgo | Impacto | Probabilidad | Mitigación |
|---|--------|---------|-------------|-----------|
| R1 | Pérdida de datos durante migración Excel/Access | Alto | Media | Importación idempotente (buscar antes de crear); modo dry-run; backup previo; verificación post-migración |
| R2 | Credenciales cifradas irrecuperables | Alto | Baja | Backup de `APP_KEY`; cifrado con `Crypt::encryptString()` (reversible con misma key); test de decrypt post-import |
| R3 | Datos inconsistentes Excel vs Access | Medio | Alta | Servicio de conciliación; reporte de discrepancias; resolución manual antes de Go-Live |
| R4 | Resistencia del equipo al cambio | Medio | Media | Período de operación dual (1 semana); capacitación presencial; exportación a Excel como puente |
| R5 | Rendimiento con 891+ afiliados y filtros complejos | Bajo | Baja | Índices en columnas de filtro; paginación; eager loading de relaciones |
| R6 | Interrupción del servicio durante migración | Alto | Baja | Migración en horario no operativo; rollback script; operación dual durante transición |
| R7 | Cambios normativos durante implementación | Medio | Media | Parámetros en BD con vigencia; diseño flexible que no asume valores fijos |

---

# 10. CRITERIOS DE ACEPTACIÓN FINAL {#10-criterios}

## El sistema reemplaza el Excel cuando:

- [ ] Los 891 afiliados del Excel están en la BD con todos sus datos operativos
- [ ] Las ~2,121 credenciales están cifradas y consultables con auditoría
- [ ] Los ~400-500 empleadores están deduplicados y vinculados a sus afiliados
- [ ] El panel operativo permite filtrar y buscar como se hacía en el Excel (o mejor)
- [ ] La fecha límite de pago se calcula correctamente para cualquier mes
- [ ] El estado de pago se actualiza automáticamente cada mes
- [ ] Los asesores pueden exportar a Excel cualquier vista filtrada
- [ ] La ficha del afiliado muestra toda la información que tenía el Excel

## El sistema reemplaza el Access cuando:

- [ ] El historial de aportes PILA está migrado a `payrolls`
- [ ] Los recibos de caja se pueden crear y consultar
- [ ] Las cuentas de cobro se gestionan desde la app
- [ ] Las transiciones automáticas de pago (`CompruebaOrigen`) están implementadas como job mensual
- [ ] Los datos del Access están conciliados con los del Excel

## El equipo opera exclusivamente desde Laravel cuando:

- [ ] El Excel se congela (solo lectura, archivo histórico)
- [ ] El Access se archiva (solo consulta de históricos si necesario)
- [ ] Los asesores están capacitados
- [ ] La documentación operativa está entregada
- [ ] El período de operación dual se completó sin incidentes

---

## RESUMEN EJECUTIVO

| Elemento | Cantidad |
|----------|----------|
| Sprints de implementación | 6 |
| Duración total estimada | ~10 semanas |
| Tablas nuevas/modificadas | 12 (9 PilaManagement + 3 CashManagement) |
| Tablas existentes reutilizadas | 15+ |
| Registros a migrar del Excel | 891 afiliados + ~2,121 credenciales |
| Registros a migrar del Access | Variable (historial de aportes, recibos, etc.) |
| Servicios de negocio | 8+ (DeadlineService, CredentialService, PaymentStatusService, AffiliateImportService, AccessImportService, ContributionCalculator, PayrollService, PayrollBatchService) |
| Módulos afectados | PilaManagement (nuevo), SocialSecurity (deprecated), Patients (ajustado), CashManagement (nuevo) |

---

*Documento preparado para el equipo de desarrollo de Serviconli*  
*Fuentes: ARQUITECTURA_MODULO_GESTION_PILA.md + DataSegura SERVICONLI 2025 (1).xlsx + AplicativoV6.accdb + estado actual del código*  
*Este plan debe actualizarse al inicio de cada sprint con el estado real del avance.*
