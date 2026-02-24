# Tareas concretas — Módulo de Seguridad Social

**Objetivo de este documento:** Definir de forma **muy clara y meticulosa** las tareas a realizar en cada ítem del módulo de Seguridad Social, para tener un sistema **confiable**, **fácil de operar** y que resuelva los **principales problemas de organización operativa** del proceso.

**Documentos de referencia:**  
- [PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md](PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md)  
- [NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md](NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md) — Normatividad colombiana, cálculos, PILA y **recomendaciones obligatorias** (parametrización, históricos, validaciones, logging, flexibilidad).

**Principios operativos que guían las tareas:**
- **Una sola fuente de verdad:** cada dato (EPS, pagador, IBC, día de pago) en un solo lugar; evitar duplicados o textos libres que impidan reportes.
- **Trazabilidad:** quién hizo qué y cuándo (novelties, auditoría, communication_logs).
- **Consistencia:** validaciones en backend; catálogos en lugar de texto libre; reglas de negocio (PILA, vencimientos) centralizadas.
- **Claridad para el operador:** pantallas que muestren lo que debe hacer hoy (vencimientos, pendientes, mora) y qué falta por completar.

**Normatividad colombiana (ver [NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md](NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md)):** parametrización (porcentajes y valores en BD con vigencia), históricos por fecha, validaciones robustas antes de PILA, logging/trazabilidad de cálculos, diseño flexible ante cambios normativos.

---

## Índice de fases

| Fase | Contenido |
|------|-----------|
| **Fase 1 (MVP)** | Tareas concretas 1.0 a 1.13 — cerrar el MVP de Seguridad Social |
| **Fase 2** | Automatizaciones y recordatorios |
| **Fase 3** | Reportería y cierre |
| **Post-MVP** | Historia clínica para citas y otros ítems |

---

## Fase 1 — MVP: tareas concretas

### 1.0 Catálogos y configuración

**Objetivo operativo:** Que ningún dato de SS dependa de texto libre. Todo debe venir de catálogos para reportes y consistencia.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.0.1 | Crear migración para tablas `afps`, `arps`, `ccfs`, `payment_operators`, `accounting_registries` (id, name, code, is_active, timestamps). | `php artisan migrate` crea las tablas sin error. |
| 1.0.2 | Crear modelos `Afp`, `Arp`, `Ccf`, `PaymentOperator`, `AccountingRegistry` con scope `active()`. | Modelos usables en formularios y seeders. |
| 1.0.3 | Ejecutar/crear seeders: AfpSeeder, ArpSeeder, CcfSeeder, PaymentOperatorSeeder, AccountingRegistrySeeder con valores oficiales PILA/colombianos. | Después de `db:seed`, los catálogos tienen datos y los selects del perfil SS los muestran. |
| 1.0.4 | Migración que añada a `social_security_profiles` las FKs (afp_id, arp_id, ccf_id, payment_operator_id, accounting_registry_id) si no existen; migración de datos: para cada perfil con afp_name/arp_name/ccf_name/payment_operator no nulo, buscar o crear registro en la tabla correspondiente y asignar el id; luego eliminar columnas de texto. | Perfiles existentes siguen mostrando la misma entidad por nombre, pero guardada por FK. |
| 1.0.5 | Módulo de configuración (Admin): ruta `/admin/configuracion` o integrado en Admin existente; listado por cada catálogo (AFP, ARP, CCF, Operadores, Registros contables); formulario crear/editar (nombre, código, activo); solo rol admin. | Un admin puede dar de alta una nueva AFP/ARP/CCF/operador sin tocar código. |
| 1.0.6 | En formularios de afiliado (Create/Edit) y en recursos API: usar siempre id de catálogo (afp_id, arp_id, etc.); nunca guardar nombre como texto en `social_security_profiles`. | No quedan columnas de texto para entidades; solo FKs. |

---

### 1.1 Migraciones complementarias y roles

**Objetivo operativo:** Estructura de datos lista para planillas, novedades, soportes y comunicaciones; roles claros.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.1.1 | Verificar o crear migraciones para: `novelties`, `operator_credentials`, `payrolls`, `support_documents`, `communication_logs`, `payroll_trackings`, `colombian_holidays`. Todas con FKs correctas (affiliate_id, payroll_id, etc.) e índices para consultas por fecha y estado. | Todas las tablas existen; integridad referencial correcta. |
| 1.1.2 | Definir en BD o en seeders los roles/permisos de Seguridad Social: por ejemplo `seguridad_social` (acceso al módulo), y si aplica subpermisos (solo lectura reportes, solo afiliaciones, etc.). Asignar rol en `users` o en tabla de roles. | Middleware puede restringir rutas por rol. |
| 1.1.3 | Middleware que proteja rutas de afiliados (perfil SS), pagadores, novedades, planillas y dashboard: solo usuarios autenticados con rol que incluya Seguridad Social. | Acceso no autorizado devuelve 403 o redirección. |

---

### 1.2 DueDateCalculator y festivos

**Objetivo operativo:** Fecha de vencimiento PILA correcta según normativa colombiana; sin dependencias de fechas incorrectas.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.2.1 | Mantener tabla `colombian_holidays` (fecha); seeder con festivos colombianos del año en curso y siguiente. | DueDateCalculator no cuenta festivos como día hábil. |
| 1.2.2 | DueDateCalculator: método que reciba (año período, mes período, documento o payment_day) y devuelva la fecha del N-ésimo día hábil del mes siguiente (N entre 2 y 16 según tabla PILA). | Para un documento dado, la fecha devuelta es la misma que en PILA. |
| 1.2.3 | Al crear o actualizar perfil SS: si no se ingresa payment_day, calcularlo con DueDateCalculator a partir del documento del afiliado y guardarlo en el perfil. | Perfil siempre tiene payment_day cuando hay documento. |
| 1.2.4 | En la ficha del afiliado (Show): mostrar "Próximo vencimiento PILA" usando DueDateCalculator (período actual o siguiente). | El operador ve hasta cuándo tiene que pagar. |

---

### 1.3 CRUD Pagadores (Payers)

**Objetivo operativo:** Un pagador = un NIT/documento único; varios afiliados pueden compartir el mismo pagador; evitar duplicados.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.3.1 | Modelo Payer: name, document_type, document_number (unique), address, phone, email, contact_person, is_active; relación hasMany socialSecurityProfiles / hasManyThrough affiliates. | Un pagador puede listar sus afiliados. |
| 1.3.2 | Validación en Store/Update: document_number único en tabla payers; mensaje claro si ya existe. | No se crean dos payers con el mismo NIT/documento. |
| 1.3.3 | Listado pagadores: búsqueda por nombre, documento, contacto; filtro por is_active; columnas: nombre, documento, contacto, cantidad de afiliados; enlace a ver/editar. | Operador encuentra rápido un pagador. |
| 1.3.4 | Vista Show de pagador: datos del pagador + listado de afiliados que tienen ese payer_id en su perfil SS (enlace a ficha de cada afiliado). | Se ve qué afiliados “pertenecen” a ese NIT. |
| 1.3.5 | En formulario de afiliado (Create/Edit): select de pagadores activos (nombre + documento); opción "Sin pagador". | Al guardar se asigna payer_id en social_security_profiles. |

---

### 1.4 Perfil SS en Afiliado

**Objetivo operativo:** Un afiliado tiene como máximo un perfil SS; todos los campos de SS viven en ese perfil; cambios importantes generan trazabilidad (novedad).

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.4.1 | Crear/actualizar perfil SS desde Create y Edit de afiliado: client_type, contributor_type, eps_id, afp_id, arp_id, arp_risk_class, ccf_id, payer_id, payment_operator_id, accounting_registry_id, ibc, payment_day, payment_periodicity, has_parafiscales, observations. Validaciones: IBC en rango 290.000–14.235.800; payment_day 2–16; FKs existentes. | No se guardan valores inválidos. |
| 1.4.2 | Si el afiliado es beneficiario y se elige cotizante: prellenar eps_id (y opcionalmente más campos) desde el perfil del cotizante; permitir edición. | Menos errores y menos pasos al dar de alta beneficiarios. |
| 1.4.3 | Al guardar cambios en perfil SS que afecten EPS, tipo de cotizante o datos sensibles: crear registro en `novelties` (tipo según cambio, effective_date, old_value, new_value, registered_by). | Queda trazabilidad de cambios. |
| 1.4.4 | Vista Show del afiliado: bloques claros (Afiliación, Entidades, Pago y vencimiento, Observaciones); mostrar "—" cuando no hay valor; próximo vencimiento PILA visible. | Operador ve de un vistazo el estado SS del afiliado. |

---

### 1.5 Novedades (Novelties)

**Objetivo operativo:** Historial de eventos por afiliado (ingreso, retiro, cambio EPS, etc.) para auditoría y soporte.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.5.1 | Catálogo de tipos de novedad (novelty_types): al menos ENTRY, WITHDRAWAL, EPS_CHANGE; seeder y uso en formulario. | Al registrar una novedad se elige tipo. |
| 1.5.2 | En Show del afiliado: sección "Novedades" con listado ordenado por fecha efectiva (más reciente primero); columnas: tipo, fecha efectiva, descripción, valor anterior/nuevo si aplica. | Operador ve el historial del afiliado. |
| 1.5.3 | Botón "Registrar novedad": formulario (modal o página) con tipo, fecha efectiva (obligatoria), descripción, valor anterior, valor nuevo; guardar en `novelties` con affiliate_id y registered_by. | Cada novedad queda registrada con responsable. |
| 1.5.4 | (Opcional) Al actualizar perfil SS desde Edit afiliado: crear novedad automática cuando cambie eps_id, contributor_type o datos que la normativa considere relevantes. | Trazabilidad sin paso manual extra. |

---

### 1.6 Planillas (Payrolls) y PayrollService

**Objetivo operativo:** Una planilla por afiliado por mes/año; fecha de vencimiento calculada; estados que permitan saber qué está pendiente de pago o en mora. **Normativa:** seguir [NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md](NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md): porcentajes parametrizados, no hardcodeados; trazabilidad de cálculos.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.6.0 | **Parametrización de aportes:** Tabla (ej. `contribution_parameters`) o configuración con: tipo (salud_employer, health_employee, pension_employer, pension_employee, arl por clase de riesgo, parafiscales), valor (porcentaje), vigencia_desde, vigencia_hasta. Seed con valores vigentes según normativa. PayrollService debe leer de aquí, no de constantes en código. | Cambiar un porcentaje no exige tocar código; se puede dar vigencia histórica. |
| 1.6.1 | Modelo Payroll: affiliate_id, year, month, due_date, status (PENDING, SETTLED, SENT_TO_CLIENT, PAID, OVERDUE), montos (health, pension, arl, ccf), sent_at, paid_at, notes; unique (affiliate_id, year, month). | No hay dos planillas para el mismo afiliado/mes/año. |
| 1.6.2 | PayrollService: método para crear o obtener planilla de un afiliado para (año, mes). Calcular due_date con DueDateCalculator usando payment_day del perfil (o documento). Calcular montos a partir de IBC y **parámetros vigentes para ese período** (salud, pensión, ARL por clase, CCF); si no hay IBC, montos null o 0. Opcional: guardar en planilla o en log qué parámetros se usaron (trazabilidad). | La planilla tiene fecha y montos coherentes; los porcentajes vienen de BD. |
| 1.6.3 | Regla de estado: si hoy > due_date y status no es PAID ni SENT_TO_CLIENT, considerar OVERDUE; actualizar vía comando o al listar. | Dashboard y listados muestran "en mora" correctamente. |
| 1.6.4 | CRUD o al menos: listado de planillas (filtro por afiliado, pagador, año, mes, estado); vista detalle de planilla (afiliado, período, vencimiento, montos, estado); acción "Crear planilla" para un afiliado/mes/año si no existe. | Operador puede generar y consultar planillas. |
| 1.6.5 | Transición de estados: PENDING → SETTLED (liquidada); SETTLED → SENT_TO_CLIENT (enviada al cliente); SENT_TO_CLIENT → PAID (pagada). Registrar paid_at al marcar PAID. | Flujo claro y auditable. |

---

### 1.7 Dashboard Seguridad Social

**Objetivo operativo:** Pantalla que resuelva "qué debo hacer hoy" y "cuál es el estado general".

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.7.1 | Tarjetas o bloques con conteos: afiliados activos con perfil SS; planillas pendientes de pago; planillas en mora (due_date &lt; hoy y no pagadas); planillas pagadas en el mes. | Métricas básicas visibles. |
| 1.7.2 | Lista "Vencimientos hoy": afiliados (o planillas) cuyo due_date es hoy. Enlace a ficha del afiliado o de la planilla. | Operador sabe a quién contactar hoy. |
| 1.7.3 | Lista "Próximos vencimientos" (por ejemplo próximos 7 días): mismo criterio, ordenado por fecha. | Anticipación operativa. |
| 1.7.4 | Enlace rápido a: listado de afiliados, listado de pagadores, listado de planillas. | Navegación rápida desde el dashboard. |

---

### 1.8 Credenciales de operadores (opcional en MVP)

**Objetivo operativo:** Guardar usuario/clave por afiliado y por proveedor (operador de pago, ARL, CCF, EPS, AFP) de forma cifrada para uso en trámites.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.8.1 | Modelo OperatorCredential: affiliate_id, provider_type (enum o string: PAYMENT_OPERATOR, ARL, CCF, EPS, AFP), encrypted_credentials (texto cifrado con Laravel Crypt, por ejemplo JSON con user/pass). Unique (affiliate_id, provider_type). | No se guardan credenciales en claro. |
| 1.8.2 | En Show o en una pestaña del afiliado: listado de credenciales por proveedor; botón "Agregar credencial" (proveedor, usuario, clave); al guardar, cifrar y guardar. No mostrar la clave; opción "editar" para reemplazar. | Operador puede asociar credenciales por afiliado de forma segura. |

---

### 1.9 Soportes documentales (opcional en MVP)

**Objetivo operativo:** Adjuntar archivos a un afiliado (y opcionalmente a una planilla) para respaldo.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.9.1 | Modelo SupportDocument: affiliate_id, payroll_id (nullable), title, file_path, original_name, mime_type, size. Subida a storage (disco local o S3); path guardado en BD. | Archivos no se pierden y están asociados al afiliado. |
| 1.9.2 | En Show del afiliado: sección "Soportes" con listado de documentos; botón subir (título + archivo); descarga con control de acceso (solo usuarios autorizados). | Operador puede adjuntar y consultar soportes. |

---

### 1.10 Roles y permisos

**Objetivo operativo:** Que solo los roles autorizados accedan a datos y acciones de Seguridad Social.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.10.1 | Definir rol(es) en RoleSeeder: por ejemplo `seguridad_social`, `atencion`, `supervisor`, `admin` con acceso a rutas de afiliados (con perfil SS), pagadores, novedades, planillas, dashboard SS. | Usuario sin rol no accede a /payers ni a secciones SS. |
| 1.10.2 | Middleware en rutas: `auth` + comprobación de rol (ej. `role:seguridad_social,admin`). Aplicar a todas las rutas del módulo SS y a affiliates (si aplica). | 403 o redirección si no tiene permiso. |
| 1.10.3 | En menú/layout: enlaces a "Pagadores", "Dashboard SS", "Planillas" (o equivalente) visibles solo para roles con permiso. | La UI no muestra opciones que el usuario no puede usar. |

---

### 1.11 Frontend Vue del módulo

**Objetivo operativo:** Interfaz coherente, sin duplicar lógica; formularios que reflejen las validaciones del backend.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.11.1 | Páginas existentes: Affiliates (Index, Create, Edit, Show) con perfil SS completo; Payers (Index, Create, Edit, Show). Asegurar que en Create/Edit afiliado estén todos los campos de perfil SS (incl. pagador, IBC, día de pago, periodicidad, parafiscales, observaciones). | Ningún campo de perfil SS queda fuera del formulario. |
| 1.11.2 | Página o sección Planillas: listado con filtros (afiliado, pagador, año, mes, estado); botón crear planilla; vista detalle con cambio de estado. | Flujo de planillas usable de punta a punta. |
| 1.11.3 | Página Dashboard SS: métricas y listas (vencimientos hoy, próximos); enlaces a afiliados y planillas. | Operador entra al dashboard y resuelve "qué hacer hoy". |
| 1.11.4 | Mensajes de error y éxito consistentes (Inertia flash o componente global); validación en front opcional pero sin sustituir la del backend. | El usuario entiende por qué falló un guardado. |

---

### 1.12 ImportService DataSegura

**Objetivo operativo:** Carga masiva desde Excel según mapeo oficial; sin duplicar payers; trazabilidad y manejo de errores.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 1.12.1 | Leer [mapeo_datasegura_a_base_de_datos.md](mapeo_datasegura_a_base_de_datos.md) y implementar ImportService: por cada fila Excel, en orden: (1) buscar o crear Payer por document_number; (2) buscar o crear Affiliate por document_number, actualizar datos (nombres, city, department, status, document_type con ppt/ptt); (3) crear o actualizar SocialSecurityProfile con FKs a catálogos y payer_id; (4) crear Novelty si aplica; (5) crear OperatorCredential cifrado si aplica. | Un Excel de prueba importa sin errores y los datos quedan en affiliates, payers, social_security_profiles, novelties. |
| 1.12.2 | Validaciones: fechas en rango válido; documentos únicos; status ACTIVO/INACTIVO; split de nombres según reglas del mapeo; 2.340 sin clasificar → INACTIVO + notes. | No se importan datos corruptos. |
| 1.12.3 | Reporte de importación: filas procesadas, creadas, actualizadas, omitidas, errores (con número de fila y mensaje). | El operador sabe qué pasó con cada bloque de datos. |

---

### 1.13 Cierre de Fase 1 (checklist operativo)

| # | Tarea | Aceptación |
|---|--------|------------|
| 1.13.1 | Prueba de punta a punta: crear afiliado cotizante con perfil SS completo (EPS, AFP, ARP, CCF, pagador, IBC, día de pago); crear beneficiario vinculado; registrar novedad; crear planilla para un mes; ver dashboard con vencimientos. | Todo el flujo se completa sin error. |
| 1.13.2 | Verificar que un pagador muestre la lista de afiliados que lo tienen asignado. | Operador puede ver "todos los de este NIT". |
| 1.13.3 | Verificar que el "Próximo vencimiento PILA" en la ficha del afiliado coincida con la lógica PILA (día hábil N del mes siguiente). | Confianza en la fecha mostrada. |

---

## Fase 2 — Automatizaciones (después del MVP)

**Objetivo operativo:** Reducir carga manual y no perder vencimientos; todo envío queda registrado.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 2.1 | Tabla `communication_logs`: affiliate_id (nullable), payroll_id (nullable), channel (whatsapp, email), type (reminder, confirmation, overdue), status (sent, failed), recipient, sent_at. Cada envío de recordatorio o notificación debe insertar un registro. | Hay historial de comunicaciones por afiliado/planilla. |
| 2.2 | Plantillas de mensaje (WhatsApp/email) para: recordatorio de pago (X días antes del vencimiento), planilla lista para pago, aviso de mora. Variables: nombre afiliado, fecha vencimiento, monto, etc. | Mensajes coherentes y personalizables. |
| 2.3 | Comando o job programado: listar planillas con due_date en los próximos N días y status no pagado; enviar recordatorio según plantilla; registrar en communication_logs. | Recordatorios se envían sin intervención diaria. |
| 2.4 | Batch de planillas: comando o pantalla "Generar planillas del mes" que, para todos los afiliados activos con perfil SS, cree la planilla del mes/año en curso si no existe. | Operador no tiene que crear planilla una por una. |

---

## Fase 3 — Reportería y cierre

**Objetivo operativo:** Tomar decisiones con datos; auditoría y cumplimiento.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| 3.1 | Dashboard avanzado: gráficos (planillas por estado, tendencia de pagos, afiliados por pagador); filtros por período y pagador. | Visión agregada del estado operativo. |
| 3.2 | Reportes exportables: por pagador (afiliados y estado de planillas); por período (planillas vencidas, pagadas); listado de afiliados con perfil SS (Excel/PDF). | Se puede compartir con cliente o auditoría. |
| 3.3 | PayrollTracking o equivalente: registro de cambios de estado en planillas (quién, cuándo, de qué estado a cuál). | Auditoría de quién marcó "pagada" y cuándo. |
| 3.4 | Documentación interna: cómo usar el módulo SS (roles, flujo de planillas, importación DataSegura); y documentación técnica (normativa PILA, DueDateCalculator). | Nuevos operadores o desarrolladores pueden seguir el proceso. |

---

## Post-MVP — Historia clínica para citas

**Objetivo operativo:** En el momento de gestionar una cita, poder consultar información de historia clínica del afiliado si es necesario.

| # | Tarea concreta | Criterio de aceptación |
|---|----------------|------------------------|
| H.1 | Definir alcance: ¿solo documentos adjuntos (PDFs/imágenes) por afiliado, o también datos estructurados (diagnósticos, medicamentos, resúmenes)? Definir modelo de datos (tabla(s)) y permisos. | Documento de diseño aprobado. |
| H.2 | Almacenamiento: si es por documentos, tabla `clinical_documents` o similar (affiliate_id, title, file_path, document_date, uploaded_by, created_at); subida y descarga con control de acceso (solo roles autorizados). | Archivos de historia clínica asociados al afiliado y accesibles de forma controlada. |
| H.3 | En la ficha del afiliado (Show): sección "Historia clínica" o "Soportes clínicos" con listado de documentos y opción de subir. En la vista de cita (Show de Appointment): enlace o bloque que permita abrir/consultar la historia clínica del afiliado de la cita. | Operador accede a la información clínica desde la cita sin cambiar de contexto. |
| H.4 | Permisos: solo roles que deban ver historia clínica (ej. atención, médico, admin); registro de quién accedió a qué documento si se requiere auditoría. | Cumplimiento de confidencialidad. |

---

## Resumen de prioridades operativas

Para que el sistema sea **confiable** y **fácil de manejar**:

1. **Datos únicos y consistentes:** catálogos para entidades; payers por NIT único; un perfil SS por afiliado.
2. **Fechas correctas:** DueDateCalculator y festivos siempre actualizados; vencimientos visibles en dashboard y en ficha del afiliado.
3. **Estados claros en planillas:** PENDING → SETTLED → SENT → PAID; mora automática cuando pase due_date sin pagar.
4. **Trazabilidad:** novedades en cambios de perfil SS; communication_logs en envíos; auditoría en cambios de estado de planillas.
5. **Una pantalla "qué hacer hoy":** dashboard con vencimientos hoy y próximos días.
6. **Roles y permisos:** solo quien debe ver o editar datos SS tiene acceso.

Este documento debe actualizarse cuando se complete cada tarea o cuando se añadan nuevas exigencias normativas o de negocio.
