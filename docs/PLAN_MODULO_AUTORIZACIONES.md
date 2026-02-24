# Plan de implementación — Módulo de Autorizaciones Médicas

**Referencia:** [requisitos_autorizaciones_serviconli.md](requisitos_autorizaciones_serviconli.md)

**Objetivo:** Implementar el módulo de gestión de autorizaciones médicas integrado con Solicitudes de Cita y Citas, según los RF-AUT-01 a RF-AUT-18.

---

## 0. Principios obligatorios (modelo y alcance)

### 0.1 Modelo: Afiliado (Affiliate), nunca Paciente (Patient)

- En todo el sistema se usa el **modelo Affiliate** (afiliado). No existe modelo Patient en el dominio de citas y autorizaciones.
- Todas las relaciones y referencias en el módulo de autorizaciones deben ser a **`affiliate_id`** y al modelo **`App\Modules\Patients\Models\Affiliate`**.
- Solicitudes de cita (`AppointmentRequest`), citas (`Appointment`) y autorizaciones (`Authorization`) están vinculadas a **afiliados** mediante `affiliate_id`. No debe quedar en el código ningún uso de `patient_id` ni de un modelo Patient en este flujo.
- En documentación, mensajes de validación y UI se debe hablar siempre de **afiliado**, no de paciente.

### 0.2 Alcance: solo afiliados con Serviconli como pagador

- **Citas** y **autorizaciones** (y las **solicitudes de cita** que las originan) solo se gestionan para **afiliados cuyo pagador directo es Serviconli**.
- Según los requisitos: *"Afiliado: usuario registrado en Serviconli cuyo pagador directo es Serviconli. Tiene derecho a solicitar citas médicas y gestionar sus autorizaciones ante la EPS."*
- **Implementación:**
  - El afiliado tiene perfil de seguridad social (`social_security_profiles`) con **`payer_id`** apuntando al registro en `payers` que representa a **Serviconli** como empresa pagadora.
  - Identificación del pagador Serviconli: se recomienda un campo **`is_serviconli`** (boolean) en la tabla `payers`, con un único registro en `true`. Alternativa: configuración `config('serviconli.payer_id')`. En ambos casos, la regla es: *afiliado con `socialSecurityProfile.payer_id` = pagador Serviconli*.
- **Validaciones y filtros:**
  - Al **crear o editar una solicitud de cita**: el afiliado seleccionado debe tener perfil SS con pagador Serviconli; si no, rechazar con mensaje claro (*"Solo se gestionan solicitudes de cita para afiliados con Serviconli como pagador"*).
  - Al **crear una autorización**: el afiliado de la solicitud (o el seleccionado) debe ser afiliado con Serviconli como pagador.
  - En **listados** de solicitudes, citas y autorizaciones: por defecto (o de forma obligatoria) filtrar por afiliados que tengan pagador Serviconli, para no mostrar datos de afiliados de otros pagadores.
- **Scope reutilizable:** en el modelo `Affiliate` (o en un scope global) definir algo como `scopeWhereServiconliAsPayer($query)` que haga `whereHas('socialSecurityProfile.payer', fn ($q) => $q->where('is_serviconli', true))` (o la condición equivalente si se usa config). Usar este scope en controladores de AppointmentRequest, Appointment y Authorization al listar y al validar que el afiliado aplica.

---

## 1. Contexto del sistema actual

| Componente | Estado actual |
|------------|----------------|
| **Solicitudes (AppointmentRequest)** | Módulo `AppointmentRequests`: affiliate_id, type, priority, specialty, status (PENDING, IN_PROGRESS, COMPLETED, CANCELLED, FAILED), appointment_id. **Falta:** `requires_authorization` y estado "Pendiente de autorización". |
| **Citas (Appointment)** | Módulo `Appointments`: affiliate_id, appointment_request_id, authorization_number (texto libre). **Falta:** `authorization_id` (FK a autorización) y validaciones al confirmar. |
| **Afiliados** | `Affiliate` con `socialSecurityProfile` → eps_id, **payer_id**. Solo afiliados con pagador Serviconli gestionan citas y autorizaciones. EPS en `App\Modules\Patients\Models\Eps`. |
| **Pagadores** | `Payer` en módulo SocialSecurity; perfil SS tiene `payer_id`. Identificar pagador Serviconli vía `is_serviconli` en `payers` (recomendado) o config. |
| **Módulos** | Cargados vía `ModuleServiceProvider` desde `app/Modules/*/routes.php`. |

---

## 2. Diseño de datos

### 2.1 Nueva tabla `authorizations`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| uuid | uuid unique | Para URLs y API |
| appointment_request_id | FK appointment_requests | Solicitud vinculada (una autorización por solicitud en flujo normal) |
| affiliate_id | FK affiliates | Afiliado (denormalizado para listados y filtros) |
| eps_id | FK eps | EPS del afiliado (puede copiarse del perfil SS al crear) |
| service_type | string | Tipo de servicio: consulta especializada, procedimiento, examen, cirugía, etc. |
| diagnosis_or_reason | text nullable | Diagnóstico o motivo de la solicitud |
| status | enum | pending_radication, radicated, approved, denied, expired, in_appeal |
| radicated_at | date nullable | Fecha de radicación ante la EPS |
| authorization_number | string nullable | Número asignado por la EPS (cuando aprobada) |
| authorized_ips_name | string nullable | IPS autorizada para la prestación |
| valid_until | date nullable | Vigencia de la autorización |
| denial_reason | text nullable | Motivo de negación (cuando negada) |
| created_by | FK users nullable | |
| updated_by | FK users nullable | |
| timestamps, softDeletes | | |

### 2.2 Tabla `authorization_state_histories`

Trazabilidad de cambios de estado (RF-AUT-03).

| Campo | Tipo |
|-------|------|
| id | bigint PK |
| authorization_id | FK authorizations |
| from_status | string nullable |
| to_status | string |
| user_id | FK users nullable |
| notes | text nullable |
| created_at | timestamp |

### 2.3 Tabla `authorization_documents`

Documentos de soporte (RF-AUT-02): orden médica, resultados, historia clínica.

| Campo | Tipo |
|-------|------|
| id | bigint PK |
| authorization_id | FK authorizations |
| type | string | order_medica, resultados, historia_clinica, otro |
| file_path | string | Ruta en storage (ej. authorizations/{id}/archivo.pdf) |
| original_name | string | Nombre original del archivo |
| mime_type | string | |
| size | unsigned bigint | bytes |
| uploaded_by | FK users nullable |
| created_at | timestamp |

Validaciones: formatos PDF, JPG, PNG; tamaño máximo (ej. 10 MB) en request.

### 2.4 Cambios en tablas existentes

**appointment_requests**

- `requires_authorization` (boolean, default false).
- Nuevo estado en enum `RequestStatus`: `PENDING_AUTHORIZATION` = 'pending_authorization' (solicitud esperando que la autorización sea aprobada).

**appointments**

- `authorization_id` (FK authorizations nullable). Mantener `authorization_number` para mostrar; puede rellenarse desde la autorización al crear la cita desde una solicitud con autorización aprobada.

---

## 3. Estados de la autorización y transiciones

| Estado | Descripción | Transiciones permitidas |
|--------|-------------|-------------------------|
| pending_radication | Creada, falta radicar ante EPS | → radicated |
| radicated | Radicada, esperando respuesta EPS | → approved, denied |
| approved | Aprobada (número, IPS, vigencia) | → expired (por cron o manual) |
| denied | Negada (motivo registrado) | → in_appeal |
| in_appeal | En apelación con documentación | → approved, denied (según respuesta) |
| expired | Vigencia vencida sin uso | (final) |

Cada transición debe registrar en `authorization_state_histories` y, según RF-AUT-05, notificar al afiliado.

---

## 4. Mapeo RF → Implementación

| RF | Implementación |
|----|----------------|
| **RF-AUT-01** | Controller `AuthorizationController`: create/store desde formulario; datos: tipo servicio, diagnóstico, EPS, orden médica (documento), fecha radicación (opcional al crear). Estado inicial: pending_radication. |
| **RF-AUT-02** | Controller `AuthorizationDocumentController` o método en AuthorizationController: upload de archivos; validación formato (pdf, jpg, png) y tamaño; guardar en `authorization_documents` y storage. |
| **RF-AUT-03** | Enum `AuthorizationStatus`; método `Authorization->changeStatus(AuthorizationStatus $newStatus)` que valide transición, guarde en `authorization_state_histories` y dispare evento para notificación. |
| **RF-AUT-04** | Al cambiar estado a `approved`: formulario/modal para ingresar número, IPS y vigencia (valid_until). Actualizar autorización y luego cambiar estado. |
| **RF-AUT-05** | Listener `NotifyAffiliateOnAuthorizationStatusChange` (o integración con módulo comunicaciones): al cambiar estado, enviar notificación interna y/o correo/WhatsApp según configuración. |
| **RF-AUT-06** | Comando programado (scheduler): diario; buscar autorizaciones approved con valid_until en 5 y 2 días → enviar alertas; si valid_until < hoy y no hay cita vinculada → estado expired. |
| **RF-AUT-07** | Al cambiar a `denied`: campo denial_reason obligatorio. Opción "Iniciar apelación" → estado in_appeal y permitir adjuntar más documentos. |
| **RF-AUT-08** | En formulario de solicitud (Create/Edit): checkbox o campo `requires_authorization`. Opcional: reglas por tipo de servicio (tabla configurable). |
| **RF-AUT-09** | En Show de solicitud: si requires_authorization y no tiene autorización, botón "Crear autorización" → lleva a crear autorización con appointment_request_id y affiliate_id; al guardar, poner solicitud en PENDING_AUTHORIZATION. |
| **RF-AUT-10** | En acción "Crear cita" desde solicitud: si requires_authorization y (no hay autorización o autorización no está approved), bloquear y mostrar mensaje. |
| **RF-AUT-11** | Al cambiar autorización a approved: actualizar solicitud a IN_PROGRESS (o estado que permita agendar). Al cambiar a denied: opcionalmente marcar solicitud como FAILED o estado específico. |
| **RF-AUT-12** | En AppointmentService al crear/confirmar cita: si la solicitud requiere_authorization, validar que exista autorización approved y que hoy ≤ valid_until. |
| **RF-AUT-13** | Al crear cita desde solicitud con autorización aprobada: asignar appointment.authorization_id y opcionalmente appointment.authorization_number desde authorization.authorization_number. |
| **RF-AUT-14** | Si authorization.authorized_ips_name está lleno, al agendar cita validar o advertir si la IPS/lugar no coincide (comparar con location_name o catálogo IPS si existe). |
| **RF-AUT-15** | En frontend y backend: no permitir elegir fecha de cita > authorization.valid_until; mensaje "Requiere renovación ante la EPS". |
| **RF-AUT-16** | Dashboard: agregar estadísticas de autorizaciones (pendientes radicación, radicadas, aprobadas sin cita, próximas a vencer) y tarjetas/links. |
| **RF-AUT-17** | En Show de Afiliado: sección "Autorizaciones" con listado. Y/o listado en módulo Autorizaciones filtrable por afiliado. |
| **RF-AUT-18** | Index de autorizaciones: filtros por estado, EPS, afiliado, rango fechas, tipo servicio, número de autorización. |

---

## 5. Orden sugerido de implementación

### Fase 1 — Base del módulo
0. **Modelo y alcance (obligatorio antes o junto con Fase 1):** (a) Añadir a tabla `payers` campo **`is_serviconli`** (boolean, default false); en seed o migración de datos, marcar como `true` el registro de Serviconli. (b) En modelo `Affiliate`, scope **`scopeWhereServiconliAsPayer($query)`** que restrinja a afiliados con perfil SS cuyo `payer.is_serviconli = true`. (c) Asegurar que en todo el código de solicitudes, citas y autorizaciones se use **Affiliate** y **affiliate_id** (nunca patient).
1. **Migraciones:** crear `authorizations`, `authorization_state_histories`, `authorization_documents`; añadir `requires_authorization` y estado `PENDING_AUTHORIZATION` a solicitudes; añadir `authorization_id` a appointments.
2. **Enum:** `AuthorizationStatus` con casos y labels/colores.
3. **Modelos:** `Authorization`, `AuthorizationStateHistory`, `AuthorizationDocument` (relaciones, fillable, casts).
4. **Módulo:** carpeta `App\Modules\Authorizations`, rutas, registrar en `ModuleServiceProvider`.
5. **CRUD básico:** AuthorizationController (index, create, store, show, update para cambio de estado y datos de aprobación/negación); requests de validación; resource para Inertia.
6. **Documentos:** subida de archivos en show de autorización (store document), listado y descarga.

### Fase 2 — Integración con solicitudes
7. **Solicitud:** campo `requires_authorization` en Create/Edit de solicitud; nuevo estado `RequestStatus::PENDING_AUTHORIZATION`; en Show de solicitud botón "Crear autorización" y flujo que cree autorización y ponga solicitud en PENDING_AUTHORIZATION. **Validar:** al crear/editar solicitud, que el afiliado tenga pagador Serviconli (scope `whereServiconliAsPayer`); si no, rechazar con mensaje: solo se gestionan solicitudes para afiliados con Serviconli como pagador.
8. **Bloqueo:** en "Crear cita" desde solicitud, validar autorización aprobada y vigente; mensaje claro si no.
9. **Actualización automática:** al aprobar autorización, actualizar solicitud a IN_PROGRESS (o estado que permita agendar); al negar, opción de marcar solicitud como fallida.

### Fase 3 — Integración con citas
10. **Cita:** al crear cita desde solicitud con autorización aprobada, asignar `authorization_id` y copiar `authorization_number`; validar vigencia y, si aplica, IPS.
11. **Validación:** en store/update de cita, si viene de solicitud con autorización, comprobar vigencia y bloquear fecha fuera de vigencia.

### Fase 4 — Notificaciones, alertas y dashboard
12. **Notificaciones:** evento/listener al cambiar estado de autorización; integración con canal disponible (in-app, email, WhatsApp).
13. **Alertas vencimiento:** comando programado (5 y 2 días antes) y cambio automático a expired.
14. **Dashboard:** indicadores de autorizaciones y enlace al listado.
15. **Historial por afiliado:** sección en Show de afiliado y filtros en index de autorizaciones.

---

## 6. Archivos a crear / modificar (resumen)

**Nuevos (módulo Authorizations):**
- `app/Modules/Authorizations/Models/Authorization.php`
- `app/Modules/Authorizations/Models/AuthorizationStateHistory.php`
- `app/Modules/Authorizations/Models/AuthorizationDocument.php`
- `app/Modules/Authorizations/Enums/AuthorizationStatus.php`
- `app/Modules/Authorizations/Controllers/AuthorizationController.php`
- `app/Modules/Authorizations/Requests/StoreAuthorizationRequest.php`, `UpdateAuthorizationRequest.php`, `StoreAuthorizationDocumentRequest.php`
- `app/Modules/Authorizations/Resources/AuthorizationResource.php`
- `app/Modules/Authorizations/routes.php`
- Migraciones (en `database/migrations` o en módulo si se usa loadMigrationsFrom)
- Vue: `Pages/Authorizations/Index.vue`, Create.vue, Show.vue (y componente para subir documentos)

**Modificar:**
- `app/Modules/SocialSecurity/Models/Payer.php` — fillable y cast para `is_serviconli`
- `app/Modules/Patients/Models/Affiliate.php` — scope `scopeWhereServiconliAsPayer($query)` (whereHas socialSecurityProfile.payer donde is_serviconli = true)
- `app/Modules/AppointmentRequests/Enums/RequestStatus.php` — añadir PENDING_AUTHORIZATION
- `app/Modules/AppointmentRequests/Models/AppointmentRequest.php` — relación authorization(), fillable requires_authorization (y migración)
- `app/Modules/AppointmentRequests/Controllers/AppointmentRequestController.php` — al listar solicitudes, filtrar por afiliados con pagador Serviconli; al crear/validar, comprobar que el afiliado tenga pagador Serviconli; lógica "Crear cita" con validación de autorización; createAppointment con authorization_id
- `app/Modules/AppointmentRequests/Requests/CreateAppointmentRequestRequest.php` — requires_authorization; validación de que affiliate tiene pagador Serviconli
- `app/Modules/Appointments/Models/Appointment.php` — relación authorization(), fillable authorization_id
- `app/Modules/Appointments/Services/AppointmentService.php` — validaciones RF-AUT-12, RF-AUT-15; asignar authorization_id; en listados/búsqueda, restringir a afiliados con pagador Serviconli si aplica
- `app/Providers/ModuleServiceProvider.php` — registrar módulo Authorizations
- Rutas y vistas: AppointmentRequests/Show.vue (botón crear autorización, estado), Appointments/Create.vue (validación vigencia/IPS si aplica); en selects de afiliado (solicitudes, autorizaciones, citas) solo ofrecer afiliados con Serviconli como pagador
- Dashboard: stats de autorizaciones y enlaces

**Migraciones:**
- `add_is_serviconli_to_payers_table` (boolean; un solo registro true = pagador Serviconli)
- `create_authorizations_table`
- `create_authorization_state_histories_table`
- `create_authorization_documents_table`
- `add_requires_authorization_and_pending_authorization_to_appointment_requests`
- `add_authorization_id_to_appointments_table`

Con este plan se puede implementar por fases y probar cada integración. Si estás de acuerdo, el siguiente paso es **Fase 1** (migraciones, modelos, enum, CRUD básico y documentos).
