# Viabilidad e implementación — Arquitectura módulo gestión PILA

**Referencia:** `docs/ARQUITECTURA_MODULO_GESTION_PILA.md`  
**Fecha:** Marzo 2026  
**Contexto:** Refactorización del módulo de seguridad social; el módulo “Pacientes” pasa a considerarse **Afiliados** como concepto de negocio.

---

## 1. Resumen ejecutivo

El documento **ARQUITECTURA_MODULO_GESTION_PILA.md** está bien alineado con la normativa colombiana (PILA, Decreto 1990, Resolución 2388) y con el reemplazo del Excel DataSegura. **Es factible implementarlo** aprovechando el módulo de **Afiliados** (hoy carpeta `Patients`) como base y sustituyendo el módulo **SocialSecurity** actual por la nueva estructura de datos y servicios descrita en el documento.

| Criterio | Conclusión |
|----------|------------|
| Alineación con PILA y DataSegura | Alta: bloques A–H del Excel cubiertos, credenciales cifradas, fecha límite por Decreto 1990. |
| Base existente (Afiliados) | Reutilizable: tabla `affiliates` y modelo ya existen; solo ajustes de campos y relaciones. |
| Módulo SocialSecurity actual | Descartable para esta refactor: se reemplaza por el nuevo diseño (employers, affiliations, credenciales, catálogos unificados). |
| Riesgo técnico | Medio: migración de datos y actualización de referencias en Appointments, Authorizations, etc. |
| Esfuerzo estimado | 4 sprints según el doc; viable en el orden propuesto. |

---

## 2. Mapeo documento de arquitectura ↔ estado actual

### 2.1 Entidades principales

| Arquitectura (doc) | Estado actual | Acción |
|--------------------|---------------|--------|
| **employers** (empleadores/pagadores) | **payers** en SocialSecurity | Renombrar/evolucionar: añadir `payment_business_day`, `check_digit`, `is_self_employed`; nombre de tabla puede ser `employers` (doc) o mantener `payers` con mismos campos. |
| **affiliates** (afiliados/cotizantes) | **affiliates** en Patients | **Mantener.** Ajustar: añadir `employer_id` (o seguir usando relación vía perfil), `client_type` (hoy en perfil como `client_type_id`), `full_name` opcional (ya hay `getFullNameAttribute`). |
| **affiliations** (datos operativos y laborales) | **social_security_profiles** | **Reemplazar concepto:** el doc agrupa en una sola tabla tipo de cotizante, operador PILA, salario, entidades (ARL, CCF, EPS, AFP), facturación y estado de pago. Hoy está repartido en `social_security_profiles` + FKs a catálogos. Habría que añadir campos del doc: `last_novelty_type`, `last_novelty_date`, `billing_type`, `last_document_number`, `last_payment_period`, `payment_status`, `self_employed`, etc. |
| **pila_credentials** (credenciales operador PILA) | **operator_credentials** (por afiliado, tipo PILA/ARL/EPS/etc.) | **Rediseñar:** el doc exige credenciales PILA por **empleador** (no por afiliado), en tabla propia. Actualmente las credenciales están por afiliado y en un solo modelo. Separar: (1) `pila_credentials` (por employer), (2) `portal_credentials` (ARL/EPS/AFP/CCF, por employer o affiliate). |
| **portal_credentials** (ARL, EPS, AFP, CCF) | **operator_credentials** (provider_type) | Ver arriba: nueva tabla `portal_credentials` con `entity_type`, `entity_id` (FK a catálogo unificado), `employer_id`/`affiliate_id`, `is_not_applicable`. |
| **affiliate_notes** | No existe como tabla | **Crear** tabla `affiliate_notes` (tipo, contenido, is_pinned, created_by). Las observaciones hoy están en `social_security_profiles.observations` (texto libre); el doc pide notas estructuradas. |

### 2.2 Catálogos

| Arquitectura (doc) | Estado actual | Acción |
|--------------------|---------------|--------|
| **cotizante_types** (17 tipos) | **contributor_types** (24 códigos PILA con reglas) | Mapear: el doc usa 17; el sistema ya tiene más y reglas (is_dependent, parafiscales, etc.). Mantener tabla `contributor_types` y alinear nombres/códigos con el doc; opcional renombrar a `cotizante_types` si se quiere coincidir 100% con el doc. |
| **social_entities** (AFP, ARL, CCF, EPS, SENA, ICBF en una tabla) | **eps**, **afps**, **arps**, **ccfs** (tablas separadas) + **Eps** en Patients | **Decisión de diseño:** (A) Unificar en una sola tabla `social_entities` con `type` (AFP, ARL, CCF, EPS, …) como en el doc, o (B) Mantener tablas separadas y que `affiliations` sigan con FKs a `eps`, `afps`, `arps`, `ccfs`. La opción (A) simplifica FKs y coincide con el doc; (B) minimiza cambios. Recomendación: **unificar en `social_entities`** a medio plazo para integridad y mantenimiento. |
| **risk_classes** (clases de riesgo ARL) | **ArpRiskClass** enum + posible tabla | El doc define tabla con nivel, clase romana, descripción, tarifa. Hoy hay enum. Añadir tabla `risk_classes` (o equivalente) si se quieren tarifas editables; si no, mantener enum con tarifas en config/servicio. |

### 2.3 Servicios

| Doc | Estado actual | Acción |
|-----|---------------|--------|
| **DeadlineService** (fecha límite por Decreto 1990) | **DueDateCalculator** en SocialSecurity | Reutilizar/renombrar: misma responsabilidad; alinear interfaz con el doc (empleador + período AAAAMM → fecha límite). |
| **CredentialService** (cifrado + auditoría) | **OperatorCredential** con cifrado en modelo | Extraer lógica a **CredentialService**: cifrado/descifrado y **auditoría obligatoria** en cada consulta de contraseña (el doc es explícito). |
| **AffiliateImportService** (importación Excel) | No existe | Crear para migración única desde DataSegura. |
| **PaymentStatusService** (transiciones de estado, vencimientos) | Lógica repartida (Payroll, Dashboard) | Centralizar en **PaymentStatusService** y job programado (día 1 de cada mes) como indica el doc. |

### 2.4 Planilla y aportes (fuera del alcance estricto del doc)

El documento indica que el módulo de **Planilla PILA / Aportes** “viene del Access” y no lo reemplaza en este módulo. En el código actual existen **Payroll**, **PayrollService**, **ContributionCalculator**, **ContributionParameter**, etc. Decisiones posibles:

- **Opción A:** Dejar planilla y aportes en un submódulo o módulo aparte que consuma **employers**, **affiliates** y **affiliations** del nuevo diseño (sin duplicar lógica de perfil).
- **Opción B:** Incluir en el mismo módulo de “gestión PILA” la parte de listado/estado de pagos y que el cálculo de aportes use los datos de **affiliations** y catálogos.

En ambos casos, la **fuente de verdad** para tipo de cotizante, IBC, EPS, AFP, ARL, CCF debe ser la nueva estructura (affiliations + social_entities/catálogos).

---

## 3. Módulo “Pacientes” vs “Afiliados”

- **Concepto de negocio:** El módulo que gestiona personas en el sistema es de **Afiliados** (cotizantes y beneficiarios), no solo “pacientes”. Las rutas y la UX ya hablan de “afiliados” en muchos sitios.
- **Código actual:** La carpeta sigue siendo `app/Modules/Patients` (nombre técnico). No es obligatorio renombrar la carpeta de inmediato; sí conviene:
  - **Documentación y UX:** Referirse siempre a “Afiliados”.
  - **Rutas:** Mantener `/affiliates` (ya así).
  - **Renombrar módulo en código:** Opcional y costoso (muchos `use` y referencias); puede hacerse en una fase posterior (p. ej. `Patients` → `Affiliates` en el namespace y en `ModuleServiceProvider`).

Conclusión: **no bloquea la refactor.** Se puede implementar la arquitectura del doc manteniendo el namespace `Patients` y tratando el concepto como “Afiliados” en todo lo nuevo.

---

## 4. Dependencias que hay que preservar

Estos puntos deben seguir funcionando tras la refactor:

| Dependiente | Qué usa | Cómo preservar |
|-------------|---------|----------------|
| **Appointments** | Affiliate, status (activo/inactivo) | Affiliate sigue en Patients; status ya es enum. |
| **AppointmentRequests** | Affiliate, validación “Serviconli gestionado” | Mantener relación Affiliate ↔ employer/payer y scope `whereServiconliManaged` (basado en client_type o payer). |
| **Authorizations** | Affiliate | Sin cambio. |
| **HistoriaClinica** | Affiliate | Sin cambio. |
| **AffiliateTaskController** (tareas pendientes) | Affiliate | Sin cambio. |
| **AffiliatePaymentController** | Affiliate, pagos | Si “payments” se integra con estado de pago del doc, alimentar desde **affiliations** o tabla de pagos vinculada a affiliate. |
| **IndependentContract** | Affiliate, Payer, IBC/contratos | Los contratos pueden seguir ligados a `affiliate_id` y `payer_id`; si Payer se renombra a Employer, actualizar FK y modelo. |
| **Payroll** (si se mantiene) | Affiliate, perfil SS, parámetros | Que Payroll use la nueva fuente: **affiliations** + catálogos (y, si aplica, employers). |

Todas las referencias a `SocialSecurityProfile`, `Payer`, `OperatorCredential` deberán actualizarse a los nuevos modelos (Affiliation, Employer/Payer, PilaCredential, PortalCredential) según se implemente el nuevo diseño.

---

## 5. Estrategia recomendada de implementación

### 5.1 Enfoque: reemplazo controlado del módulo SocialSecurity

1. **Mantener** el módulo **Patients** (Afiliados) y la tabla **affiliates** como base.
2. **Introducir** las tablas y modelos del doc en un nuevo módulo o dentro de un “nuevo” SocialSecurity:
   - **employers** (o extender **payers** con los campos del doc y, si se desea, alias “employers” en código).
   - **affiliations** (sustituto de `social_security_profiles`): misma relación 1:1 con `affiliates`, con todos los campos operativos del doc.
   - **pila_credentials**, **portal_credentials**, **affiliate_notes**.
   - Catálogos: **cotizante_types** (o seguir con **contributor_types**), **social_entities** (o migrar desde eps/afps/arps/ccfs), **risk_classes**.
3. **Migrar datos** desde:
   - `payers` → `employers` (o actualizar payers).
   - `social_security_profiles` → `affiliations` (mapeo de columnas + FKs a social_entities si se unifican catálogos).
   - `operator_credentials` → `pila_credentials` (por employer) + `portal_credentials` (por tipo de entidad).
4. **Servicios:** Implementar **DeadlineService** (o refactor de DueDateCalculator), **CredentialService** (cifrado + auditoría), **PaymentStatusService**, **AffiliateImportService** para Excel.
5. **Desactivar o eliminar** progresivamente el código antiguo de SocialSecurity (perfiles antiguos, uso directo de OperatorCredential por afiliado para PILA, etc.) una vez migrado y probado.

### 5.2 Nombre del módulo Laravel

- **Opción A:** Nuevo módulo **PilaManagement** como en el doc: limpio y alineado con el documento.
- **Opción B:** Mantener **SocialSecurity** y reestructurar por dentro (employers, affiliations, credenciales, catálogos y servicios del doc).

Ambas son viables. La A deja claro que es “gestión PILA”; la B evita cambiar rutas y nombres de módulo en el ecosistema.

### 5.3 Orden sugerido (alineado con el doc)

1. **Sprint 1 — BD y catálogos:** Migraciones para employers (o payers ampliados), affiliations (nueva estructura), pila_credentials, portal_credentials, affiliate_notes; seeders para cotizante_types, social_entities, risk_classes (o equivalente). Modelos Eloquent y relaciones.
2. **Sprint 2 — Afiliados y empleadores:** CRUD empleadores, CRUD afiliados (ya existe; adaptar a employer_id y client_type si se mueven), CRUD afiliaciones; listado principal con filtros; ficha del afiliado (pestañas 1–4); DeadlineService.
3. **Sprint 3 — Credenciales:** CredentialService, UI ver/editar credenciales con permisos y auditoría, migración de credenciales desde Excel/actuales.
4. **Sprint 4 — Dashboard y automatizaciones:** KPIs, calendario de vencimientos, job de transiciones de estado de pago, exportaciones, notificaciones.

---

## 6. Conclusión

- **Viabilidad:** **Alta.** El documento ARQUITECTURA_MODULO_GESTION_PILA.md es implementable sobre la base actual de Afiliados (Patients) y sustituyendo el módulo SocialSecurity por la nueva arquitectura.
- **Módulo Afiliados:** Se mantiene como base; el nombre de negocio es “Afiliados”; el nombre de carpeta `Patients` puede seguir mientras se documenta y se planifica un eventual renombrado.
- **Módulo SocialSecurity actual:** Puede descartarse para esta refactor en el sentido de reemplazar su modelo de datos y flujos por employers, affiliations, credenciales y catálogos del doc; la funcionalidad de planilla/aportes puede reubicarse o reimplementarse sobre la nueva estructura.
- **Próximo paso recomendado:** Fijar opción de nombre de módulo (PilaManagement vs SocialSecurity), decidir unificación de catálogos en `social_entities` y luego ejecutar Sprint 1 (BD + catálogos + modelos) sin tocar aún las rutas públicas de Afiliados.

---

---

## 7. Decisiones de diseño pendientes (para el equipo)

| Decisión | Opciones | Recomendación |
|----------|----------|---------------|
| Nombre del módulo nuevo/refactorizado | **PilaManagement** (doc) vs **SocialSecurity** (actual) | PilaManagement si se quiere un corte claro con el pasado; SocialSecurity si se prioriza menos cambio de rutas y referencias. |
| Catálogo de entidades | **Una tabla `social_entities`** (doc) vs **tablas separadas** (eps, afps, arps, ccfs) | Una sola tabla simplifica FKs y mantenimiento; migración desde tablas actuales con un seeder o script. |
| Tabla de empleadores | **Nueva tabla `employers`** vs **ampliar `payers`** | Ampliar `payers` con los campos del doc (`payment_business_day`, `check_digit`, `is_self_employed`) reduce migración; crear `employers` alinea 100% con el doc y evita confusiones semánticas. |
| Renombrar carpeta Patients → Affiliates | Sí (namespace + rutas) vs No (solo en docs/UX) | No bloqueante; puede hacerse en una fase posterior con búsqueda/reemplazo y tests. |

---

*Documento generado a partir de ARQUITECTURA_MODULO_GESTION_PILA.md y del estado actual del código (Patients, SocialSecurity).*
