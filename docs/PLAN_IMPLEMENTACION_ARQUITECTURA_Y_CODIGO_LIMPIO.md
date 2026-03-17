# Plan de implementación — Arquitectura y código limpio

**Rol:** Arquitecto de software  
**Objetivo:** Plan de implementación del Sistema Serviconli aplicando buenas prácticas, código limpio y **cero hardcodeo**.  
**Documento operativo a sustituir (módulo Seguridad Social):** `docs/DATA CENTRAL DE CITAS ACTUPELAEZALIZADO 202505 (1).xlsx`. Este archivo pertenece a la sección/módulo de **Seguridad Social**; la Central de Citas es un **módulo aparte** (gestión de citas). La plataforma debe reemplazar el uso de dicho Excel como fuente de verdad operativa dentro del módulo de Seguridad Social.  
**Referencias:** [PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md](PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md), [TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md), [ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md](ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md), [mapeo_datasegura_a_base_de_datos.md](mapeo_datasegura_a_base_de_datos.md). **Pasos concretos dado lo ya implementado:** [PASOS_IMPLEMENTACION_MODULO_SEGURIDAD_SOCIAL.md](PASOS_IMPLEMENTACION_MODULO_SEGURIDAD_SOCIAL.md).

### Documento operativo a sustituir (módulo Seguridad Social)

**Aclaración:** La **Central de Citas** es un módulo aparte (gestión de citas). El documento siguiente pertenece a la **sección o módulo de Seguridad Social** y es el que el sistema debe reemplazar como fuente operativa en ese ámbito.

| Ubicación | Módulo | Descripción |
|-----------|--------|-------------|
| `docs/DATA CENTRAL DE CITAS ACTUPELAEZALIZADO 202505 (1).xlsx` | **Seguridad Social** | Archivo Excel de referencia operativa del módulo SS. El sistema debe reemplazar su uso como fuente de verdad en este módulo; el ImportService y el mapeo de datos deben alinearse con la estructura de este documento. |

---

## 1. Principios de diseño (obligatorios)

### 1.1 Una sola fuente de verdad

| Tipo de dato | Dónde vive | Ejemplo | Prohibido |
|--------------|------------|---------|-----------|
| Parámetros normativos (porcentajes, SMLMV, topes IBC, día de pago 2–16) | Tabla `contribution_parameters` (o equivalente) con `valid_from` / `valid_to` | Salud 12.5%, IBC min/max | Constantes en código, `config()` con valores fijos |
| Catálogos de negocio (tipos cotizante, EPS, AFP, ARL, CCF, operadores) | Tablas `contributor_types`, `eps`, `afps`, `arps`, `ccfs`, `payment_operators` | Código 01 = Dependiente | Arrays o enums con descripciones de negocio en PHP |
| Estados de entidades (planilla, pago, novedad) | Enums PHP que reflejan valores en BD; etiquetas para UI en lang o en BD | `PayrollStatus::SETTLED` | Strings mágicos en controladores o vistas |
| Configuración por entorno (URLs, feature flags, límites) | `.env` + `config/*.php` (solo lectura de `env()`) | `config('services.whatsapp.api_url')` | Valores fijos en código |
| Mensajes y textos de UI | `lang/` o tablas de contenido si son dinámicos | `__('payroll.status_settled')` | Textos embebidos en vistas o servicios |
| Reglas de negocio (qué tipo de cotizante paga qué) | Servicios o clases de reglas que lean catálogos/parámetros | `ContributorTypeRules::forCode($code)` | `if ($code === '01')` con lógica dispersa |

### 1.2 Código limpio y responsabilidades

- **Controladores:** solo validar request, llamar a un servicio o acción, devolver respuesta (Inertia/JSON/redirect). Sin cálculos ni consultas complejas.
- **Servicios:** orquestan lógica de negocio; leen parámetros y catálogos desde BD o config; no conocen `Request` ni detalles HTTP.
- **Modelos:** datos, relaciones, scopes, accessors; validaciones de integridad (unique, exists). Sin reglas de negocio pesadas (delegar a servicios).
- **Form Requests:** reglas de validación de entrada; rangos y opciones deben derivarse de config/catálogos (inyección o `app()->make()`), no literales.
- **DTOs/Value objects:** estructuras inmutables para datos compuestos (ej. resultado de liquidación, desglose de aportes); evitan arrays anónimos y magic keys.

### 1.3 Prohibiciones explícitas (anti‑hardcodeo)

- No usar números mágicos ni porcentajes en código (ej. `0.125`, `12.5`, `1750905`). Leer desde `ContributionParametersResolver` o tablas de parámetros.
- No fijar en código: topes IBC, días de pago (2–16), códigos de tipo de cotizante, tipos de planilla PILA (E, I, S, Y, N, A). Todo en BD o config.
- No duplicar listas de opciones (ej. “tipos de cotizante”) entre backend y frontend; el backend expone endpoints o props con opciones derivadas de catálogos.
- No poner lógica de negocio en modelos Eloquent más allá de scopes y accessors simples; cálculos y validaciones complejas en servicios.
- No usar `config()` para valores que cambian por normativa (esos van en BD con vigencia temporal).

---

## 2. Dónde colocar cada tipo de configuración

```
Parámetros con vigencia (normativa)     → contribution_parameters (valid_from, valid_to)
Catálogos de negocio (EPS, AFP, etc.)  → tablas propias + seeders
Estados y códigos fijos del sistema     → Enums PHP (valores = los de BD)
Configuración por entorno              → .env + config/*.php
Mensajes y etiquetas                   → lang/es/*.php (o lang según locale)
Reglas “si código X entonces Y”        → Servicios/Reglas que lean catálogos (ej. ContributorTypeRules)
```

### 2.1 Ubicaciones en el proyecto (referencia)

| Concepto | Ruta actual / sugerida |
|----------|------------------------|
| Parámetros de contribución | `app/Modules/SocialSecurity/Services/ContributionParametersResolver.php` → tabla `contribution_parameters` |
| Cálculo de aportes | `app/Modules/SocialSecurity/Services/ContributionCalculator.php` (solo usa datos del resolver) |
| Enums de dominio SS | `app/Modules/SocialSecurity/Enums/PayrollStatus.php`; ampliar en `app/Modules/SocialSecurity/Enums/` según necesidad |
| Enums de pacientes/afiliados | `app/Modules/Patients/Enums/DocumentType.php`, etc. |
| Configuración por entorno | `config/*.php` leyendo `env()`; claves en `.env.example` |
| Traducciones | `lang/es/` (crear `lang/es/social_security.php` para mensajes del módulo) |
| Reglas por tipo cotizante | Crear `app/Modules/SocialSecurity/Services/ContributorTypeRules.php` (o equivalente) que consulte catálogos |
| Form Requests que usan parámetros | `app/Modules/Patients/Requests/CreateAffiliateRequest.php`, `StorePayrollRequest.php` — inyectar resolver, no literales |

---

## 3. Fases de implementación (orden ejecutable)

### Fase 0 — Consolidación y reglas (sin nuevas features)

**Objetivo:** Asegurar que no quede hardcodeo en lo ya construido y que toda lectura de parámetros/catálogos pase por puntos únicos.

| # | Tarea | Criterio de aceptación | Referencia |
|---|--------|------------------------|------------|
| 0.1 | Auditoría de constantes | Buscar en código: números literales (porcentajes, montos), strings de tipo cotizante/planilla, rangos de día de pago. Documentar y reemplazar por lectura desde `ContributionParametersResolver` o catálogos. | NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md |
| 0.2 | Centralizar mensajes de validación | Mensajes de Form Requests y errores de negocio en `lang/` (ej. `lang/es/validation.php`, `lang/es/social_security.php`). Controladores y servicios usan `__()` o excepciones con clave de traducción. | — |
| 0.3 | Enums alineados con BD | Verificar que todos los enums (PayrollStatus, DocumentType, etc.) reflejen exactamente los valores almacenados en BD; etiquetas para UI desde lang o método `label()`. | — |
| 0.4 | Reglas de negocio en un solo lugar | Tipos de cotizante y reglas (salud, pensión, ARL, parafiscales, proporcional) solo en `ContributorTypeRules` y servicios que lean parámetros; ningún `if ($code === '01')` fuera de esa capa. | ContributorTypeRules, ContributionCalculator |

**Entregable:** Código base sin constantes de negocio en controladores/modelos; parámetros y catálogos como única fuente.

---

### Fase 1 — Tipos de planilla PILA y flujo operativo

**Objetivo:** Introducir tipos de planilla según normativa (E, I, S, Y, N, A) y flujo de estados de pago sin hardcodear códigos ni mensajes.

| # | Tarea | Criterio de aceptación | Referencia |
|---|--------|------------------------|------------|
| 1.1 | Catálogo de tipos de planilla | Tabla `payroll_types` (code, name, description, is_correction, is_mora, ...) o enum persistido. Códigos: E, I, S, Y, N, A. Seed con datos oficiales. Servicios leen de catálogo, no literales. | ARQUITECTURA (tipos PILA) |
| 1.2 | Campo `payroll_type` en planillas | Migración: `payrolls.payroll_type` (FK o string indexado). Valor por defecto según tipo de cotizante del afiliado (regla en servicio que consulte catálogo). | — |
| 1.3 | Reglas por tipo de planilla | Servicio o clase que, dado `payroll_type`, devuelva reglas aplicables (quién paga, si aplica mora, si es corrección). Alimentado por catálogo o config, no switch con literales. | — |
| 1.4 | Flujo de estados de pago | Estados intermedios (ej. Pago recibido, Validado banco, Aprobado token, Soportes enviados) en BD: nueva tabla `payroll_payment_states` o campos en `payrolls`/`affiliate_payments`. Transiciones validadas por servicio; mensajes de error desde lang. | TAREAS_SEGURIDAD_SOCIAL |
| 1.5 | Panel “Qué hacer hoy” | Vista que consulte: vencimientos (DueDateCalculator + parámetros), mora (estado OVERDUE), pendientes de validación/soporte. Sin fechas ni rangos en código; uso de `now()`, config de timezone y parámetros de BD. | — |

**Entregable:** Planillas tipadas según PILA; flujo de pago trazable; panel operativo sin constantes.

---

### Fase 2 — Mora, intereses y recibos

**Objetivo:** Cálculo de mora e intereses parametrizado; generación de recibos con plantillas y montos desde BD.

| # | Tarea | Criterio de aceptación | Referencia |
|---|--------|------------------------|------------|
| 2.1 | Parámetros de mora e intereses | Tabla de parámetros o `contribution_parameters`: tipo MORA/INTEREST, vigencia, tasa o regla. Servicio `MoraInterestService` que calcule usando solo esos parámetros. | NORMATIVA |
| 2.2 | Planilla tipo A (mora) | Creación de planillas de tipo A a partir de planillas vencidas; montos e intereses según parámetros. Sin tasas en código. | — |
| 2.3 | Plantilla de recibo | Plantilla (Blade o similar) con placeholders; valores (planilla, administración, afiliación, IVA) desde servicio que lea config o parámetros (ej. valor administración por planilla). Claves de texto en lang. | — |
| 2.4 | Generación y almacenamiento de PDF | Servicio que genere PDF del recibo y lo guarde en `support_documents` o tabla análoga; ruta y nombre desde config de almacenamiento. | — |

**Entregable:** Mora e intereses calculados por parámetros; recibos generados sin literales de negocio.

---

### Fase 3 — Importación y reconciliación

**Objetivo:** Importación DataSegura con reglas y mensajes configurables; reconciliación sin hardcodeo de columnas ni valores.

| # | Tarea | Criterio de aceptación | Referencia |
|---|--------|------------------------|------------|
| 3.1 | Mapeo columnas → campos en config | Config (PHP o BD) que defina mapeo columna Excel → campo BD y transformaciones (split nombre, normalizar enums). ImportService lee ese mapeo; no columnas fijas en código. | mapeo_datasegura_a_base_de_datos.md |
| 3.2 | Validaciones de importación | Reglas de validación (rangos de fecha, documentos únicos, códigos válidos) que usen los mismos parámetros y catálogos que el resto del sistema. Mensajes en lang. | — |
| 3.3 | Reconciliación CSV vs BD | Servicio de reconciliación que compare snapshot importado con estado en BD; diferencias según reglas configurables; reporte con claves de mensaje traducibles. | — |

**Entregable:** Importación y reconciliación gobernadas por config/catálogos; cero literales de negocio en importador.

---

### Fase 4 — Auditoría, políticas y cierre

**Objetivo:** Auditoría y permisos de forma consistente; historial por usuario sin duplicar lógica.

| # | Tarea | Criterio de aceptación | Referencia |
|---|--------|------------------------|------------|
| 4.1 | Políticas por recurso | Policies para Payroll, Payer, Affiliate (y demás recursos sensibles): `view`, `update`, `settle`, `approve`, etc. Controladores usan `authorize()`; mensajes 403 desde lang. | — |
| 4.2 | Eventos y listeners | Eventos de dominio (ej. `SettlementCreated`, `PaymentRegistered`) y listeners que registren en tabla de auditoría o logs. Payload sin datos sensibles; referencias por ID. | — |
| 4.3 | Historial por usuario | Consulta de acciones por usuario desde tabla de auditoría o `payroll_trackings` + equivalente para otros módulos; filtros por fecha y entidad desde parámetros de request. | — |

**Entregable:** Acceso controlado por políticas; trazabilidad por eventos; historial consultable sin hardcodeo.

---

## 4. Estándares de código (checklist por PR)

- [ ] No hay números mágicos ni porcentajes literales; se usan `ContributionParametersResolver` o tablas de parámetros.
- [ ] No hay strings de tipo de cotizante, tipo de planilla o estado en lógica de negocio; se usan enums o catálogos.
- [ ] Mensajes de error y etiquetas en `lang/` o desde BD; no en controladores/vistas.
- [ ] Controladores delgados: validan, llaman servicio, devuelven respuesta.
- [ ] Servicios reciben DTOs o valores escalares; no inyectan `Request`.
- [ ] Operaciones que modifican más de una entidad o actualizan estados críticos van en `DB::transaction()`.
- [ ] Nuevas reglas de negocio (por tipo de cotizante, tipo de planilla, mora) en servicios o clases de reglas que lean catálogos/parámetros.
- [ ] Tests unitarios o de integración para cálculos y reglas; datos de prueba desde factories o seeds, no literales dispersos.

---

## 5. Orden de ejecución recomendado

1. **Fase 0** (1–2 semanas): auditoría y eliminación de hardcodeo en código existente; centralización de mensajes y enums.
2. **Fase 1** (2–3 semanas): tipos de planilla PILA, flujo de estados de pago, panel operativo.
3. **Fase 2** (2 semanas): mora/intereses parametrizados, generación de recibos.
4. **Fase 3** (1–2 semanas): ImportService y reconciliación configurables.
5. **Fase 4** (1 semana): políticas, eventos de auditoría, historial por usuario.

Cada fase termina con revisión de cumplimiento del checklist de la sección 4 y con actualización de este documento si se incorporan nuevos principios o capas (ej. Actions, Repositories) de forma estándar.

---

## 6. Referencia cruzada con documentos existentes

| Documento | Uso en este plan |
|-----------|-------------------|
| PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md | Fases 0–3 originales; este plan las reinterpreta con foco en no hardcodear y en fuentes únicas de verdad. |
| TAREAS_SEGURIDAD_SOCIAL.md | Criterios de aceptación por ítem; aquí se añaden criterios de “sin constantes” y uso de parámetros/catálogos. |
| ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md | Tipos de planilla, normativa, capas; se respetan y se refuerza parametrización. |
| NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md | Origen de la regla “parametrización obligatoria”; evita magic numbers en cálculos. |
| mapeo_datasegura_a_base_de_datos.md | Fuente de verdad para Fase 3 (importación); mapeo debe volcarse a config/catálogos, no codificado. |

---

*Documento de arquitectura y plan de implementación — Código limpio y cero hardcodeo. Actualizar al añadir fases o principios.*
