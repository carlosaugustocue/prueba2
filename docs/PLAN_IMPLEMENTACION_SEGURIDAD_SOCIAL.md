# Plan de implementación: Refactor Pacientes → Afiliados y Módulo de Seguridad Social

**Documento:** Plan paso a paso para arquitectos y desarrolladores  
**Versión:** 1.0  
**Stack:** Laravel 12 + Vue 3 + Inertia.js + MySQL  
**Principio:** Modelo normalizado atómico — cada tabla una responsabilidad.

**Documentos de referencia obligatorios:**

- **[docs/mapeo_datasegura_a_base_de_datos.md](mapeo_datasegura_a_base_de_datos.md)** — Mapeo DataSegura (Excel) → tablas `affiliates`, `social_security_profiles`, `payers`, `novelties`, `operator_credentials`. Todas las decisiones de campos, enums, validaciones e ImportService deben alinearse con ese documento.
- **[docs/TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md)** — Tareas concretas y meticulosas por ítem (catálogos, pagadores, perfil SS, novedades, planillas, dashboard, roles, ImportService, Fase 2, Fase 3, post-MVP historia clínica). Criterios de aceptación por tarea y principios operativos (una sola fuente de verdad, trazabilidad, consistencia, claridad para el operador) para un sistema confiable y ordenado.
- **[docs/NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md](NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md)** — Complejidad normativa colombiana (múltiples entidades, legislación cambiante, cálculos complejos, PILA, tipos de contratación, plazos y sanciones). Recomendaciones obligatorias: parametrización, históricos por fecha, validaciones robustas, logging/trazabilidad, flexibilidad. Checklist de diseño antes de implementar cálculos o PILA.

---

## Índice

| Sección | Contenido |
|---------|-----------|
| **Resumen ejecutivo** | Una página con el camino crítico y entregables por fase |
| **0** | Convenciones, prerequisitos y archivos clave |
| **1** | Fase 0 — Refactor Patient → Affiliate (pasos 0.1 a 0.7) |
| **2** | Fase 1 — MVP Módulo de Seguridad Social |
| **3** | Fase 2 — Automatizaciones |
| **4** | Fase 3 — Reportería |
| **5** | Criterios de aceptación por fase |
| **6** | Matriz de riesgos |
| **7** | Orden de ejecución en una sola vista |
| **8** | Mapeo DataSegura (referencia e ImportService) |
| **9** | Contexto de negocio y normas (Serviconli) |
| **10** | Después del MVP (backlog) |

---

## 9. Contexto de negocio y normas — Seguridad Social

**Tener muy claro en todo el desarrollo del módulo de Seguridad Social:**

El alcance es la **Administración de Seguridad Social para independientes o contratistas del sector privado**. Serviconli respalda a todos los independientes, con personal especializado dispuesto a servir y acompañar en el proceso de seguridad social, en temas como: cuánto debe pagar según el monto del contrato, en qué tiempo debe hacerlo, cómo debe hacerlo para tener planilla, entre otros.

**Con Serviconli se puede contar para:**

- **Administración de los pagos de seguridad social mes a mes** como independiente.
- **Trámite de afiliaciones y retiros.**
- **Acompañamiento legal** para sus contratistas ante las entidades de seguridad social colombianas.

**Además, y de forma explícita en el producto:**

- **Administración de los pagos de seguridad social de empleados y contratistas** bajo el NIT correspondiente (pagadores / empresas).
- **Trámite de afiliaciones y retiros de contratistas.**

Todas las funcionalidades del módulo (afiliados, perfil SS, pagadores, planillas, novedades, vencimientos PILA, etc.) deben alinearse con estas normas y con la normativa colombiana (PILA, Decreto 1990 de 2016, etc.).

---

## 10. Después del MVP (backlog)

Una vez terminado el **MVP del módulo de Seguridad Social** (Fase 1 completada), se debe contemplar:

### 10.1 Soporte de historia clínica en citas

- Disponer de **soporte de historia clínica** asociado al afiliado (o a la cita), de modo que **en el momento de gestionar las citas** se pueda consultar dicha información cuando sea necesario.
- Incluye: diseño de almacenamiento (documentos, resúmenes, o módulo de historia clínica), permisos, y acceso desde la ficha del afiliado y/o desde la cita.

Otros ítems post-MVP se irán sumando aquí (Fase 2, Fase 3 y mejoras posteriores).

---

## Resumen ejecutivo

- **Fase 0 (bloqueante, ~1 semana):**  
  1) Backup + rama.  
  2) Una migración: crear `payers` → renombrar `patients` → `affiliates` → crear `social_security_profiles` → copiar datos EPS/AFP/ARL → borrar columnas SS de `affiliates` → cambiar FKs en `appointments` y `appointment_requests` a `affiliate_id`.  
  3) Modelos Affiliate, SocialSecurityProfile, Payer.  
  4) Backend: reemplazar Patient por Affiliate en todo el código; leer EPS/AFP/ARL desde `$affiliate->socialSecurityProfile`.  
  5) Frontend: rutas y páginas de “Pacientes” → “Afiliados”, props y API a `affiliates`.  
  6) Regresión: citas, afiliados, solicitudes, dashboard.

- **Fase 1 (MVP SS, ~5–6 semanas):** **Catálogos y configuración:** tablas catálogo (AFP, ARP, CCF, operador de pago, registro contable, etc.) y CRUD en módulo de configuración (solo admin). Módulo `SocialSecurity`: migraciones (novelties, payrolls, operator_credentials, support_documents, communication_logs, payroll_trackings), DueDateCalculator, CRUD pagadores y perfil SS (usando FKs a catálogos), novedades, planillas, dashboard, roles y middleware, frontend Vue del módulo.

- **Fase 2:** Recordatorios WhatsApp/email, communication_logs, batch planillas.  
- **Fase 3:** Reportería, exportación, auditoría, documentación.

**Camino crítico:** No se puede desarrollar el módulo de Seguridad Social sin tener primero `affiliates` y `social_security_profiles` (Fase 0). No se deben mezclar en la misma rama el refactor y features nuevas del módulo SS.

---

## 0. Convenciones y prerequisitos

### 0.1 Reglas de trabajo

- **Una rama por fase** (ej. `fase-0-refactor-afiliados`, `fase-1-mvp-ss`). No mezclar refactor con features nuevas.
- **Backup de BD** antes de cada migración estructural: `mysqldump -u user -p database > backup_pre_fase0.sql`
- **Tests manuales de regresión** tras cada subfase: login, listar, crear, editar, citas end-to-end.
- **Commit atómico**: un commit por “paso” lógico (ej. “Migración: crear payers y social_security_profiles”) para facilitar rollback.

### 0.2 Orden de lectura de este plan

1. **Fase 0** es bloqueante: sin ella no hay tabla `affiliates` ni `social_security_profiles`. El módulo de citas debe seguir funcionando al final de Fase 0.
2. **Fase 1** construye el MVP del Módulo de Seguridad Social (CRUD afiliados con perfil SS, pagadores, planillas, novedades).
3. **Fase 2** añade automatizaciones (recordatorios WhatsApp/email, trazabilidad).
4. **Fase 3** añade reportería y pulido.

### 0.3 Documentos de referencia

| Documento | Uso |
|-----------|-----|
| **[docs/mapeo_datasegura_a_base_de_datos.md](mapeo_datasegura_a_base_de_datos.md)** | **Fuente de verdad** para: columnas Excel → BD, enums (document_type con ppt/ptt, client_type, contributor_type), campos nuevos en affiliates (city, department), flujo del ImportService, validaciones (fechas corruptas, split de nombres, 2.340 sin clasificar → INACTIVO + notes), deduplicación de payers. No implementar ImportService ni ampliar esquema sin seguir este mapeo. |

### 0.4 Archivos clave a tener abiertos durante Fase 0

| Archivo | Uso |
|--------|-----|
| `app/Providers/ModuleServiceProvider.php` | Registrar módulo SocialSecurity y rutas |
| `app/Modules/Patients/` (luego Affiliates) | Modelo, controlador, requests, resource |
| `app/Modules/Appointments/Models/Appointment.php` | Relación `patient_id` → `affiliate_id` |
| `app/Modules/AppointmentRequests/Models/AppointmentRequest.php` | Idem |
| `database/migrations/` | Orden estricto de migraciones (ver 1.2) |
| `resources/js/Pages/Patients/` | Renombrar a Affiliates y actualizar props/rutas |

---

## 1. Fase 0 — Refactor: Pacientes → Afiliados (BLOQUEANTE)

**Objetivo:** Renombrar `patients` → `affiliates`, mover datos de seguridad social (EPS, AFP, ARL) a `social_security_profiles`, actualizar todo el código y el front para que “pacientes” pasen a ser “afiliados” sin romper citas.

**Duración estimada:** 1 semana (3–5 días de desarrollo + 1–2 de pruebas).

---

### 1.1 Paso 0.1 — Backup y rama

1. Crear backup de la base de datos.
2. Crear rama: `git checkout -b fase-0-refactor-afiliados`.
3. Anotar número de registros actuales para validar después:
   ```sql
   SELECT COUNT(*) FROM patients;
   SELECT COUNT(*) FROM appointments;
   SELECT COUNT(*) FROM appointment_requests;
   ```

---

### 1.2 Paso 0.2 — Migraciones (orden estricto)

Las migraciones deben ejecutarse en este orden. Crear **una sola migración** que haga todo el refactor estructural (recomendado) o varias numeradas de forma que se ejecuten en secuencia.

**Opción recomendada: una migración “refactor” que:**

1. **Crear tabla `payers`**  
   - Campos: id, name, document_type, document_number (unique), address, phone, email, contact_person, is_active, timestamps.  
   - Sin FKs a `patients` todavía.

2. **Renombrar tabla `patients` → `affiliates`**  
   - `Schema::rename('patients', 'affiliates');`  
   - Actualizar FK en `affiliates`: `holder_id` debe seguir apuntando a `affiliates.id` (ya lo hace por nombre de tabla en la constraint; en Laravel a veces hay que drop y recrear la FK si estaba nombrada con `patients`).  
   - **Según [mapeo DataSegura](mapeo_datasegura_a_base_de_datos.md):** agregar a `affiliates` los campos `city` varchar(100) nullable y `department` varchar(100) nullable (Cols J y K del Excel).  
   - **Enum `document_type`:** ampliar valores a **cc, ti, ce, pa, rc, nit, ppt, ptt** (mapeo: PPT = Permiso por Protección Temporal, PTT = Permiso Temporal de Permanencia). Actualizar el enum en código (ej. `App\Modules\Affiliates\Enums\DocumentType`) y, si la columna BD lo permite, no hace falta migración solo para el enum si ya es varchar(10).

3. **Crear tabla `social_security_profiles`**  
   - `id`, `affiliate_id` (FK UNIQUE a `affiliates`, cascadeOnDelete), `client_type` (enum: INDEPENDENT, DEPENDENT, SERVICONLI, FOREIGN_RESIDENT), `contributor_type` (varchar 10), `ibc` (decimal 12,2 nullable), `eps_id` (FK nullable a `eps`), `afp_name`, `arp_name`, `arp_risk_class`, `ccf_name`, `payer_id` (FK nullable a `payers`), `payment_operator`, `payment_day`, `payment_periodicity`, `has_parafiscales` (boolean default false), `accounting_registry`, `observations`, timestamps.

4. **Migrar datos de SS desde `affiliates` a `social_security_profiles`**  
   - `INSERT INTO social_security_profiles (affiliate_id, client_type, contributor_type, eps_id, afp_name, arp_name, arp_risk_class, created_at, updated_at)`  
   - `SELECT id, 'SERVICONLI', '01', eps_id, afp_name, arp_name, arp_risk_class, NOW(), NOW() FROM affiliates WHERE eps_id IS NOT NULL OR afp_name IS NOT NULL OR arp_name IS NOT NULL;`  
   - Solo para filas que tengan al menos un dato de SS. Los que no tienen no llevan perfil (relación 1:1 opcional).

5. **Eliminar columnas de SS de `affiliates`**  
   - Drop FK `eps_id` (si existe), luego `dropColumn(['eps_id', 'afp_name', 'arp_name', 'arp_risk_class'])`.

6. **Actualizar tabla `appointments`**  
   - `dropForeign(['patient_id'])`, `renameColumn('patient_id', 'affiliate_id')`, `foreign('affiliate_id')->references('id')->on('affiliates')->cascadeOnDelete()`.

7. **Actualizar tabla `appointment_requests`**  
   - Mismo patrón: `patient_id` → `affiliate_id`, FK a `affiliates`.

8. **Validar integridad**  
   - Conteos: mismos registros en `affiliates` que antes en `patients`. Mismo número de filas en `appointments` y `appointment_requests` con `affiliate_id` no nulo.

**Archivo sugerido:** `database/migrations/YYYY_MM_DD_HHMMSS_refactor_patients_to_affiliates_and_social_security_profiles.php`

---

### 1.3 Paso 0.3 — Modelo Affiliate y módulo Affiliates

1. **Crear carpeta** `app/Modules/Affiliates/` (o renombrar `Patients` → `Affiliates` y actualizar namespace).
2. **Modelo `Affiliate.php`** (reemplazo de `Patient.php`):  
   - `$table = 'affiliates'`.  
   - Mismos `fillable` que Patient excepto `eps_id`, `afp_name`, `arp_name`, `arp_risk_class`.  
   - Relaciones: `socialSecurityProfile()` hasOne, `holder()` belongsTo(self), `beneficiaries()` hasMany(self), `appointments()` hasMany(Appointment).  
   - Mantener traits: SoftDeletes, HasUuid, Searchable.  
   - Casts: document_type, patient_type (renombrar a affiliate_type si se desea; el documento mantiene `patient_type` en BD), relationship_type, birth_date.  
   - Accessor `full_name` igual que en Patient.  
   - Método `getWhatsAppNumber()` igual.
3. **Modelo `SocialSecurityProfile.php`** en `app/Modules/SocialSecurity/Models/` (o en Affiliates si se prefiere):  
   - belongsTo Affiliate, belongsTo Payer (nullable), belongsTo Eps (nullable).  
   - Fillable según tabla del documento técnico.
4. **Modelo `Payer.php`** en mismo módulo:  
   - hasMany SocialSecurityProfile, hasManyThrough Affiliate via SocialSecurityProfile.
5. **Eliminar o deprecar** `app/Modules/Patients/Models/Patient.php` una vez Affiliate esté en uso (tras actualizar referencias).

---

### 1.4 Paso 0.4 — Actualizar referencias en backend (PHP)

Orden sugerido para no dejar referencias rotas:

1. **Appointments:**  
   - `Appointment`: fillable y relación `patient_id` → `affiliate_id`, relación `patient()` → `affiliate()` (belongsTo Affiliate).  
   - `AppointmentController`, `AppointmentService`: Patient → Affiliate, patient_id → affiliate_id, cargar `affiliate` y opcionalmente `affiliate.socialSecurityProfile.eps` donde se use EPS.  
   - `AppointmentResource`: exponer `affiliate` en lugar de `patient`; si se muestra EPS, usar `$this->affiliate->socialSecurityProfile?->eps`.
2. **AppointmentRequests:**  
   - `AppointmentRequest`: `patient_id` → `affiliate_id`, relación `patient()` → `affiliate()`.  
   - Controller, Request, Resource: mismo reemplazo. Validación “paciente activo” → “afiliado activo” (status en `affiliates`).
3. **Patients → Affiliates:**  
   - Renombrar `PatientController` → `AffiliateController`, `PatientService` → `AffiliateService`, `CreatePatientRequest` → `CreateAffiliateRequest`, `UpdatePatientRequest` → `UpdateAffiliateRequest`, `PatientResource` → `AffiliateResource`.  
   - En Create/Update: si el formulario sigue enviando datos de SS (EPS, AFP, ARL), guardarlos en `SocialSecurityProfile` (crear o actualizar) asociado al affiliate; no en `affiliates`.  
   - Rutas: `patients` → `affiliates` (Route::resource('affiliates', ...)), y rutas API `api/patients` → `api/affiliates`.
4. **Jobs y listeners:**  
   - `SendConfirmationJob`, `SendReminderJob`: donde se use `patient` (vía appointment), usar `affiliate`; para teléfono/WhatsApp seguir usando `$affiliate->getWhatsAppNumber()`.
5. **Admin y otros módulos:**  
   - `AdminCommunications`, `AdminWhatsApp`, `AdminMetrics`, etc.: reemplazar Patient por Affiliate y patient_id por affiliate_id donde aplique.
6. **ModuleServiceProvider:**  
   - En la lista de módulos, reemplazar `'Patients'` por `'Affiliates'` (y cargar rutas de `Affiliates`).  
   - Añadir módulo `'SocialSecurity'` cuando exista (Fase 1).
7. **Seeders (RoleSeeder, etc.):**  
   - Permisos `patients.*` → `affiliates.*`.  
   - Si hay roles que referencian “pacientes”, actualizar a “afiliados”.

**Checklist de búsqueda (grep):**  
- `Patient::` → `Affiliate::`  
- `patient_id` → `affiliate_id`  
- `'patient'` en relaciones y resources → `'affiliate'`  
- `$patient` → `$affiliate`  
- `patients.` (tabla) → `affiliates.` en queries raw o migraciones viejas  
- `eps_id`, `afp_name`, `arp_name`, `arp_risk_class` en modelos/vistas: deben leerse de `$affiliate->socialSecurityProfile` (con null check).

---

### 1.5 Paso 0.5 — Frontend (Vue + Inertia)

1. **Rutas web:**  
   - Todas las rutas que apunten a `/patients` deben apuntar a `/affiliates` (backend ya expone `affiliates`).  
   - Nombres de rutas: `patients.index` → `affiliates.index`, etc.
2. **Carpeta de páginas:**  
   - Renombrar `resources/js/Pages/Patients/` → `resources/js/Pages/Affiliates/`.  
   - En cada componente: props `patient` → `affiliate`, `patient_id` → `affiliate_id`.  
   - Formularios: `form.patient_type` puede mantenerse como clave (o renombrar a `affiliate_type` si el backend lo acepta); en backend se sigue guardando en columna `patient_type` en `affiliates` según documento.
3. **Enlaces y router:**  
   - `route('patients.show', patient.id)` → `route('affiliates.show', affiliate.id)`.  
   - `router.visit(route('patients.index'))` → `router.visit(route('affiliates.index'))`.  
   - Links del menú/layout: “Pacientes” → “Afiliados”, href a `affiliates.index`.
4. **Appointments y AppointmentRequests:**  
   - En listados y formularios donde se muestre o se elija “paciente”, usar “afiliado”: labels, props (ej. `affiliate`, `affiliate_id`), y llamadas a API de búsqueda (ej. `affiliates.search`).
5. **Dashboard y métricas:**  
   - Referencias a pacientes → afiliados (textos y datos).
6. **Validación:**  
   - Probar crear/editar afiliado, crear solicitud de cita, crear cita desde solicitud, editar cita, enviar recordatorio (si aplica). Todo debe funcionar sin errores 404 ni referencias a `patient` en la UI que rompan.

---

### 1.6 Paso 0.6 — Tabla `novelties` (opcional en Fase 0)

Si se desea tener el historial de novedades desde el inicio:

1. Crear migración `create_novelties_table`:  
   - affiliate_id (FK), type (enum), effective_date, description, old_value, new_value, registered_by (FK users), created_at.  
   - Índices: (affiliate_id, type), effective_date.
2. Modelo `Novelty` con relación a Affiliate.  
   - No es obligatorio implementar la lógica “al actualizar perfil SS crear novedad” en Fase 0; puede dejarse para Fase 1.

---

### 1.7 Paso 0.7 — Pruebas de regresión Fase 0

- [ ] Login con cada rol.  
- [ ] Listar afiliados, búsqueda, paginación.  
- [ ] Crear afiliado (cotizante y beneficiario).  
- [ ] Editar afiliado.  
- [ ] Ver detalle afiliado (con o sin perfil SS).  
- [ ] Listar solicitudes de cita; crear solicitud eligiendo afiliado.  
- [ ] Crear cita desde solicitud; crear cita directa.  
- [ ] Editar y ver detalle de cita (datos del afiliado y EPS si existe perfil).  
- [ ] Dashboard y métricas que usen afiliados.  
- [ ] Verificar que no queden rutas `/patients` ni nombres de ruta `patients.*` en uso.

Al finalizar Fase 0, el sistema debe comportarse igual que antes en flujos de citas y gestión de “pacientes”, pero con entidad “afiliados” y datos de SS en `social_security_profiles`.

---

## 2. Fase 1 — MVP Módulo de Seguridad Social

**Objetivo:** Tener operativo el módulo de Seguridad Social: catálogos configurables (AFP, ARP, CCF, etc.), CRUD afiliados (con perfil SS), CRUD pagadores, novedades, planillas básicas, dashboard mínimo, roles y permisos.

**Duración estimada:** 5–6 semanas.

---

### 2.0 Catálogos y módulo de configuración (prerrequisito Fase 1)

Los valores de referencia usados en `social_security_profiles` (AFP, ARP, CCF, operador de pago, registro contable, etc.) deben ser **tablas catálogo** con CRUD, no texto libre. Así se evita inconsistencia de nombres y se permite al administrador dar de alta/editar opciones sin tocar código.

#### 2.0.1 Valores posibles por campo (referencia para BD, enums, validaciones y seeders)

Tabla de verdad para implementación: tipos, enums vs catálogos y valores permitidos. Alinear migraciones, modelos, requests, ImportService y frontend con esta tabla.

| Columna Excel | Campo BD | Tipo | Valores posibles / Notas |
|---------------|----------|------|---------------------------|
| B | **client_type** | enum | `INDEPENDENT`, `DEPENDENT`, `SERVICONLI`, `FOREIGN_RESIDENT` |
| C | **contributor_type** | varchar(10) | Catálogo PILA: códigos 01–59 (lista completa en [mapeo DataSegura](mapeo_datasegura_a_base_de_datos.md#columna-c--contributor_type): 01 Dependiente, 02 Servicio Doméstico, 03 Independiente, 04 Madre sustituta, 05/57 Voluntario ARL, 12–59 según tabla). Tabla catálogo `contributor_types` (code, title, description) recomendada. |
| AC | **ibc** | decimal(12,2) | Rango: 290.000 — 14.235.800 (Ingreso Base de Cotización). Nullable. |
| AK | **eps_id** | FK → eps | **31 EPS** (catálogo PILA: Codigo Pila + Administradora + Subsistema). Ya existe tabla `eps`; EpsSeeder debe cargar las 31 entradas oficiales. + NULL. |
| AN | **afp_name** → **afp_id** | FK → afps | Catálogo: PORVENIR, COLPENSIONES, PROTECCIÓN, COLFONDOS, OLD MUTUAL, NULL. |
| AD | **arp_name** → **arp_id** | FK → arps | Catálogo: POSITIVA, SURA, COLMENA, COLPATRIA, NULL. |
| AE | **arp_risk_class** | varchar(20) o tinyint | Clase de riesgo ARL: `0`, `1`, `2`, `3`, `4`, `5` (0 = No aplica; 1–5 = mínimo a máximo). Enum o tabla pequeña. |
| AH | **ccf_name** → **ccf_id** | FK → ccfs | Catálogo: COMFENALCO QUINDÍO, COMFAMILIAR RISARALDA, COMFANDI, COMFAMILIAR CALDAS, COLSUBSIDIO, COMPENSAR, etc. + NULL. |
| Z | **payment_operator** → **payment_operator_id** | FK → payment_operators | Catálogo: ENLACE OPERATIVO, SIMPLE, ASOPAGOS, APORTES EN LINEA, SOI, MI PLANILLA, NULL. |
| V | **payment_day** | tinyint | 2–16 (auto-calculado según documento; DueDateCalculator). |
| AQ | **has_parafiscales** | boolean | `true` / `false`. |
| AS | **payment_periodicity** | enum | `CURRENT`, `OVERDUE` (nullable en BD si aplica). |
| AT | **accounting_registry** | varchar(50) o enum | `RECIBO_CAJA`, `FACTURA_ELECTRONICA`. Lista cerrada → enum en código (o tabla catálogo si se desea ampliar luego). |
| AR | **observations** | text | Texto libre. Nullable. |

**Resumen decisión implementación:**

- **Enums (código PHP):** `client_type`, `payment_periodicity`, `accounting_registry`. Opcionalmente `arp_risk_class` y `contributor_type` si se mantienen listas cerradas.
- **Tablas catálogo (CRUD en módulo Configuración):** `eps` (existente; **31 EPS** según tabla PILA — Codigo Pila, Administradora, Subsistema), `afps`, `arps`, `ccfs`, `payment_operators`. Recomendado: **`contributor_types`** con la lista completa PILA (código, título, descripción) — ver [mapeo DataSegura, tipos de cotizante](mapeo_datasegura_a_base_de_datos.md#columna-c--contributor_type). Opcional: `accounting_registries`.
- **Validaciones:** IBC en rango; `payment_day` 2–16; `contributor_type` en lista permitida; FKs no negativos.

**Entregables:**

1. **Tablas catálogo (migraciones):**
   - **`afps`**: id, name, code (opcional), is_active, timestamps. Ej.: PORVENIR, COLPENSIONES, PROTECCIÓN, COLFONDOS, OLD MUTUAL.
   - **`arps`**: id, name, code (opcional), is_active, timestamps. Ej.: POSITIVA, SURA, COLMENA, COLPATRIA.
   - **`ccfs`**: id, name, code (opcional), is_active, timestamps. Ej.: COMFENALCO QUINDÍO, COMFAMILIAR RISARALDA, COMFANDI.
   - **`payment_operators`**: id, name, code (opcional), is_active, timestamps. Ej.: ENLACE OPERATIVO, SIMPLE, ASOPAGOS, APORTES EN LÍNEA, SOI, MI PLANILLA.
   - **`accounting_registries`** (opcional): id, name, code, is_active, timestamps. Ej.: RECIBO_CAJA, FACTURA_ELECTRONICA.  
   - Valores fijos como **client_type**, **contributor_type**, **payment_periodicity**, **arp_risk_class** pueden quedar como enums en código (lista cerrada) o, si se desea que el negocio los edite, como tabla genérica `catalog_options` (type, value, label, sort_order) o tablas específicas.

2. **Actualizar `social_security_profiles`:**
   - Sustituir columnas de texto por FKs: `afp_id` (FK → afps), `arp_id` (FK → arps), `ccf_id` (FK → ccfs), `payment_operator_id` (FK → payment_operators), `accounting_registry_id` (opcional, FK → accounting_registries).
   - Migración de datos existentes: por cada perfil con `afp_name` no nulo, buscar o crear registro en `afps` por nombre y asignar `afp_id`; igual para ARP, CCF y operador de pago. Luego eliminar columnas antiguas (`afp_name`, `arp_name`, `ccf_name`, `payment_operator`, `accounting_registry` si se reemplaza).

3. **Módulo de configuración (o área Admin):**
   - Rutas bajo `/admin/configuracion` (o `/configuracion`) con middleware que restrinja a rol **admin** (o rol “configuración” si se define).
   - CRUD por cada catálogo: listado, crear, editar, activar/desactivar. Campos mínimos: nombre, código (opcional), activo.
   - Frontend Vue: páginas por catálogo (ej. Configuración → AFP, ARP, CCF, Operadores de pago) o una sola vista con pestañas/subsecciones.
   - Seeders con valores de **2.0.1** y del [mapeo DataSegura](mapeo_datasegura_a_base_de_datos.md): **EpsSeeder** (31 EPS según tabla PILA: Codigo Pila, Administradora, Subsistema); **ContributorTypeSeeder** (lista completa PILA: 01 Dependiente, 02 Servicio Doméstico, 03 Independiente, 04–59 según tabla en mapeo, incl. 57 si aplica); AfpSeeder; ArpSeeder; CcfSeeder; PaymentOperatorSeeder; opcional AccountingRegistrySeeder.

4. **Modelos y uso en perfil SS:**
   - Modelos `Afp`, `Arp`, `Ccf`, `PaymentOperator` (y opcional `AccountingRegistry`) con relación desde `SocialSecurityProfile` (belongsTo). En formularios de perfil SS y en ImportService, usar selects que lean de estas tablas en lugar de texto libre.

**Orden sugerido:** Implementar 2.0 al inicio de Fase 1 (rama `fase-1-mvp-ss`), antes del CRUD de perfil SS y del ImportService, para que ambos consuman ya los catálogos.

---

### 2.1 Estructura del módulo

Crear bajo `app/Modules/SocialSecurity/` (y opcionalmente `app/Modules/Config/` o `app/Modules/AdminConfig/` para catálogos):

```
SocialSecurity/
├── Controllers/
│   ├── AffiliateController.php    # CRUD persona (o reusar el de Affiliates y extender)
│   ├── SocialSecurityProfileController.php
│   ├── PayerController.php
│   ├── NoveltyController.php
│   ├── PayrollController.php
│   ├── DueDateController.php
│   ├── DashboardController.php
│   └── ...
├── Models/
│   ├── SocialSecurityProfile.php
│   ├── Payer.php
│   ├── Novelty.php
│   ├── Payroll.php
│   ├── OperatorCredential.php
│   ├── SupportDocument.php
│   ├── CommunicationLog.php
│   └── PayrollTracking.php
├── Services/
│   ├── DueDateCalculator.php
│   ├── PayrollService.php
│   ├── ReminderService.php
│   └── ImportService.php
├── Enums/
│   ├── ClientType.php
│   ├── NoveltyType.php
│   ├── PayrollStatus.php
│   └── ...
├── Requests/
├── Resources/
└── routes.php
```

Rutas bajo prefijo `/social-security`, middleware `auth` + `EnsureSocialSecurityRole`.

---

### 2.2 Pasos Fase 1 (resumen ordenado)

| # | Tarea | Detalle |
|---|--------|--------|
| 1.0 | **Catálogos y módulo de configuración** | Tablas `afps`, `arps`, `ccfs`, `payment_operators` (y opcional `accounting_registries`). Migración en `social_security_profiles`: columnas texto → FKs; migrar datos existentes. CRUD de cada catálogo en módulo Configuración (solo admin). Modelos Afp, Arp, Ccf, PaymentOperator. Ver **2.0** con detalle. |
| 1.1 | Migraciones complementarias | `operator_credentials`, `payrolls`, `support_documents`, `communication_logs`, `payroll_trackings`. Todas con `affiliate_id` o `payroll_id` según corresponda. Campo `social_security_role` en `users`. |
| 1.2 | Enums | ClientType, NoveltyType, PayrollStatus, ContributorType, PaymentPeriodicity, etc. (valores que no sean catálogo editable). |
| 1.3 | DueDateCalculator | Servicio que calcula fecha de pago según documento y tipo de cliente (Decreto 1990/2016). Usar festivos colombianos (ej. paquete o tabla). |
| 1.4 | CRUD Payers | Listado, crear, editar, ver. Validación NIT/documento único. |
| 1.5 | Perfil SS en Affiliate | Crear/editar `SocialSecurityProfile` desde pantalla de afiliado (o pantalla dedicada). Al guardar cambios de EPS/AFP/ARL, crear registro en `novelties`. |
| 1.6 | CRUD Novelties | Listado por afiliado, registro manual de novedad. |
| 1.7 | PayrollService + CRUD Planillas | Crear planilla por afiliado/mes/año; calcular due_date con DueDateCalculator; calcular valores (salud, pensión, ARL, CCF). Estados: PENDING, SETTLED, SENT_TO_CLIENT, PAID, OVERDUE. |
| 1.8 | Dashboard | Conteos por estado (pendiente, liquidado, pagado, en mora). Listas básicas (vencimientos hoy, próximos días). |
| 1.9 | Credenciales de operadores | Modelo OperatorCredential (cifrado con Laravel Crypt). CRUD por afiliado. |
| 1.10 | Soportes documentales | Subida/descarga de archivos vinculados a afiliado (y opcionalmente a planilla). |
| 1.11 | Roles y middleware | `social_security_role`: affiliations, payments, reports, admin. Middleware que compruebe rol antes de acceder a `/social-security/*`. |
| 1.12 | Frontend Vue del módulo | Páginas: listado afiliados (con perfil SS), pagadores, novedades por afiliado, planillas, dashboard. Formularios coherentes con API. |
| 1.13 | **ImportService DataSegura** | Implementar según **[docs/mapeo_datasegura_a_base_de_datos.md](mapeo_datasegura_a_base_de_datos.md)** (ver sección 8 de este plan). |

---

### 2.3 Dependencias entre tareas Fase 1

- **Catálogos (1.0)** deben existir antes de CRUD perfil SS (1.5) e ImportService (1.13): el perfil y el import usan afp_id, arp_id, ccf_id, payment_operator_id desde las tablas catálogo.  
- DueDateCalculator y tabla de festivos son base para Payroll (due_date).  
- Payers debe existir antes de poder asignar payer_id en SocialSecurityProfile.  
- Novelties puede alimentarse desde actualización de perfil SS (automático) y desde formulario manual.  
- Planillas dependen de SocialSecurityProfile (IBC, EPS, AFP, ARL, CCF) y de DueDateCalculator.

---

## 3. Fase 2 — Automatizaciones

- Plantillas WhatsApp para recordatorios de pago, planilla lista, mora.  
- Envío automático de recordatorios (scheduler o comando).  
- Módulo de email (Mailable): planillas adjuntas, recordatorios.  
- Tabla `communication_logs`: registrar cada envío (canal, tipo, estado, destinatario).  
- Batch de creación de planillas mensuales.  
- Notificaciones internas (ej. “nueva novedad” para rol de pagos).

---

## 4. Fase 3 — Reportería y cierre

- Dashboard avanzado (gráficos, tendencias).  
- Reportes por pagador/empresa.  
- Exportación Excel/PDF.  
- Auditoría completa (payroll_trackings).  
- Scheduler automático y documentación/capacitación.

---

## 5. Criterios de aceptación por fase

### Fase 0

- Base de datos: tabla `affiliates` con mismos datos que antes tenía `patients` (excepto columnas movidas a SS). Tabla `social_security_profiles` poblada para quienes tenían EPS/AFP/ARL.  
- Citas y solicitudes funcionan con `affiliate_id`.  
- No existen referencias a `Patient` ni a tabla `patients` en el código activo.  
- UI: “Afiliados” en lugar de “Pacientes”; rutas y props actualizadas.

### Fase 1

- Un usuario con rol de Seguridad Social puede gestionar afiliados (con perfil SS), pagadores, novedades y planillas.  
- Fecha de vencimiento de planilla se calcula según regla de negocio.  
- Dashboard muestra métricas básicas y listas de vencimientos.

### Fase 2

- Recordatorios por WhatsApp/email se envían y quedan registrados en `communication_logs`.

---

## 6. Matriz de riesgos (resumen)

| Riesgo | Mitigación |
|--------|------------|
| Pérdida de datos en migración | Backup previo; migración en una transacción si es posible; validar conteos. |
| Citas sin EPS tras refactor | Acceso vía `$affiliate->socialSecurityProfile?->eps`; mostrar “Sin EPS” si null; eager load donde se listen afiliados. |
| Rutas o nombres antiguos en front | Búsqueda global por `patients`, `patient`, `patient_id` y reemplazo sistemático; pruebas de regresión. |
| Rollback de Fase 0 | Rama separada; migración con `down()` que revierta renames y columnas (y datos de profiles → affiliates si se requiere). |

---

## 7. Orden de ejecución recomendado (una sola vista)

1. Backup BD + rama Fase 0.  
2. Una migración que: payers → rename patients→affiliates → agregar city, department a affiliates (mapeo DataSegura) → social_security_profiles → insert desde affiliates → drop columnas SS en affiliates → FKs appointments/appointment_requests. Enum document_type: incluir ppt, ptt (mapeo).  
3. Modelos Affiliate, SocialSecurityProfile, Payer (y Novelty si se incluye).  
4. Reemplazo completo en backend (Patient → Affiliate, patient_id → affiliate_id, acceso SS vía profile).  
5. Rutas y controladores: resource affiliates, actualizar Appointments y AppointmentRequests.  
6. Frontend: renombrar Patients → Affiliates, actualizar todas las referencias y rutas.  
7. Regresión completa.  
8. Fase 1 en rama nueva: **primero** catálogos y módulo de configuración (tablas afps, arps, ccfs, payment_operators; CRUD admin); **luego** migraciones complementarias del módulo SocialSecurity, actualización de social_security_profiles a FKs de catálogos, servicios (DueDateCalculator, PayrollService), CRUD perfil SS, pagadores, novedades, planillas, dashboard, frontend Vue del módulo.

Este documento debe tratarse como la **única fuente de verdad** para el orden de pasos; cualquier desviación (por ejemplo hacer cambios de SS en Affiliate antes de tener `social_security_profiles`) debe documentarse y validarse.

---

## 8. Mapeo DataSegura e ImportService

**Documento operativo a sustituir (módulo Seguridad Social):** `docs/DATA CENTRAL DE CITAS ACTUPELAEZALIZADO 202505 (1).xlsx`. Este archivo pertenece al módulo de Seguridad Social; la Central de Citas es un módulo aparte. La **única fuente de verdad** para el mapeo de ese Excel (o su hoja "DATA ACTUALIZADA 2025" / equivalente) a la base de datos es:

- **[docs/mapeo_datasegura_a_base_de_datos.md](mapeo_datasegura_a_base_de_datos.md)**

### 8.1 Resumen de obligaciones

| Tema | Qué hacer (según mapeo) |
|------|--------------------------|
| **Tabla affiliates** | Campos adicionales respecto a Patient: `city`, `department` (varchar 100 nullable). Enum `document_type`: incluir **ppt** y **ptt** además de cc, ti, ce, pa, rc, nit. |
| **Status** | Col A: ACTIVO/INACTIVO. Los 2.340 registros sin clasificar → status INACTIVO y marcar en `notes` (ej. "pendiente_clasificación"). |
| **Nombres (Col F)** | Split en first_name, second_name, last_name, second_last_name según número de palabras (reglas en mapeo). Revisión manual para nombres compuestos. |
| **Fechas** | Validar birth_date y effective_date: rechazar o corregir fechas corruptas (ej. 83247937). Rango razonable: > 1900-01-01 y < hoy. |
| **social_security_profiles** | Mapeo de columnas B, C, AC, AK, AN, AD, AE, AH, Z, V, AQ, AS, AT, AR. client_type: normalizar "COLOMBIANO RECIDENTE EN EL EXTERIOR" → FOREIGN_RESIDENT. contributor_type: solo código (01, 03, 57, etc.). eps_id: buscar en tabla `eps` por código extraído de col AK; "NO APLICA" → NULL. |
| **payers** | Deduplicar por document_number (col P). Un registro en `payers` por NIT/documento único; varios afiliados pueden compartir el mismo payer_id. Columnas N–U. |
| **novelties** | Cols X, Y: type (ENTRY, WITHDRAWAL, EPS_CHANGE según valor) y effective_date. Un registro por afiliado en la importación (la “última” novedad del Excel). |
| **operator_credentials** | Pares de columnas usuario/clave por proveedor (AA-AB, AF-AG, AI-AJ, AL-AM, AO-AP). Cifrar con Laravel Crypt. provider_type: PAYMENT_OPERATOR, ARL, CCF, EPS, AFP. |
| **Campos dinámicos** | last_invoice_number, last_payment_month, is_up_to_date, payment_observations **no** se migran; se calculan desde `payrolls` y DueDateCalculator. |

### 8.2 Orden del ImportService (por fila Excel)

1. **Payer** (cols N–U): buscar o crear por document_number → obtener payer_id.  
2. **Affiliate** (cols A, D–M): buscar o crear por document_number; split nombre (F); city/department (J, K); document_type con ppt/ptt; status (A).  
3. **SocialSecurityProfile** (cols B, C, AC, AK, AN, AD, AE, AH, Z, V, AQ, AS, AT, AR): eps_id por lookup en `eps`; calcular payment_day con DueDateCalculator; vincular payer_id.  
4. **Novelty** (X, Y): si hay tipo y fecha, INSERT.  
5. **OperatorCredentials** (pares AA–AP): por cada par con datos, INSERT cifrado.

Cualquier desviación del mapeo (nuevas columnas, otros valores de enum, otra orden) debe quedar documentada en el propio mapeo y acordada con el equipo.
