# Plan de implementación — Módulo de Historia Clínica

**Objetivo:** Módulo ligero de historia clínica vinculado a **afiliados** que tienen o pueden tener citas médicas, con información bien estructurada, acceso restringido (solo persona de citas y administrador) y auditoría de todos los accesos, en línea con el contexto normativo colombiano.

**Referencias:**  
- [TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md) — Post-MVP Historia clínica (H.1–H.4)  
- [PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md](PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md) — Sección 10.1  
- Ley 1581 de 2012 (datos sensibles), Resolución 1995 de 1999 (historia clínica)

---

## 1. Principios obligatorios

### 1.1 Vinculación al afiliado

- La historia clínica se asocia siempre al **afiliado** (`affiliate_id`), nunca a un “paciente” distinto.
- Un afiliado tiene **como máximo una historia clínica activa** (una por IPS/organización). Estados: ACTIVA | INACTIVA | ARCHIVADA.
- Las citas y solicitudes de cita ya usan `affiliate_id`; el módulo de historia clínica reutiliza el mismo modelo `Affiliate`.

### 1.2 Acceso restringido

- **Solo** los roles **persona de citas** (atención/citas) y **administrador** pueden acceder al módulo de historia clínica.
- Rutas protegidas por middleware (ej. `role:atencion,admin`). Verificación de rol también en backend en cada acción.
- Ningún otro rol (seguridad_social, operador, etc.) debe ver o modificar datos de historia clínica.

### 1.3 Auditoría

- **Todo acceso** (listado, consulta, creación, actualización, descarga de documentos) debe quedar registrado en la tabla de auditoría.
- No se permite **DELETE** físico sobre datos clínicos; si algo se anula, es por cambio de estado o nota, nunca borrado. La auditoría debe reflejar quién accedió a qué y cuándo.

### 1.4 Información sensible

- Los datos de salud son sensibles (Ley 1581). Almacenamiento en disco privado para documentos; descarga solo vía backend con control de permiso y registro en auditoría.
- Opcional: cifrado de campos muy sensibles (ej. contenido de notas de evolución) según política interna.

---

## 2. Contexto normativo colombiano (resumen)

- **Ley 1581 de 2012:** datos de salud = datos sensibles; tratamiento con finalidad definida, mínima circulación, medidas de seguridad.
- **Resolución 1995 de 1999:** historia clínica con contenido mínimo, conservación (mín. 20 años donde aplique), custodia y derecho de acceso/corrección.
- **Buena práctica:** UUID en registros clínicos, número de historia secuencial por IPS, firma/hash para integridad, y registro de accesos (auditoría).

---

## 3. Diseño de datos (esquema completo)

### 3.1 Núcleo de la Historia Clínica

**Tabla `historia_clinica`**

| Campo           | Tipo        | Descripción                                      |
|-----------------|-------------|--------------------------------------------------|
| id              | bigint PK   |                                                  |
| uuid            | uuid unique | Obligatorio por normativa; uso en URLs/API       |
| numero_historia | string      | Secuencial único por IPS (ej. HC-2026-00001)     |
| affiliate_id    | FK affiliates | Un afiliado → una historia clínica (unique)    |
| fecha_apertura   | date        | Fecha de apertura de la historia                 |
| estado          | enum        | ACTIVA \| INACTIVA \| ARCHIVADA                  |
| created_by      | FK users    | Profesional/usuario que creó                     |
| firma_digital   | string nullable | Hash de integridad (opcional)                 |
| timestamps      |             | created_at, updated_at                           |

### 3.2 Encuentros / Atenciones

**Tabla `encuentros_clinicos`**

| Campo              | Tipo        | Descripción |
|--------------------|-------------|-------------|
| id                 | bigint PK   |             |
| uuid               | uuid unique |             |
| historia_clinica_id| FK historia_clinica | |
| tipo_atencion      | enum        | CONSULTA \| URGENCIA \| HOSPITALIZACION \| TELECONSULTA |
| fecha_atencion     | date        |             |
| profesional_id     | FK users nullable | Quien atendió |
| especialidad_id    | FK nullable| Si existe catálogo de especialidades |
| motivo_consulta    | text        |             |
| enfermedad_actual  | text nullable |           |
| estado_mental      | text nullable | Si aplica  |
| firma_digital      | string nullable | Hash     |
| timestamps         |             |             |

### 3.3 Antecedentes (inmutables una vez registrados)

**Tabla `antecedentes`**

| Campo              | Tipo        |
|--------------------|-------------|
| id                 | bigint PK   |
| historia_clinica_id| FK historia_clinica |
| tipo               | enum: PATOLOGICO \| QUIRURGICO \| FARMACOLOGICO \| ALERGICO \| FAMILIAR \| TOXICO \| GINECO_OBSTETRICO |
| descripcion        | text        |
| fecha_registro     | date        |
| profesional_id     | FK users nullable |
| timestamps         |             |

### 3.4 Examen Físico por Encuentro

**Tabla `examenes_fisicos`**

| Campo                    | Tipo        |
|--------------------------|-------------|
| id                       | bigint PK   |
| encuentro_id             | FK encuentros_clinicos |
| peso_kg                  | decimal nullable |
| talla_cm                 | decimal nullable |
| imc                      | decimal nullable (calculado) |
| presion_arterial_sistolica | unsigned nullable |
| presion_arterial_diastolica | unsigned nullable |
| frecuencia_cardiaca      | unsigned nullable |
| frecuencia_respiratoria  | unsigned nullable |
| temperatura              | decimal nullable |
| saturacion_oxigeno       | unsigned nullable |
| hallazgos_por_sistema    | JSON nullable (flexible por especialidad) |
| resumen_general           | text nullable |
| timestamps                |             |

### 3.5 Diagnósticos CIE-10 (obligatorio en Colombia)

**Tabla `diagnosticos`**

| Campo          | Tipo        |
|----------------|-------------|
| id             | bigint PK   |
| encuentro_id   | FK encuentros_clinicos |
| cie10_codigo   | string (o FK catalogo_cie10 si existe) |
| tipo           | enum: PRINCIPAL \| RELACIONADO \| COMPLICACION |
| condicion      | enum: NUEVO \| REPETICION \| CRONICO |
| descripcion_libre | text nullable |
| timestamps     |             |

### 3.6 Plan de Manejo

**Tabla `planes_manejo`**

| Campo          | Tipo        |
|----------------|-------------|
| id             | bigint PK   |
| encuentro_id   | FK encuentros_clinicos |
| conducta       | text        |
| incapacidad_dias | unsigned nullable |
| fecha_control  | date nullable |
| observaciones  | text nullable |
| timestamps     |             |

### 3.7 Órdenes Médicas

**Tabla `ordenes_medicas`**

| Campo                 | Tipo        |
|-----------------------|-------------|
| id                    | bigint PK   |
| encuentro_id          | FK encuentros_clinicos |
| tipo                  | enum: MEDICAMENTO \| LABORATORIO \| IMAGEN \| INTERCONSULTA \| PROCEDIMIENTO |
| descripcion           | text        |
| estado                | enum: GENERADA \| AUTORIZADA \| EJECUTADA \| CANCELADA |
| cups_codigo           | string nullable (o FK catalogo_cups) |
| requiere_autorizacion | boolean default false |
| timestamps            |             |

**Tabla `ordenes_medicamentos`** (cuando tipo = MEDICAMENTO)

| Campo                  | Tipo        |
|------------------------|-------------|
| id                     | bigint PK   |
| orden_id               | FK ordenes_medicas |
| principio_activo       | string      |
| concentracion          | string nullable |
| forma_farmaceutica     | string nullable |
| dosis                  | string nullable |
| frecuencia             | string nullable |
| duracion               | string nullable |
| via_administracion     | string nullable |
| indicaciones_especiales| text nullable |
| timestamps             |             |

### 3.8 Notas de Evolución

**Tabla `notas_evolucion`**

| Campo              | Tipo        |
|--------------------|-------------|
| id                 | bigint PK   |
| historia_clinica_id| FK historia_clinica |
| encuentro_id       | FK nullable (puede ser nota general) |
| tipo                | enum: EVOLUCION \| ENFERMERIA \| INTERCONSULTA \| EPICRISIS |
| contenido           | text (valorar cifrado) |
| fecha_nota          | date        |
| profesional_id      | FK users    |
| firma_hash          | string nullable (inmutable) |
| timestamps          |             |

### 3.9 Documentos clínicos (adjuntos)

**Tabla `documentos_clinicos`**

| Campo               | Tipo        |
|---------------------|-------------|
| id                  | bigint PK   |
| historia_clinica_id | FK historia_clinica |
| tipo                | enum: LABORATORIO \| IMAGEN \| CONSENTIMIENTO \| EXTERNO |
| nombre_archivo      | string      |
| ruta_almacenamiento | string      | En disco privado; valorar encriptación |
| hash_integridad     | string nullable |
| fecha_documento     | date nullable |
| uploaded_by         | FK users nullable |
| timestamps          |             |

### 3.10 Auditoría (crítico — exigido por ley)

**Tabla `auditoria_hc`**

| Campo          | Tipo        |
|----------------|-------------|
| id             | bigint PK   |
| tabla_afectada  | string      |
| registro_id     | string      | UUID o id del registro |
| accion         | enum        | CREATE \| READ \| UPDATE (DELETE no permitido en HC) |
| usuario_id     | FK users    |
| ip_origen      | string nullable |
| datos_anteriores | JSON nullable |
| datos_nuevos   | JSON nullable |
| created_at     | timestamp   |

**Regla:** Todo acceso (listado, ver detalle, crear, actualizar, descargar documento) debe generar al menos un registro en `auditoria_hc` con acción READ/CREATE/UPDATE según corresponda.

---

## 4. Implementación por fases (módulo ligero)

### Fase 1 — Núcleo mínimo (prioridad para “tener a mano” en citas)

- **Tablas:** `historia_clinica`, `encuentros_clinicos`, `documentos_clinicos`, `auditoria_hc`.
- **Modelos:** HistoriaClinica, EncuentroClinico, DocumentoClinico, AuditoriaHc.
- **Lógica:** Una historia clínica por afiliado (crear al abrir por primera vez, estado ACTIVA). Número de historia secuencial por IPS. Encuentros y documentos asociados a la historia.
- **UI:**  
  - En **ficha del afiliado (Show):** sección “Historia clínica” con enlace “Ver historia clínica” (solo roles citas/admin).  
  - **Vista historia clínica:** cabecera (afiliado, número, estado), listado de encuentros (fecha, tipo, motivo), listado de documentos y subida.  
  - En **vista de cita (Appointment Show):** enlace “Historia clínica del afiliado” hacia la misma historia.
- **Permisos:** Middleware `role:atencion,admin` en todas las rutas del módulo. Cada acción (index, show, store, update, download) registrada en `auditoria_hc`.
- **Archivos:** Almacenamiento privado; descarga vía controlador con verificación de rol y auditoría.

**Criterios de aceptación Fase 1:**  
- Rol citas y admin pueden abrir la historia clínica de un afiliado desde su ficha o desde la cita.  
- Se pueden registrar encuentros (fecha, tipo, motivo, enfermedad actual) y adjuntar documentos.  
- Todo acceso (lectura, creación, descarga) queda en `auditoria_hc`.

### Fase 2 — Antecedentes y examen físico

- Tablas: `antecedentes`, `examenes_fisicos`.
- En detalle del encuentro: secciones Antecedentes (por tipo) y Examen físico (signos vitales + hallazgos).
- Misma política de permisos y auditoría.

### Fase 3 — Diagnósticos CIE-10 y plan de manejo

- Tablas: `diagnosticos`, `planes_manejo`.
- Catálogo CIE-10 (reducido o código + descripción) si no existe.
- Pantallas en el encuentro: Diagnósticos y Plan de manejo.

### Fase 4 — Órdenes médicas y medicamentos

- Tablas: `ordenes_medicas`, `ordenes_medicamentos`.
- Catálogo CUPS opcional. Estados de orden: GENERADA → AUTORIZADA → EJECUTADA / CANCELADA.

### Fase 5 — Notas de evolución

- Tabla: `notas_evolucion`.
- Contenido sensible; valorar cifrado en BD. Registros inmutables (solo creación; no edición/borrado).

---

## 5. Archivos y estructura del módulo

**Módulo Laravel:** `App\Modules\HistoriaClinica`

- **Models:** HistoriaClinica, EncuentroClinico, Antecedente, ExamenFisico, Diagnostico, PlanManejo, OrdenMedica, OrdenMedicamento, NotaEvolucion, DocumentoClinico, AuditoriaHc.
- **Controllers:** HistoriaClinicaController (index/show por afiliado, store para crear HC si no existe), EncuentroClinicoController, DocumentoClinicoController (upload, download con auditoría). En fases posteriores: AntecedenteController, etc.
- **Services/Traits:** Un trait o servicio central que registre en `auditoria_hc` (tabla_afectada, registro_id, accion, user_id, ip, datos si aplica).
- **Requests:** Validación de store/update por recurso.
- **Resources:** Recursos API/Inertia para no exponer campos innecesarios.
- **Routes:** Rutas bajo prefijo (ej. `/historia-clinica` o `/affiliates/{affiliate}/historia-clinica`) con middleware `auth` y `role:atencion,admin`.
- **Migraciones:** En `database/migrations/` o en el módulo si se usa `loadMigrationsFrom`.

**Frontend (Vue/Inertia):**

- `Pages/HistoriaClinica/Show.vue` — Vista principal de la historia de un afiliado (encuentros, documentos).
- `Pages/HistoriaClinica/EncuentroCreate.vue`, `EncuentroShow.vue` (y en Fase 2–5 las vistas de antecedentes, examen físico, diagnósticos, etc.).
- Componente o sección en `Affiliates/Show.vue`: bloque “Historia clínica” con enlace.
- En `Appointments/Show.vue`: enlace “Historia clínica del afiliado”.

**Registro del módulo:** Incluir `HistoriaClinica` en la lista de módulos de `ModuleServiceProvider` y cargar rutas del módulo.

---

## 6. Resumen de prioridades

1. **Acceso:** Solo roles citas (atención) y administrador.  
2. **Auditoría:** Todo acceso (CREATE, READ, UPDATE) en `auditoria_hc`. Sin DELETE en datos clínicos.  
3. **Vinculación:** Siempre por `affiliate_id`; una historia clínica por afiliado.  
4. **Integración:** Enlace desde ficha de afiliado y desde vista de cita para consultar la historia.  
5. **Fases:** Empezar por Fase 1 (núcleo + encuentros + documentos + auditoría) para tener el módulo “ligero” operativo; luego ampliar con antecedentes, examen físico, diagnósticos CIE-10, planes, órdenes y notas de evolución.

Este documento debe actualizarse cuando se complete cada fase o se incorporen nuevos requisitos normativos o de negocio.
