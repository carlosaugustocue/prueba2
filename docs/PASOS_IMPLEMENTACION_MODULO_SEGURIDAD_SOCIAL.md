# Pasos de implementación — Módulo de Seguridad Social

**Objetivo:** Definir cómo proceder con la implementación a partir de lo ya construido, alineado con la normativa de seguridad social y con lo que debe guardar la aplicación según el documento **Data Central de Citas** (Excel del módulo SS), siguiendo buenas prácticas y código limpio.

**Documentos de referencia:**  
- [mapeo_datasegura_a_base_de_datos.md](mapeo_datasegura_a_base_de_datos.md) — columnas Excel → tablas y campos  
- [PLAN_IMPLEMENTACION_ARQUITECTURA_Y_CODIGO_LIMPIO.md](PLAN_IMPLEMENTACION_ARQUITECTURA_Y_CODIGO_LIMPIO.md) — principios, cero hardcodeo, fases  
- [TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md) — tareas concretas por ítem  

---

## 1. Estado actual del módulo de Seguridad Social

### 1.1 Ya implementado (alineado con Data Central / normativa)

| Área | Implementado | Nota |
|------|--------------|------|
| **Afiliados** | Modelo `Affiliate`, migración con `city`, `department`; enum `DocumentType` con **cc, ti, ce, pa, rc, nit, ppt, ptt** | Cumple mapeo columnas F, J, K, D. |
| **Perfil SS** | `SocialSecurityProfile` con FKs: `client_type_id`, `contributor_type_id`, `eps_id`, `afp_id`, `arp_id`, `ccf_id`, `payer_id`, `payment_operator_id`, `accounting_registry_id`; `ibc`, `payment_day`, `payment_periodicity`, `has_parafiscales`, `observations`, `arp_risk_class` | Campos según mapeo Data Central. |
| **Parámetros** | `ContributionParameter` con `valid_from` / `valid_to`; `ContributionParametersResolver` y `ContributionCalculator` que leen de BD | Sin porcentajes ni topes en código. |
| **Catálogos** | Modelos: `ContributorType`, `ClientType`, `Eps`, `Afp`, `Arp`, `Ccf`, `PaymentOperator`, `AccountingRegistry` | Perfil SS usa solo FKs. |
| **Planillas** | `Payroll`, `PayrollService`, `PayrollBatchService`; estados (`PayrollStatus`); montos por aporte; `calculation_metadata`; filtro por `payer_id` | Falta tipo de planilla PILA (E, I, S, etc.). |
| **Contratos independientes** | `IndependentContract`, IBC por contrato, integración con liquidación | — |
| **Novedades** | `Novelty`, `NoveltyType`, relación con afiliado | — |
| **Pagadores** | `Payer`, CRUD, relación con perfiles SS | — |
| **Vencimientos** | `DueDateCalculator` (día hábil según festivos) | — |
| **Reglas por cotizante** | `ContributorTypeRules::forCode()` con salud, pensión, ARL, CCF, proporcional | Códigos hoy en constantes; evolución: leer de catálogo. |

### 1.2 Pendiente o a reforzar según Data Central y buenas prácticas

| Área | Pendiente / refuerzo | Fuente |
|------|---------------------|--------|
| **Importación Excel** | No existe `ImportService`. El Excel Data Central debe poder importarse con mapeo configurable (columnas → campos), validaciones con catálogos/parámetros y mensajes en `lang/`. | Mapeo doc; plan arquitectura Fase 3. |
| **Tipos de planilla PILA** | No hay catálogo ni campo `payroll_type` (E, I, S, Y, N, A). Requerido por normativa para identificar planilla ordinaria, corrección, mora, etc. | Plan arquitectura Fase 1; normativa. |
| **ContributorTypeRules** | Códigos (01, 02, 03, …) están en constantes PHP. Buena práctica: que las reglas lean de la tabla `contributor_types` (o tabla de reglas asociada) para no hardcodear. | Plan arquitectura Fase 0. |
| **Mensajes y validaciones** | Centralizar mensajes de validación y errores de negocio en `lang/` (ej. `lang/es/social_security.php`); no strings en controladores ni servicios. | Plan arquitectura Fase 0. |
| **Mora e intereses** | Parámetros de mora/intereses en BD; servicio de cálculo; planilla tipo A (mora). | Plan arquitectura Fase 2; normativa. |
| **Recibos y soportes** | Plantilla de recibo con textos en lang; generación de PDF y almacenamiento en `support_documents`; sin literales de negocio en código. | Plan arquitectura Fase 2. |
| **Políticas y auditoría** | Policies por recurso (Payroll, Payer, Affiliate); eventos de dominio y registro en auditoría; historial por usuario. | Plan arquitectura Fase 4. |

---

## 2. Qué debe guardar la aplicación (resumen según Data Central y normativa)

Lo que el Excel **Data Central de Citas** y la normativa implican que la aplicación debe almacenar ya está en gran parte cubierto por el modelo actual. Resumen:

- **Afiliados:** identificación, nombres (split desde un solo campo si se importa), documento (único), tipo documento (cc, ti, ce, ppt, ptt…), género, nacimiento, dirección, **ciudad**, **departamento**, teléfono, email, estado (ACTIVO/INACTIVO), notas (ej. pendiente clasificación).
- **Perfil SS:** tipo cliente, tipo cotizante (código PILA), IBC, EPS, AFP, ARP, clase riesgo ARL, CCF, pagador, operador de pago, día de pago (2–16), parafiscales, periodicidad pago, registro contable, observaciones.
- **Planillas:** por afiliado/mes/año; vencimiento; montos por concepto; **tipo de planilla** (E, I, S, Y, N, A) cuando se implemente; estados (pendiente, liquidada, enviada, pagada, en mora).
- **Novedades:** tipo, fecha efectiva, descripción, valor anterior/nuevo, responsable.
- **Pagadores:** NIT/documento único, datos de contacto.
- **Parámetros:** porcentajes y topes con vigencia (desde `contribution_parameters`); ningún valor normativo en código.

Los puntos en negrita son los que faltan o hay que completar (tipos de planilla; ciudad/departamento ya están en migración).

---

## 3. Cómo proceder: orden recomendado y buenas prácticas

Se sigue el orden del [PLAN_IMPLEMENTACION_ARQUITECTURA_Y_CODIGO_LIMPIO.md](PLAN_IMPLEMENTACION_ARQUITECTURA_Y_CODIGO_LIMPIO.md), priorizando lo que desbloquea el reemplazo del Excel y la operación diaria.

### Fase 0 — Consolidación (sin nuevas funcionalidades)

Objetivo: eliminar hardcodeo y tener una sola fuente de verdad para mensajes y reglas.

| Paso | Acción | Buena práctica |
|------|--------|------------------|
| 0.1 | **Auditoría de constantes:**** Buscar en el código números literales (porcentajes, montos, días 2–16), strings de tipo cotizante o tipo de planilla. Reemplazar por lectura desde `ContributionParametersResolver` o desde catálogos (tablas). | No magic numbers; parámetros y catálogos como única fuente. |
| 0.2 | **Mensajes en lang:** Crear `lang/es/social_security.php` (y/o ampliar `validation.php`) con claves para validaciones y errores del módulo SS. En Form Requests y servicios usar `__('social_security.xxx')` o equivalente. | No strings de usuario en controladores/servicios. |
| 0.3 | **Enums y BD:** Verificar que los enums (p. ej. `PayrollStatus`, `DocumentType`) coincidan con los valores guardados en BD; etiquetas para UI desde lang o método `label()`. | Una sola definición de estados/códigos. |
| 0.4 | **Reglas por tipo cotizante:** Evolucionar `ContributorTypeRules` para que, donde sea posible, lea códigos o reglas desde la tabla `contributor_types` (o tabla de configuración de reglas) en lugar de constantes PHP. Si algún código debe seguir en código, documentarlo y centralizarlo en esa clase. | Reglas de negocio en un solo lugar; preferencia por datos (BD) frente a constantes. |

Entregable: código base sin constantes de negocio dispersas; mensajes y reglas centralizados.

---

### Fase 1 — Tipos de planilla PILA y flujo operativo

Objetivo: cumplir normativa con tipos de planilla y flujo de estados de pago sin hardcodear.

| Paso | Acción | Buena práctica |
|------|--------|------------------|
| 1.1 | **Catálogo de tipos de planilla:** Crear tabla `payroll_types` (code, name, description, is_correction, is_mora, sort_order, etc.) y seeder con códigos E, I, S, Y, N, A según normativa. Servicios y APIs leen de este catálogo. | Códigos PILA en BD, no en código. |
| 1.2 | **Campo en planillas:** Migración que añada `payroll_type` a `payrolls` (FK a `payroll_types` o string indexado). Valor por defecto según tipo de cotizante del afiliado mediante servicio que consulte catálogo. | Un solo lugar para la relación tipo cotizante ↔ tipo planilla. |
| 1.3 | **Reglas por tipo de planilla:** Servicio o clase que, dado `payroll_type`, devuelva si aplica mora, si es corrección, etc., leyendo del catálogo (o config), sin `switch` con literales en controladores. | Lógica en servicios; datos en BD/config. |
| 1.4 | **Flujo de estados de pago:** Si se requieren estados intermedios (pago recibido, validado banco, etc.), modelarlos en BD (tabla de estados o campos en `payrolls`/pagos). Transiciones validadas en servicio; mensajes desde lang. | Trazabilidad y mensajes traducibles. |
| 1.5 | **Panel “Qué hacer hoy”:** Vista que use `DueDateCalculator` y parámetros de BD para vencimientos, y estados (ej. OVERDUE) para mora; sin fechas ni rangos hardcodeados. | Datos y reglas desde BD/config. |

Entregable: planillas tipadas según PILA; flujo de pago trazable; panel operativo parametrizado.

---

### Fase 2 — Mora, intereses y recibos

Objetivo: mora e intereses parametrizados y recibos sin literales en código.

| Paso | Acción | Buena práctica |
|------|--------|------------------|
| 2.1 | **Parámetros de mora/intereses:** Usar `contribution_parameters` (o tabla específica) con tipo MORA/INTEREST, vigencia y valor/tasa. Servicio `MoraInterestService` que calcule solo con esos parámetros. | Sin tasas en código. |
| 2.2 | **Planilla tipo A (mora):** Crear planillas de mora a partir de planillas vencidas; montos e intereses según parámetros anteriores. | Misma fuente de verdad que el resto de aportes. |
| 2.3 | **Plantilla de recibo:** Blade (o similar) con placeholders; textos en lang; valores (administración, afiliación, etc.) desde servicio que lea config o parámetros. | Sin textos ni montos fijos en vistas. |
| 2.4 | **Generación y guardado de PDF:** Servicio que genere el PDF y lo guarde en `support_documents` (o tabla análoga); rutas y nombres desde config de almacenamiento. | Configuración en config/storage. |

Entregable: mora e intereses desde BD; recibos generados sin hardcodeo.

---

### Fase 3 — Importación Data Central y reconciliación

Objetivo: poder importar el Excel del módulo SS con mapeo configurable y validaciones alineadas al resto del sistema.

| Paso | Acción | Buena práctica |
|------|--------|------------------|
| 3.1 | **Mapeo configurable:** Definir en config (PHP o BD) el mapeo columna Excel → campo BD y transformaciones (split de nombres, normalización de enums). `ImportService` solo lee ese mapeo; no columnas fijas en código. | Un solo lugar para el mapeo; fácil de ajustar si cambia el Excel. |
| 3.2 | **Validaciones:** Reutilizar mismos rangos y catálogos que el resto (IBC, tipo documento, tipo cotizante, fechas). Mensajes de error con claves en lang. | Misma fuente de verdad que CRUD y planillas. |
| 3.3 | **Reconciliación:** Servicio que compare datos importados con el estado actual en BD; diferencias según reglas configurables; reporte con claves de mensaje traducibles. | Sin lógica de comparación hardcodeada. |

Entregable: importación y reconciliación gobernadas por config/catálogos; cero literales de negocio en el importador.

---

### Fase 4 — Auditoría, políticas y cierre

Objetivo: permisos y trazabilidad consistentes.

| Paso | Acción | Buena práctica |
|------|--------|------------------|
| 4.1 | **Policies:** Policies para Payroll, Payer, Affiliate (y otros recursos sensibles): `view`, `update`, `settle`, `approve`, etc. Controladores usan `authorize()`; mensajes 403 desde lang. | Autorización centralizada; mensajes traducibles. |
| 4.2 | **Eventos y listeners:** Eventos de dominio (ej. `SettlementCreated`, `PaymentRegistered`) y listeners que registren en tabla de auditoría; payload sin datos sensibles, referencias por ID. | Trazabilidad sin acoplar lógica en controladores. |
| 4.3 | **Historial por usuario:** Consulta de acciones desde tabla de auditoría (y `payroll_trackings` u equivalente); filtros por fecha y entidad desde request. | Sin filtros ni etiquetas hardcodeados. |

Entregable: acceso controlado por políticas; trazabilidad por eventos; historial consultable.

---

## 4. Principios a respetar en cada paso

- **Una sola fuente de verdad:** Parámetros normativos y catálogos en BD (o config para entorno); estados en enums alineados con BD; mensajes en lang.
- **Cero hardcodeo de negocio:** Sin porcentajes, topes, códigos de tipo cotizante/planilla ni días de pago en código; todo desde `ContributionParametersResolver` o tablas catálogo.
- **Controladores delgados:** Validar, llamar servicio o acción, devolver respuesta. Sin cálculos ni consultas complejas.
- **Servicios sin HTTP:** Reciben DTOs o valores escalares; no inyectar `Request`; leen parámetros y catálogos desde BD/config.
- **Transacciones:** Operaciones que modifican varias entidades o estados críticos dentro de `DB::transaction()`.
- **Tests:** Donde haya cálculos o reglas de negocio, tests unitarios o de integración; datos de prueba desde factories/seeds, no literales dispersos.

---

## 5. Resumen ejecutivo

- **Estado actual:** El módulo ya cubre afiliados, perfil SS, catálogos, parámetros, planillas, pagadores, novedades, contratos independientes y vencimientos. Faltan: tipos de planilla PILA, ImportService para el Excel Data Central, mora/recibos parametrizados y capa de políticas/auditoría.
- **Orden de trabajo:** Fase 0 (consolidar sin hardcodeo y mensajes) → Fase 1 (tipos de planilla y panel operativo) → Fase 2 (mora y recibos) → Fase 3 (importación y reconciliación) → Fase 4 (políticas y auditoría).
- **Documento a reemplazar:** El Excel **Data Central de Citas** del módulo de Seguridad Social se reemplaza como fuente operativa cuando el ImportService (Fase 3) y el resto de flujos (Fase 0–2) estén alineados con el mapeo y la normativa, sin hardcodear y con una sola fuente de verdad.

Actualizar este documento cuando se complete cada fase o cuando cambie el alcance del módulo.
