# ARQUITECTURA — MÓDULO DE GESTIÓN DE CLIENTES PILA
## Reemplazo de DataSegura_SERVICONLI_2025.xlsx en Laravel 12
**Versión:** 1.0 | **Fecha:** Marzo 2026 | **Proyecto:** Serviconli

---

## TABLA DE CONTENIDOS

1. [Visión y Alcance del Módulo](#1-vision-y-alcance)
2. [Diagnóstico del Excel Actual](#2-diagnostico-excel)
3. [Modelo de Datos Completo](#3-modelo-de-datos)
4. [Arquitectura del Módulo Laravel](#4-arquitectura-laravel)
5. [Flujos de Usuario (UX)](#5-flujos-usuario)
6. [Reglas de Negocio](#6-reglas-de-negocio)
7. [Seguridad de Credenciales](#7-seguridad)
8. [Datos de Referencia](#8-datos-referencia)
9. [Integración con el Módulo Access](#9-integracion-access)
10. [Estrategia de Migración de Datos](#10-migracion-datos)
11. [Dashboard Operativo](#11-dashboard)
12. [Permisos y Roles](#12-permisos)
13. [Orden de Implementación](#13-orden-implementacion)

---

# 1. VISIÓN Y ALCANCE

## Objetivo principal

Reemplazar completamente el archivo `DataSegura_SERVICONLI_2025.xlsx` por un módulo web dentro de la aplicación Laravel 12 de Serviconli. Este módulo debe ser **la herramienta de trabajo diario** de los asesores operativos para gestionar los 891 clientes activos del servicio de planilla PILA.

## Qué hace hoy el Excel

El archivo Excel es actualmente la herramienta central de operación. Concentra en una sola hoja:

- Datos personales del afiliado (cotizante)
- Datos del empleador / pagador
- Credenciales de acceso a 5 portales de seguridad social (en texto plano)
- Parámetros operativos de la planilla (operador, día hábil, periodicidad)
- Seguimiento del estado de pago de cada cliente
- Novedades laborales (ingresos, retiros)
- Información de afiliaciones (EPS, AFP, ARL, CCF)
- Datos de facturación y comprobantes

## Qué ofrecerá la aplicación que el Excel NO puede dar

| Limitación actual (Excel) | Solución en Laravel |
|---|---|
| Contraseñas visibles para cualquier usuario | Cifrado AES-256-CBC, acceso solo a usuarios autorizados |
| Sin control de quién consultó qué credencial | Auditoría completa de accesos |
| Cualquier persona puede modificar cualquier dato | Control de roles por función |
| Un solo archivo sin historial de cambios | Registro de cambios (logs) por cada modificación |
| Sin alertas automáticas de vencimientos | Notificaciones automáticas de pagos próximos |
| Búsqueda manual y lenta | Filtros en tiempo real, búsqueda instantánea |
| No integrado con el sistema Access | Una sola base de datos unificada |
| Riesgo de corrupción del archivo | Base de datos MySQL con respaldos automáticos |
| Acceso solo en el equipo donde está el archivo | Acceso web desde cualquier dispositivo |

## Alcance del módulo

**Incluye:**
- Gestión completa de empleadores / pagadores
- Gestión de afiliados con todos sus datos
- Registro y gestión de credenciales de portales (cifradas)
- Panel de operación diaria PILA
- Cálculo automático de fechas límite de pago
- Seguimiento de estados de pago
- Registro de novedades laborales
- Datos de referencia: tipos de cotizante, entidades, clases de riesgo

**Se conecta con (pero no reemplaza en este módulo):**
- Módulo de Caja y Recibos (viene del Access)
- Módulo de Planilla PILA / Aportes (viene del Access)
- Módulo de Cuentas de Cobro (viene del Access)

---

# 2. DIAGNÓSTICO DEL EXCEL ACTUAL

## Dimensiones del archivo

| Dato | Valor |
|---|---|
| Registros totales | 891 filas |
| Afiliados activos | 664 (74.5%) |
| Afiliados inactivos | 227 (25.5%) |
| Columnas con datos | 50 de 69 |
| Hojas útiles | 5 de 6 |

## Las 5 hojas del Excel y su destino en Laravel

### Hoja 1: DATA ACTUALIZADA 2025 (principal)
- **50 columnas organizadas en 8 bloques temáticos**
- Contiene el núcleo operativo: afiliados, empleadores, credenciales, operación PILA
- Destino: se migra a 5 tablas MySQL + servicios en Laravel

### Hoja 2: Código y Tipo de Cotizante
- **17 tipos de cotizante** con código PILA y descripción
- Destino: tabla de catálogo `cotizante_types` + seeder en Laravel

### Hoja 3: LISTADO DE CODIGOS PILA DE ADMI
- **95 registros** de entidades: AFP (8), ARL (11+), CCF (44), EPS (32+), SENA, ICBF
- Cada entidad tiene código PILA oficial, nombre y tipo
- Destino: tabla de catálogo `social_entities` + seeder en Laravel

### Hoja 4: TABLA DE RIESGOS ARL
- **5 clases de riesgo** con nivel, descripción, clase romana y tarifa porcentual
- Destino: tabla de catálogo `risk_classes` + seeder en Laravel

### Hoja 5: FECHAS DE PAGO
- **Tabla del Decreto 1990 de 2016**: últimos 2 dígitos del NIT → día hábil de pago (2 al 16)
- No necesita tabla en BD — se implementa como servicio de cálculo (`DeadlineService`)

### Hoja 6: Hoja 1
- Vacía. Se ignora.

## Distribución de los 50 campos por bloque temático

```
Bloque A: Datos del Afiliado           — Cols  1 al 13  (13 campos)
Bloque B: Datos del Empleador/Pagador  — Cols 14 al 21  ( 8 campos)
Bloque C: Operación PILA               — Cols 22 al 28  ( 7 campos)
Bloque D: Datos laborales y ARL        — Cols 29 al 33  ( 5 campos)
Bloque E: Caja de Compensación (CCF)   — Cols 34 al 36  ( 3 campos)
Bloque F: EPS                          — Cols 37 al 39  ( 3 campos)
Bloque G: AFP                          — Cols 40 al 42  ( 3 campos)
Bloque H: Facturación y seguimiento    — Cols 43 al 50  ( 8 campos)
Total campos con datos: 50
```

## Estadísticas clave

| Categoría | Detalle |
|---|---|
| Tipo de cliente mayoritario | SERVICONLI: 477 (53.5%) |
| Segundo tipo | INDEPENDIENTE: 243 (27.3%) |
| Tercer tipo | DEPENDIENTE: 136 (15.3%) |
| Operador PILA dominante | Arus (Enlace Operativo): 702 (78.8%) |
| Día hábil más frecuente | Día 5: 488 clientes (54.8% de la carga) |
| Salario promedio | $1,566,317 COP |
| Tipo de cotizante más común | 01-Dependiente: 611 clientes |
| Comprobante mayoritario | Recibo de Caja: 478 / Factura Electrónica: 394 |
| Pagos al día | 552 clientes (61.9%) |

## Inventario de credenciales sensibles (texto plano en Excel)

| Portal | Con credencial activa | Requiere N/A | Sin datos |
|---|---|---|---|
| PILA (Simple / Arus) | **777** | 24 | — |
| ARL (Positiva, Sura, etc.) | **598** | 217 | — |
| EPS (Sanitas, Nueva EPS, etc.) | **521** | 287 | — |
| AFP (Colpensiones, Porvenir, etc.) | **114** | 453 | — |
| CCF (Comfenalco, Comfamiliar, etc.) | **111** | 710 | — |
| **Total pares usuario/clave** | **~2,121** | — | — |

> **Riesgo CRÍTICO:** Estas 2,121 contraseñas están actualmente en texto plano y visibles para cualquier persona que abra el archivo Excel.

---

# 3. MODELO DE DATOS COMPLETO

## 3.1 Diagrama relacional del módulo

```
┌─────────────────────────────────────────────────────────────────────┐
│                         MÓDULO GESTIÓN PILA                         │
└─────────────────────────────────────────────────────────────────────┘

          ┌─────────────────────┐
          │      employers      │ ← Bloque B Excel (cols 14-21)
          │   (Empleadores)     │   + Día hábil (col 22)
          └──────┬──────────────┘
                 │ 1:N (un empleador tiene muchos afiliados)
                 │
          ┌──────▼──────────────┐         ┌──────────────────────────┐
          │     affiliates      │ ← Bloque A Excel (cols 1-13)
          │    (Afiliados)      │         │   pila_credentials       │
          └──────┬──────────────┘         │  (Credenciales PILA)     │
                 │ 1:1 relación            │  ← Cols 27-28 Excel      │
                 │ de afiliación           │  Pertenece al empleador  │
          ┌──────▼──────────────┐         └──────────────────────────┘
          │    affiliations     │ ← Bloques C,D,E,F,G,H Excel
          │  (Afiliaciones)     │   Datos laborales + entidades
          └──────┬──────────────┘
                 │
         ┌───────┴────────┐
         │                │
┌────────▼──────┐  ┌───────▼──────────────┐
│portal_         │  │  affiliate_notes     │
│credentials    │  │  (Observaciones)     │
│(Credenciales  │  │  ← Cols 44,50 Excel  │
│ARL/EPS/AFP/   │  └─────────────────────┘
│CCF)           │
│← Cols 32-42   │
└───────────────┘

TABLAS DE CATÁLOGO (datos fijos, no cambian frecuentemente):
┌─────────────────┐ ┌──────────────────┐ ┌────────────────┐
│ cotizante_types │ │  social_entities │ │  risk_classes  │
│  (Hoja 2 Excel) │ │  (Hoja 3 Excel)  │ │ (Hoja 4 Excel) │
└─────────────────┘ └──────────────────┘ └────────────────┘
```

## 3.2 Descripción de cada tabla

---

### Tabla: `employers` (Empleadores / Pagadores)

**Origen:** Bloque B del Excel (cols 14-21) + campo día hábil (col 22)

**Propósito:** Almacena la información de los empleadores o pagadores de planilla. Un empleador puede tener uno o muchos afiliados. Para los afiliados de tipo INDEPENDIENTE, el empleador es la misma persona.

| Campo | Tipo | Longitud | Nulo | Descripción | Col Excel |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | No | Llave primaria autoincremental | — |
| `document_type` | ENUM | — | No | Tipo de documento: `NIT`, `CC`, `CE` | Col 15 |
| `document_number` | VARCHAR | 20 | No | Número sin dígito verificador (ej: 901776975) | Col 16 |
| `check_digit` | CHAR | 1 | Sí | Dígito verificador del NIT (ej: 4) | Col 16 (separado) |
| `name` | VARCHAR | 200 | No | Razón social o nombre del pagador | Col 14 |
| `address` | VARCHAR | 200 | Sí | Dirección del empleador | Col 17 |
| `city` | VARCHAR | 100 | Sí | Ciudad | Col 18 |
| `department` | VARCHAR | 100 | Sí | Departamento (ej: QUINDIO) | Col 19 |
| `phone` | VARCHAR | 20 | Sí | Teléfono o celular | Col 20 |
| `email` | VARCHAR | 150 | Sí | Correo electrónico | Col 21 |
| `payment_business_day` | TINYINT | — | No | Día hábil de pago (2–16). Default: 5 | Col 22 |
| `is_active` | BOOLEAN | — | No | Activo/inactivo. Default: true | — |
| `is_self_employed` | BOOLEAN | — | No | True si el empleador = el mismo afiliado | RN-E02 |
| `notes` | TEXT | — | Sí | Notas internas sobre el empleador | — |
| `created_at` | TIMESTAMP | — | No | Fecha de creación | — |
| `updated_at` | TIMESTAMP | — | No | Fecha de última modificación | — |

**Restricciones:**
- `document_number` debe ser único
- `payment_business_day` debe estar entre 2 y 16
- Si `document_type = NIT`, `check_digit` es obligatorio

**Índices:** `document_number` (unique), `city`, `department`, `payment_business_day`

---

### Tabla: `affiliates` (Afiliados / Cotizantes)

**Origen:** Bloque A del Excel (cols 1-13) + integración con tabla Access `002 - Datos Generales del Asociado`

**Propósito:** Datos personales del trabajador que cotiza al sistema de seguridad social. Es la entidad central del sistema.

| Campo | Tipo | Longitud | Nulo | Descripción | Col Excel |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | No | Llave primaria autoincremental | — |
| `employer_id` | BIGINT UNSIGNED | — | Sí | FK a `employers`. Null si independiente | — |
| `document_type` | ENUM | — | No | `CC`, `CE`, `PPT`, `PTT`, `TI` | Col 4 |
| `document_number` | VARCHAR | 20 | No | Número de documento | Col 5 |
| `full_name` | VARCHAR | 200 | No | Nombre completo en mayúsculas | Col 6 |
| `first_name` | VARCHAR | 100 | Sí | Nombre(s) separado para búsquedas | — |
| `last_name` | VARCHAR | 100 | Sí | Apellido(s) separado para búsquedas | — |
| `gender` | ENUM | — | No | `M`, `F` | Col 7 |
| `birth_date` | DATE | — | Sí | Fecha de nacimiento | Col 8 |
| `address` | VARCHAR | 200 | Sí | Dirección de residencia | Col 9 |
| `city` | VARCHAR | 100 | Sí | Ciudad de residencia | Col 10 |
| `department` | VARCHAR | 100 | Sí | Departamento | Col 11 |
| `phone` | VARCHAR | 20 | Sí | Teléfono o celular | Col 12 |
| `email` | VARCHAR | 150 | Sí | Correo electrónico | Col 13 |
| `client_type` | ENUM | — | No | `serviconli`, `dependiente`, `independiente`, `exterior` | Col 2 |
| `is_active` | BOOLEAN | — | No | Activo/inactivo. Default: true | Col 1 |
| `access_legacy_id` | INTEGER | — | Sí | ID original en la base de datos Access (para trazabilidad) | — |
| `created_at` | TIMESTAMP | — | No | Fecha de creación | — |
| `updated_at` | TIMESTAMP | — | No | Fecha de última modificación | — |

**Restricciones:**
- `document_type` + `document_number` deben ser únicos juntos
- `client_type = exterior` implica ARL = null y CCF = null

**Índices:** `(document_type, document_number)` (unique), `full_name`, `employer_id`, `client_type`, `is_active`

---

### Tabla: `affiliations` (Datos de Afiliación y Operación)

**Origen:** Bloques C, D, E, F, G, H del Excel (cols 3, 22-26, 29-50) + Access `004 - Datos de Afiliacion Asociado`

**Propósito:** Almacena todos los datos operativos y laborales del afiliado: tipo de cotizante, entidades a las que está afiliado, operador PILA, salario, facturación, estado de pagos. Es el registro que el asesor consulta y actualiza a diario.

| Campo | Tipo | Longitud | Nulo | Descripción | Col Excel |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | No | Llave primaria | — |
| `affiliate_id` | BIGINT UNSIGNED | — | No | FK a `affiliates` | — |
| `employer_id` | BIGINT UNSIGNED | — | Sí | FK a `employers` | — |
| `cotizante_type` | VARCHAR | 5 | No | Código tipo cotizante PILA: `01`, `03`, `57`, etc. | Col 3 |
| **OPERACIÓN PILA** | | | | | |
| `pila_operator` | ENUM | — | No | `arus`, `simple`, `asopagos`, `aportes_en_linea`, `soi`, `mi_planilla`, `na` | Col 26 |
| `last_novelty_type` | VARCHAR | 10 | Sí | Última novedad: `ING`, `RET`, `LMA`, `IGE`, etc. | Col 24 |
| `last_novelty_date` | DATE | — | Sí | Fecha de la última novedad registrada | Col 25 |
| **DATOS LABORALES** | | | | | |
| `salary` | DECIMAL | 12,2 | Sí | IBC (Ingreso Base de Cotización) en pesos COP | Col 29 |
| `pays_parafiscales` | BOOLEAN | — | No | Si paga SENA, ICBF y CCF. Default: false | Col 43 |
| `self_employed` | BOOLEAN | — | No | True si el afiliado es su propio pagador | RN-E02 |
| **ARL** | | | | | |
| `arl_entity_id` | BIGINT UNSIGNED | — | Sí | FK a `social_entities` (type='ARL') | Col 30 |
| `risk_class_id` | BIGINT UNSIGNED | — | Sí | FK a `risk_classes` | Col 31 |
| **CCF** | | | | | |
| `ccf_entity_id` | BIGINT UNSIGNED | — | Sí | FK a `social_entities` (type='CCF') | Col 34 |
| **EPS** | | | | | |
| `eps_entity_id` | BIGINT UNSIGNED | — | Sí | FK a `social_entities` (type='EPS') | Col 37 |
| **AFP** | | | | | |
| `afp_entity_id` | BIGINT UNSIGNED | — | Sí | FK a `social_entities` (type='AFP') | Col 40 |
| **FACTURACIÓN** | | | | | |
| `payment_periodicity` | ENUM | — | No | `vencido`, `actual` | Col 45 |
| `billing_type` | ENUM | — | No | `recibo_caja`, `factura_electronica` | Col 46 |
| `last_document_number` | VARCHAR | 30 | Sí | Último comprobante emitido (ej: FVS-1754) | Col 47 |
| `last_payment_period` | CHAR | 6 | Sí | Último mes pagado en formato AAAAMM | Col 48 |
| `payment_status` | ENUM | — | No | `current` (al día), `overdue` (mora), `anticipated` | Col 49 |
| **METADATOS** | | | | | |
| `is_current` | BOOLEAN | — | No | Si esta es la afiliación vigente. Default: true | — |
| `created_at` | TIMESTAMP | — | No | | — |
| `updated_at` | TIMESTAMP | — | No | | — |

**Notas de diseño:**
- Se almacena `arl_entity_id`, `ccf_entity_id`, etc. como FK a `social_entities` en lugar de los campos de texto del Excel (`arl_code`, `arl_name`). Esto garantiza integridad referencial.
- El campo `last_payment_period` reemplaza "MES DE PAGO" del Excel (que era texto libre) con formato numérico `AAAAMM` (ej: `202503` = Marzo 2025).
- La fecha límite de pago **NO se almacena** — se calcula dinámicamente con `DeadlineService`.

**Índices:** `affiliate_id` (unique para afiliación vigente), `employer_id`, `pila_operator`, `payment_status`, `last_payment_period`

---

### Tabla: `pila_credentials` (Credenciales del Operador PILA)

**Origen:** Bloque C del Excel (cols 27-28)

**Propósito:** Credenciales de acceso al portal del operador PILA (Arus / Simple / etc.). Estas credenciales pertenecen al **empleador**, no al afiliado individual. Un empleador puede tener varios afiliados usando las mismas credenciales.

| Campo | Tipo | Longitud | Nulo | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | No | Llave primaria |
| `employer_id` | BIGINT UNSIGNED | — | No | FK a `employers` |
| `operator` | ENUM | — | No | `arus`, `simple`, `asopagos`, `aportes_en_linea`, `soi`, `mi_planilla` |
| `username` | VARCHAR | 100 | No | Usuario en el portal del operador |
| `password_encrypted` | TEXT | — | No | Contraseña cifrada con `Crypt::encryptString()` |
| `is_active` | BOOLEAN | — | No | Si la credencial está activa. Default: true |
| `last_accessed_at` | TIMESTAMP | — | Sí | Última vez que se consultó la contraseña |
| `last_accessed_by` | VARCHAR | 100 | Sí | Usuario del sistema que hizo la última consulta |
| `password_updated_at` | TIMESTAMP | — | Sí | Última vez que se actualizó la contraseña |
| `created_at` | TIMESTAMP | — | No | |
| `updated_at` | TIMESTAMP | — | No | |

**Restricciones:**
- `(employer_id, operator)` debe ser único — un empleador solo tiene una credencial por operador
- La contraseña NUNCA se almacena en texto plano. Solo cifrada.

---

### Tabla: `portal_credentials` (Credenciales de Portales ARL / EPS / AFP / CCF)

**Origen:** Bloques D, E, F, G del Excel (cols 32-33, 35-36, 38-39, 41-42)

**Propósito:** Credenciales de acceso a los portales web de las entidades de seguridad social. Estas credenciales pueden pertenecer al empleador (ARL, CCF) o al afiliado (EPS, AFP según el caso).

| Campo | Tipo | Longitud | Nulo | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | No | Llave primaria |
| `employer_id` | BIGINT UNSIGNED | — | Sí | FK a `employers`. Para credenciales del empleador (ARL, CCF) |
| `affiliate_id` | BIGINT UNSIGNED | — | Sí | FK a `affiliates`. Para credenciales del afiliado |
| `entity_type` | ENUM | — | No | `ARL`, `EPS`, `AFP`, `CCF` |
| `entity_id` | BIGINT UNSIGNED | — | Sí | FK a `social_entities` |
| `username` | VARCHAR | 100 | Sí | Usuario en el portal. Null si no aplica. |
| `password_encrypted` | TEXT | — | Sí | Contraseña cifrada. Null si no aplica. |
| `is_active` | BOOLEAN | — | No | Default: true |
| `is_not_applicable` | BOOLEAN | — | No | True si el valor es "NO APLICA" o "N/A". Default: false |
| `last_accessed_at` | TIMESTAMP | — | Sí | |
| `last_accessed_by` | VARCHAR | 100 | Sí | |
| `password_updated_at` | TIMESTAMP | — | Sí | |
| `created_at` | TIMESTAMP | — | No | |
| `updated_at` | TIMESTAMP | — | No | |

**Restricciones:**
- Al menos uno de `employer_id` o `affiliate_id` debe tener valor
- `(employer_id, entity_type)` es unique cuando `employer_id` no es null
- Si `is_not_applicable = true`, `username` y `password_encrypted` deben ser null

---

### Tabla: `affiliate_notes` (Observaciones)

**Origen:** Bloque H del Excel (cols 44, 50) + Access `019 - Observaciones`

**Propósito:** Notas operativas del equipo sobre cada afiliado. Hay dos tipos: observaciones de afiliación y observaciones de pago.

| Campo | Tipo | Longitud | Nulo | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | No | Llave primaria |
| `affiliate_id` | BIGINT UNSIGNED | — | No | FK a `affiliates` |
| `type` | ENUM | — | No | `affiliation` (col 44), `payment` (col 50), `general` |
| `content` | TEXT | — | No | Texto de la observación |
| `is_pinned` | BOOLEAN | — | No | Si la nota es importante y debe mostrarse destacada. Default: false |
| `created_by` | BIGINT UNSIGNED | — | Sí | FK a `users` |
| `created_at` | TIMESTAMP | — | No | |
| `updated_at` | TIMESTAMP | — | No | |

---

### Tabla: `cotizante_types` (Tipos de Cotizante — Catálogo)

**Origen:** Hoja 2 del Excel

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT UNSIGNED | Llave primaria |
| `code` | VARCHAR(5) | Código oficial PILA (ej: `01`, `57`) |
| `name` | VARCHAR(200) | Descripción completa |
| `is_active` | BOOLEAN | Si está vigente en el sistema |

---

### Tabla: `social_entities` (Entidades AFP / ARL / CCF / EPS — Catálogo)

**Origen:** Hoja 3 del Excel

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT UNSIGNED | Llave primaria |
| `type` | ENUM | `AFP`, `ARL`, `CCF`, `EPS`, `SENA`, `ICBF` |
| `code` | VARCHAR(20) | Código PILA oficial (ej: `25-14`, `EPS037`) |
| `name` | VARCHAR(200) | Nombre de la entidad |
| `is_active` | BOOLEAN | Si la entidad está vigente |

---

### Tabla: `risk_classes` (Clases de Riesgo ARL — Catálogo)

**Origen:** Hoja 4 del Excel

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT UNSIGNED | Llave primaria |
| `level` | TINYINT | Nivel: 0 al 5 |
| `class_name` | ENUM | `I`, `II`, `III`, `IV`, `V` o null (nivel 0) |
| `description` | VARCHAR(100) | Ej: "Riesgo mínimo" |
| `rate` | DECIMAL(7,5) | Tarifa: 0.00522, 0.01044, etc. |

---

## 3.3 Resumen de tablas del módulo

| Tabla | Tipo | Filas iniciales | Origen |
|---|---|---|---|
| `employers` | Transaccional | ~400-500 únicos | Excel bloques B + Access |
| `affiliates` | Transaccional | 891 + Access | Excel bloque A + Access |
| `affiliations` | Transaccional | 891 + Access | Excel bloques C-H + Access |
| `pila_credentials` | Transaccional | ~777 | Excel cols 27-28 |
| `portal_credentials` | Transaccional | ~2,121 | Excel cols 32-42 |
| `affiliate_notes` | Transaccional | ~variable | Excel cols 44, 50 |
| `cotizante_types` | Catálogo | 17 | Excel hoja 2 |
| `social_entities` | Catálogo | ~95 | Excel hoja 3 |
| `risk_classes` | Catálogo | 6 | Excel hoja 4 |

---

# 4. ARQUITECTURA DEL MÓDULO LARAVEL

## 4.1 Estructura de directorios

```
app/
└── Modules/
    └── PilaManagement/                    ← Módulo principal de este documento
        ├── Controllers/
        │   ├── DashboardController.php    ← Panel operativo diario
        │   ├── EmployerController.php     ← CRUD empleadores
        │   ├── AffiliateController.php    ← CRUD afiliados (integra Excel + Access)
        │   ├── AffiliationController.php  ← Datos laborales y entidades
        │   ├── CredentialController.php   ← Ver/editar credenciales (protegido)
        │   └── ReportController.php       ← Exportaciones y reportes
        │
        ├── Models/
        │   ├── Employer.php
        │   ├── Affiliate.php
        │   ├── Affiliation.php
        │   ├── PilaCredential.php
        │   ├── PortalCredential.php
        │   └── AffiliateNote.php
        │
        ├── Services/
        │   ├── DeadlineService.php        ← Calcula fecha límite de pago (Decreto 1990)
        │   ├── CredentialService.php      ← Cifrado/descifrado + auditoría
        │   ├── AffiliateImportService.php ← Importación inicial desde Excel
        │   └── PaymentStatusService.php   ← Actualiza estados de pago
        │
        ├── Requests/                      ← Validación de formularios
        │   ├── StoreEmployerRequest.php
        │   ├── StoreAffiliateRequest.php
        │   ├── StoreAffiliationRequest.php
        │   └── UpdateCredentialRequest.php
        │
        ├── Policies/                      ← Control de acceso por rol
        │   ├── AffiliatePolicy.php
        │   └── CredentialPolicy.php
        │
        └── Resources/                     ← Transformación de datos para vistas/API
            ├── AffiliateResource.php
            ├── EmployerResource.php
            └── CredentialResource.php

database/
├── migrations/
│   ├── xxxx_create_employers_table.php
│   ├── xxxx_create_affiliates_table.php
│   ├── xxxx_create_affiliations_table.php
│   ├── xxxx_create_pila_credentials_table.php
│   ├── xxxx_create_portal_credentials_table.php
│   ├── xxxx_create_affiliate_notes_table.php
│   ├── xxxx_create_cotizante_types_table.php
│   ├── xxxx_create_social_entities_table.php
│   └── xxxx_create_risk_classes_table.php
│
└── seeders/
    ├── CotizanteTypeSeeder.php            ← 17 tipos de cotizante (hoja 2 Excel)
    ├── SocialEntitySeeder.php             ← AFP, ARL, CCF, EPS (hoja 3 Excel)
    └── RiskClassSeeder.php                ← 6 clases de riesgo (hoja 4 Excel)

resources/
└── views/
    └── pila-management/
        ├── dashboard/
        │   └── index.blade.php            ← Panel operativo diario
        ├── employers/
        │   ├── index.blade.php            ← Lista de empleadores
        │   ├── create.blade.php
        │   └── edit.blade.php
        ├── affiliates/
        │   ├── index.blade.php            ← Lista principal (reemplaza Excel)
        │   ├── create.blade.php
        │   ├── edit.blade.php
        │   └── show.blade.php             ← Ficha completa del afiliado
        └── credentials/
            └── show.blade.php             ← Vista protegida de credenciales
```

## 4.2 Servicios clave

### DeadlineService
**Responsabilidad:** Calcular la fecha límite de pago para un empleador en un período dado, según el Decreto 1990 de 2016.

**Entradas:** Número de documento del empleador + período (AAAAMM)
**Salida:** Fecha límite como objeto `Carbon` (fecha del día hábil en ese mes)
**Lógica:** Tabla de los últimos 2 dígitos del NIT → día hábil (2 al 16) → calcular la fecha real considerando fines de semana y festivos colombianos.

---

### CredentialService
**Responsabilidad:** Manejar todo el ciclo de vida de las credenciales de portales con máxima seguridad.

**Operaciones:**
- `encrypt(string $plain): string` → cifra con `Crypt::encryptString()`
- `decrypt(CredentialModel $cred): string` → descifra Y registra quién, cuándo, desde dónde
- `auditAccess(CredentialModel $cred, User $user): void` → log de auditoría
- `bulkEncrypt(array $plainCredentials): void` → importación inicial desde Excel

**Regla crítica:** El método `decrypt()` SIEMPRE debe registrar en el log de auditoría antes de devolver la contraseña. No hay excepciones.

---

### AffiliateImportService
**Responsabilidad:** Proceso único de importación inicial de los datos del Excel a MySQL.

**Etapas:**
1. Leer el archivo Excel fila por fila
2. Crear o reutilizar empleador (deduplicar por `document_number`)
3. Crear afiliado
4. Crear afiliación con todos los datos operativos
5. Cifrar y guardar credenciales PILA
6. Cifrar y guardar credenciales de portales (ARL, EPS, AFP, CCF)
7. Guardar observaciones como notas
8. Log de errores por fila

---

### PaymentStatusService
**Responsabilidad:** Actualizar masivamente los estados de pago, equivalente a lo que hacían las consultas del Access que corrían automáticamente al cambiar el mes (`CompruebaOrigen`).

**Operaciones:**
- `runMonthlyTransitions(string $period): void` → actualiza `payment_status` de todos los afiliados según el nuevo mes
- `markAsPaid(Affiliate $affiliate, string $period): void` → marcar un cliente como pagado
- `getClientsDueToday(): Collection` → afiliados cuya fecha límite es hoy

---

# 5. FLUJOS DE USUARIO (UX)

## 5.1 Panel Principal de Operación Diaria

Este es el reemplazo directo del Excel. El asesor llega cada mañana y ve:

```
┌─────────────────────────────────────────────────────────────────────┐
│  GESTIÓN CLIENTES PILA — Marzo 2026                        [Buscar] │
├──────────────┬──────────────┬──────────────┬─────────────────────────┤
│ 664 Activos  │ 48 En mora   │ 23 Vencen hoy│ 488 Día hábil 5 (Arus) │
├──────────────┴──────────────┴──────────────┴─────────────────────────┤
│ FILTROS:  Estado ▼  Tipo ▼  Operador ▼  Día hábil ▼  EPS ▼  AFP ▼   │
├─────┬──────────────────┬────────────┬──────┬──────┬──────────────────┤
│ #   │ AFILIADO         │ EMPLEADOR  │ OP.  │ DÍA  │ ESTADO PAGO      │
├─────┼──────────────────┼────────────┼──────┼──────┼──────────────────┤
│ ●   │ JOHN HERRERA     │ GRANJA TAR │ ARUS │ 5    │ ✓ Al día         │
│ ●   │ MARIA GONZALEZ   │ AUTOPAGOS  │ SIM  │ 12   │ ⚠ En mora        │
│ ●   │ PEDRO LOPEZ      │ EMP XYZ    │ ARUS │ 5    │ ✓ Al día         │
└─────┴──────────────────┴────────────┴──────┴──────┴──────────────────┘
```

**Filtros disponibles:**
- Estado del afiliado: ACTIVO / INACTIVO
- Tipo de cliente: SERVICONLI / DEPENDIENTE / INDEPENDIENTE / EXTERIOR
- Operador PILA: Arus / Simple / Asopagos / etc.
- Día hábil de vencimiento: 2 al 16
- EPS asignada
- AFP asignada
- ARL asignada
- CCF asignada
- Estado de pago: Al día / En mora / Anticipado
- Mes de pago

---

## 5.2 Ficha del Afiliado (vista detallada)

Al hacer clic en un afiliado, se abre su ficha completa con pestañas:

### Pestaña 1: Datos Personales
Campos: Nombre, documento, género, fecha nacimiento, dirección, ciudad, departamento, teléfono, correo, tipo de cliente.

### Pestaña 2: Datos del Empleador
Campos: Nombre/Razón social, NIT/CC, dirección, ciudad, teléfono, correo, día hábil de pago, fecha límite calculada automáticamente.

### Pestaña 3: Afiliación y Entidades
Campos: Tipo de cotizante, operador PILA, EPS, AFP, ARL (con clase de riesgo), CCF, salario IBC, parafiscales S/N.

### Pestaña 4: Estado de Pago
Campos: Periodicidad, tipo de comprobante, último documento, mes de pago, estado actual, historial de pagos del año.

### Pestaña 5: Credenciales [ACCESO RESTRINGIDO]
Muestra las credenciales solo si el usuario tiene permiso. Al hacer clic en "Ver contraseña" se registra la auditoría.
- Credencial PILA (del empleador)
- Portal ARL
- Portal CCF
- Portal EPS
- Portal AFP

### Pestaña 6: Novedades
Historial de novedades PILA (ingresos, retiros, incapacidades, etc.)

### Pestaña 7: Observaciones
Notas del equipo sobre el afiliado (afiliación y pagos).

---

## 5.3 Flujo: Registrar nuevo afiliado

```
1. El asesor hace clic en "Nuevo Afiliado"
2. PASO 1 — Tipo de afiliado:
   ¿Es independiente (se paga solo)?
   → SÍ: los datos del empleador se copian automáticamente del afiliado
   → NO: buscar empleador existente o registrar uno nuevo

3. PASO 2 — Datos del afiliado (bloque A del Excel)

4. PASO 3 — Datos del empleador (bloque B del Excel)
   [Omitido si ya existe o si es independiente]

5. PASO 4 — Datos de afiliación:
   Tipo de cotizante, EPS, AFP, ARL, CCF, salario, operador PILA

6. PASO 5 — Credenciales (opcional al crear, se pueden agregar después):
   PILA + portales

7. PASO 6 — Datos operativos:
   Periodicidad, tipo comprobante, estado de pago inicial

8. CONFIRMACIÓN y guardado
```

---

## 5.4 Flujo: Consultar/Actualizar una credencial

```
1. El asesor abre la ficha del afiliado → pestaña Credenciales
2. Ve la lista de credenciales con sus tipos (PILA, ARL, EPS, AFP, CCF)
3. Los usuarios son visibles. Las contraseñas se muestran como ●●●●●●●●
4. Clic en "Ver contraseña" del portal ARL:
   → El sistema verifica si el usuario tiene permiso (rol)
   → Si tiene permiso: muestra la contraseña en texto, registra auditoría
   → Si no tiene permiso: muestra mensaje "Sin autorización"
5. Para editar: el asesor ingresa la nueva contraseña, el sistema la cifra automáticamente
6. Se guarda registro de quién cambió la contraseña y cuándo
```

---

# 6. REGLAS DE NEGOCIO

Las siguientes reglas son no negociables y deben validarse tanto en el frontend (UX) como en el backend (Request validation + Service layer).

## RN-01: Tipo de cliente SERVICONLI
Los afiliados de tipo SERVICONLI son aquellos en los que Serviconli figura como aportante ante el sistema PILA. El sistema debe identificarlos claramente y tratarlos de forma diferenciada en reportes y operación.

## RN-02: Independiente = Empleador es el mismo afiliado
Cuando el tipo de cliente es INDEPENDIENTE, los datos del empleador son los mismos que los del afiliado. El campo `is_self_employed = true` en `affiliations` y el sistema copia automáticamente los datos del afiliado al registrar el empleador.

## RN-03: Credenciales PILA pertenecen al empleador, no al afiliado
Un empleador puede tener múltiples afiliados y todos usan las mismas credenciales PILA. Las credenciales están en la tabla `pila_credentials` vinculadas al `employer_id`, no al `affiliate_id`. Al mostrar credenciales de un afiliado, el sistema busca las credenciales del empleador correspondiente.

## RN-04: Fecha límite se calcula, no se almacena
La fecha límite de pago (col 23 del Excel) NO se almacena en la base de datos. Se calcula dinámicamente en tiempo real usando `DeadlineService` con la tabla del Decreto 1990 de 2016. Esto garantiza que siempre sea correcta para cualquier mes futuro.

## RN-05: Colombianos en el exterior tienen entidades nulas
Los afiliados de tipo `exterior` no cotizan a ARL ni a CCF. El sistema debe:
- Bloquear la selección de ARL y CCF para este tipo
- Establecer `is_not_applicable = true` en `portal_credentials` para ARL y CCF

## RN-06: Las contraseñas NUNCA se almacenan en texto plano
Toda contraseña que ingrese al sistema, ya sea por migración del Excel, por creación manual o por actualización, debe cifrarse inmediatamente con `Crypt::encryptString()` antes de llegar a la base de datos.

## RN-07: Toda consulta de contraseña genera un registro de auditoría
Cada vez que un usuario autorizado descifra y consulta una contraseña, el sistema registra: quién, cuándo, desde qué IP, qué credencial. Esto es inauditable y no se puede desactivar.

## RN-08: Las observaciones son notas operativas críticas
Las observaciones de las cols 44 y 50 del Excel contienen instrucciones operativas importantes (ej: "SIEMPRE MODIFICAR EL SALARIO DE LA CAJA DE COMPENSACION DEBE SER $1 PESOS"). Estas deben migrarse como `affiliate_notes` con `is_pinned = true` si contienen palabras como "IMPORTANTE", "SIEMPRE", "OBLIGATORIO", etc.

## RN-09: El campo `last_payment_period` en formato AAAAMM
El mes de pago se almacena como `AAAAMM` (ej: `202503`) para permitir comparaciones y ordenamiento. El Excel tenía texto libre como "SEPTIEMBRE", "ENERO" que debe convertirse durante la migración.

## RN-10: Credencial "No Aplica" es diferente a "Sin credencial"
- `is_not_applicable = true`: el afiliado/empleador confirmó que no aplica (ej: no está en CCF)
- `username = null` y `is_not_applicable = false`: la credencial no se ha registrado aún (pendiente)

Estos dos estados son distintos y deben mostrarse diferente en la interfaz.

## RN-11: Día hábil 5 concentra la mitad de los clientes
El 54.8% de los clientes tienen día hábil 5 (488 clientes). El sistema debe alertar de alto volumen de operaciones en ese día y permitir filtrar/priorizar la operación por día hábil.

## RN-12: Transiciones de estado de pago al cambio de mes
Al inicio de cada mes, el sistema debe ejecutar automáticamente las mismas transiciones que hacía el Access en `CompruebaOrigen`:
- Afiliados con pagos atrasados pasan progresivamente a mora (60, 90, 120+ días)
- Esta lógica se implementa como un **Scheduled Job (artisan command)** que corre el día 1 de cada mes

---

# 7. SEGURIDAD DE CREDENCIALES

## 7.1 Arquitectura de cifrado

```
FLUJO DE ESCRITURA (guardar credencial):
Usuario escribe "MiClave123"
    → Request llega al Controller
    → CredentialService::encrypt("MiClave123")
    → Crypt::encryptString("MiClave123")  [usa APP_KEY de .env, AES-256-CBC]
    → Resultado: "eyJpdiI6Ii4uLiIsInZhbHVlIjoiLi4uIn0="
    → Se guarda SOLO el texto cifrado en la columna `password_encrypted`

FLUJO DE LECTURA (ver credencial):
Usuario hace clic en "Ver contraseña"
    → Policy verifica si el usuario tiene permiso
    → CredentialService::decrypt($credential)
        → Registra auditoría (user, IP, timestamp)
        → Crypt::decryptString($credential->password_encrypted)
        → Devuelve texto plano SOLO en memoria, por 1 request
    → El texto plano NUNCA se almacena, NUNCA se loguea
```

## 7.2 Niveles de acceso a credenciales

| Rol | Puede ver usuarios | Puede ver contraseñas | Puede editar credenciales |
|---|---|---|---|
| `admin` | Sí | Sí | Sí |
| `supervisor` | Sí | Sí | Sí |
| `agent` | Sí | Solo sus clientes asignados | No (solo reportar) |
| `viewer` | Sí | No | No |

## 7.3 Auditoría de credenciales

Cada evento de consulta de contraseña genera un registro con:
- `user_id`: quién consultó
- `credential_id`: qué credencial
- `credential_type`: PILA / ARL / EPS / AFP / CCF
- `action`: `viewed` / `updated` / `created`
- `ip_address`: desde dónde
- `user_agent`: con qué dispositivo/navegador
- `accessed_at`: timestamp exacto

---

# 8. DATOS DE REFERENCIA

## 8.1 Tipos de Cotizante (17 registros — Hoja 2 Excel)

Datos completos para el seeder:

| Código | Descripción |
|---|---|
| 01 | Dependiente |
| 02 | Servicio Doméstico |
| 03 | Independiente |
| 04 | Madre sustituta |
| 12 | Aprendices en etapa lectiva |
| 16 | Afiliación colectiva al sistema de seguridad integral |
| 18 | Funcionarios públicos sin tope máximo de IBC |
| 19 | Aprendices en etapa productiva |
| 20 | Estudiantes |
| 21 | Estudiantes de postgrado en salud |
| 22 | Profesor de establecimiento particular |
| 23 | Estudiantes aporte solo riesgos laborales |
| 30 | Dependiente entidades o universidades públicas |
| 31 | Cooperados o precooperativas de trabajo asociado |
| 40 | Beneficiario UPC adicional |
| 51 | Trabajador de tiempo parcial afiliado al régimen subsidiado |
| 56 | Prepensionado con aporte voluntario en salud |
| 57 | Independiente voluntario al Sistema de Riesgos Laborales |
| 59 | Independiente con contrato de prestación de servicios superior a 1 mes |

## 8.2 Entidades AFP (8 registros — Hoja 3 Excel)

| Código PILA | Nombre |
|---|---|
| 25-2 | AFP CAXDAC |
| 231001 | AFP COLFONDOS |
| 25-14 | AFP COLPENSIONES |
| 25-3 | AFP FONPRECON |
| 230901 | AFP OLD MUTUAL |
| 230904 | AFP OLD MUTUAL ALTERNATIVO |
| 230301 | AFP PORVENIR |
| 230201 | AFP PROTECCION |

## 8.3 Entidades ARL (registros en Hoja 3 Excel — muestra)

| Código PILA | Nombre |
|---|---|
| 14-11 | SURA |
| 14-23 | POSITIVA |
| 14-25 | COLMENA |
| 14-29 | LA EQUIDAD SEGUROS |
| 14-4 | COLPATRIA |
| 14-18 | LIBERTY SEGUROS |
| 14-30 | MAPFRE |
| 14-17 | SEGUROS ALFA |
| 14-8 | SEGUROS AURORA |
| 14-7 | SEGUROS BOLIVAR |
| 00-0 | NO APLICA |

> Lista completa de CCF (44 cajas) y EPS (32+) disponible en la hoja 3 del Excel y en `KNOWLEDGE_BASE_EXCEL.md` sección 7.2

## 8.4 Clases de Riesgo ARL (6 registros — Hoja 4 Excel)

| Nivel | Clase | Descripción | Tarifa |
|---|---|---|---|
| 0 | — | No Aplica | 0.000% |
| 1 | I | Riesgo mínimo | 0.522% |
| 2 | II | Riesgo bajo | 1.044% |
| 3 | III | Riesgo medio | 2.436% |
| 4 | IV | Riesgo alto | 4.350% |
| 5 | V | Riesgo máximo | 6.960% |

## 8.5 Tabla Fechas de Pago — Decreto 1990 de 2016

Últimos 2 dígitos del NIT → Día hábil de pago:

| Día hábil | Últimos 2 dígitos del NIT |
|---|---|
| 2 | 00, 01, 02, 03, 04, 05, 06, 07 |
| 3 | 08, 09, 10, 11, 12, 13, 14 |
| 4 | 15, 16, 17, 18, 19, 20, 21 |
| 5 | 22, 23, 24, 25, 26, 27, 28 |
| 6 | 29, 30, 31, 32, 33, 34, 35 |
| 7 | 36, 37, 38, 39, 40, 41, 42 |
| 8 | 43, 44, 45, 46, 47, 48, 49 |
| 9 | 50, 51, 52, 53, 54, 55, 56 |
| 10 | 57, 58, 59, 60, 61, 62, 63 |
| 11 | 64, 65, 66, 67, 68, 69 |
| 12 | 70, 71, 72, 73, 74, 75 |
| 13 | 76, 77, 78, 79, 80, 81 |
| 14 | 82, 83, 84, 85, 86, 87 |
| 15 | 88, 89, 90, 91, 92, 93 |
| 16 | 94, 95, 96, 97, 98, 99 |

> Implementado en `DeadlineService`. No necesita tabla en BD.

---

# 9. INTEGRACIÓN CON EL MÓDULO ACCESS

## El problema a resolver

El Access y el Excel operan de forma completamente independiente, generando duplicación e inconsistencias en los datos de los 891 clientes activos. La nueva aplicación debe ser **la fuente única de verdad**.

## Relación entre entidades Access y entidades Excel

| Entidad en Access | Entidad en Excel | Entidad unificada en Laravel |
|---|---|---|
| `001 - Aportante` | Bloque B cols 14-21 | `employers` |
| `002 - Asociado` | Bloque A cols 1-13 | `affiliates` |
| `004 - Afiliación` | Bloques C-H cols 22-50 | `affiliations` |
| *(no existe)* | Cols 27-28 | `pila_credentials` |
| *(no existe)* | Cols 32-42 | `portal_credentials` |
| `019 - Observaciones` | Cols 44, 50 | `affiliate_notes` |

## Datos que vienen SOLO del Access (no están en el Excel)

- Historial completo de aportes PILA (`pila_contributions`)
- Recibos de caja (`receipts` + `receipt_details`)
- Cuentas de cobro (`billing_accounts`)
- Historial de incapacidades (`disabilities`)
- Movimientos de caja (`cash_movements`)
- Beneficiarios del afiliado (`beneficiaries`)

## Datos que vienen SOLO del Excel (no están en el Access)

- Credenciales de portales PILA, ARL, EPS, AFP, CCF
- Tipo de cliente (SERVICONLI / DEPENDIENTE / INDEPENDIENTE / EXTERIOR)
- Estado de pago (al día / mora / anticipado)
- Periodicidad de pago (vencido / actual)
- Tipo de comprobante (recibo / factura)
- Último documento y mes de pago

## Datos duplicados que necesitan conciliación

- Datos del afiliado (nombre, documento, dirección)
- Datos del empleador
- Tipo de cotizante
- EPS, AFP, ARL, CCF asignados
- Salario

> **Estrategia:** Durante la migración, el Access es la fuente primaria para datos históricos y el Excel es la fuente primaria para datos operativos actuales. Los conflictos se resuelven manualmente antes de la importación.

---

# 10. ESTRATEGIA DE MIGRACIÓN DE DATOS

## 10.1 Principio general

La migración es un proceso **único e irrepetible** que transforma los datos del Excel y el Access a MySQL. Después de la migración, el Excel se congela (solo lectura) y la aplicación Laravel es la herramienta operativa.

## 10.2 Orden obligatorio de migración

El orden importa por las llaves foráneas:

```
FASE 1 — Datos de catálogo (seeders, sin dependencias):
  1. risk_classes          (6 registros, Hoja 4 Excel)
  2. social_entities       (95 registros, Hoja 3 Excel)
  3. cotizante_types       (17 registros, Hoja 2 Excel)

FASE 2 — Entidades base del Excel:
  4. employers             (deduplicados del bloque B)
  5. affiliates            (bloque A, con FK a employers)

FASE 3 — Datos operativos:
  6. affiliations          (bloques C-H, con FKs a affiliates, employers, social_entities)
  7. pila_credentials      (cols 27-28, con FK a employers, cifradas)
  8. portal_credentials    (cols 32-42, con FKs, cifradas)
  9. affiliate_notes       (cols 44, 50)

FASE 4 — Datos históricos del Access:
  10. pila_contributions   (historial de aportes)
  11. receipts             (recibos)
  12. cash_movements       (movimientos de caja)
  ... (resto de tablas del Access)
```

## 10.3 Transformaciones requeridas al importar el Excel

| Campo original (Excel) | Transformación |
|---|---|
| ESTADO: "ACTIVO" / "INACTIVO" | `is_active`: true / false |
| TIPO DE CLIENTE: "SERVICONLI" | `client_type`: "serviconli" (minúsculas) |
| Tipo cotizante: "01 -Dependiente." | `cotizante_type`: extraer solo "01" |
| # DOC: espacios y caracteres | `document_number`: limpiar con trim y solo dígitos |
| Fecha: "20/07/1979" | `birth_date`: convertir DD/MM/YYYY → YYYY-MM-DD |
| Salario: "$1.423.500" | `salary`: quitar "$", ".", "," → decimal puro |
| NIT: "901776975-4" | `document_number`: "901776975", `check_digit`: "4" |
| NombreARL: "14-23 -POSITIVA" | Separar → buscar `social_entities` por código "14-23" |
| USUARIO PILA | `username`: tal cual, sin transformación |
| CLAVE PILA | `password_encrypted`: cifrar con `Crypt::encryptString()` |
| MES DE PAGO: "SEPTIEMBRE" | `last_payment_period`: "202509" (AAAAMM) |
| PARAFISCALES: "SI" / "NO" | `pays_parafiscales`: true / false |
| REG/CONTABLE: "FACTURA..." | `billing_type`: "factura_electronica" |
| PAGOS AL DÍA: "SI" | `payment_status`: "current" |
| PAGOS AL DÍA: "NO" | `payment_status`: "overdue" |
| PAGOS AL DÍA: "ANTICIPADO" | `payment_status`: "anticipated" |
| N/A o "NO APLICA" en credencial | `is_not_applicable`: true, `username`: null |

## 10.4 Deduplicación de empleadores

El Excel tiene 891 filas pero probablemente muchos empleadores repetidos. Antes de crear registros en `employers`:
1. Agrupar filas por `document_number` del pagador
2. Verificar que los datos sean consistentes entre filas del mismo empleador
3. Crear un solo registro en `employers` por NIT/CC único
4. Vincular todos los afiliados a ese empleador

---

# 11. DASHBOARD OPERATIVO

## 11.1 KPIs principales (visible al abrir la aplicación)

```
┌──────────────────────────────────────────────────────────────────┐
│           SERVICONLI — Panel de Operaciones PILA                 │
│                    Marzo 2026                                     │
├────────────┬──────────────┬───────────────┬──────────────────────┤
│ 664        │ 227          │ 48            │ 23                   │
│ Activos    │ Inactivos    │ En mora       │ Anticipados          │
├────────────┴──────────────┴───────────────┴──────────────────────┤
│                    VENCIMIENTOS                                   │
├──────────────┬──────────────┬─────────────┬──────────────────────┤
│ 12 Hoy       │ 35 Esta sem. │ 488 Día 5   │ Ver calendario       │
├──────────────┴──────────────┴─────────────┴──────────────────────┤
│                    POR OPERADOR                                   │
├──────────────────────────────┬───────────────────────────────────┤
│ Arus: 702 (78.8%)            │ Simple: 38 (4.3%)                 │
│ NA/Solo afiliaciones: 58     │ Otros: 15                         │
└──────────────────────────────┴───────────────────────────────────┘
```

## 11.2 Alertas automáticas

El sistema debe generar alertas visibles en el panel para:

| Alerta | Condición | Prioridad |
|---|---|---|
| Vencimiento hoy | `payment_business_day` coincide con hoy | ALTA |
| Vencimiento mañana | `payment_business_day` = mañana | ALTA |
| En mora | `payment_status = overdue` | ALTA |
| Credencial sin actualizar | `password_updated_at` > 180 días | MEDIA |
| Sin credencial PILA | `pila_credentials` vacío para activo | MEDIA |
| Afiliado sin EPS | `eps_entity_id` null para activo | MEDIA |

## 11.3 Vista de calendario de vencimientos

Una vista mensual tipo calendario que muestra cuántos clientes vencen cada día hábil:
- Día 2: 26 clientes
- Día 5: 488 clientes (color rojo — carga alta)
- Día 12: 23 clientes
- ...etc.

---

# 12. PERMISOS Y ROLES

## Definición de roles relevantes al módulo PILA

| Acción | admin | supervisor | agent | viewer |
|---|---|---|---|---|
| Ver lista de afiliados | ✓ | ✓ | ✓ | ✓ |
| Ver ficha completa del afiliado | ✓ | ✓ | ✓ | ✓ |
| Crear nuevo afiliado | ✓ | ✓ | ✓ | ✗ |
| Editar datos del afiliado | ✓ | ✓ | ✓ | ✗ |
| Desactivar afiliado | ✓ | ✓ | ✗ | ✗ |
| Ver usuarios de portales | ✓ | ✓ | ✓ | ✗ |
| **Ver contraseñas** | ✓ | ✓ | Solo asignados | ✗ |
| **Editar contraseñas** | ✓ | ✓ | ✗ | ✗ |
| Ver log de auditoría de credenciales | ✓ | ✓ | ✗ | ✗ |
| Importar datos desde Excel | ✓ | ✗ | ✗ | ✗ |
| Ver dashboard con KPIs | ✓ | ✓ | ✓ | ✓ |
| Exportar listados | ✓ | ✓ | ✓ | ✗ |

---

# 13. ORDEN DE IMPLEMENTACIÓN

El módulo se construye en 4 sprints progresivos, de modo que cada sprint entrega algo funcional y usable:

## Sprint 1 — Base de datos y datos de referencia

**Meta:** Tener la estructura de BD lista y cargada con datos de catálogo.

1. Crear migraciones de todas las tablas del módulo
2. Crear seeders con datos de catálogo (cotizante_types, social_entities, risk_classes)
3. Crear modelos Eloquent con relaciones básicas
4. Verificar en tinker que las relaciones funcionan correctamente

**Entregable:** BD lista, seeder completo, modelos funcionando.

---

## Sprint 2 — Gestión de Afiliados y Empleadores

**Meta:** Los asesores pueden registrar y consultar afiliados sin credenciales.

1. CRUD completo de empleadores (con validaciones)
2. CRUD completo de afiliados (con validaciones)
3. CRUD de afiliaciones (datos laborales y entidades)
4. Panel de listado con filtros (reemplaza la vista del Excel)
5. Ficha completa del afiliado con las primeras 4 pestañas
6. DeadlineService funcionando (cálculo de fecha límite)

**Entregable:** Los asesores pueden gestionar afiliados desde la web.

---

## Sprint 3 — Credenciales Seguras

**Meta:** Migrar las 2,121 credenciales del Excel de forma segura.

1. Implementar CredentialService (cifrado + auditoría)
2. Tablas pila_credentials y portal_credentials
3. UI para ver/editar credenciales con control de permisos
4. Log de auditoría visible para supervisores/admin
5. AffiliateImportService para importar desde Excel
6. Script de migración inicial de los datos del Excel

**Entregable:** Credenciales migradas, cifradas y auditadas. El Excel ya no es necesario para consultar contraseñas.

---

## Sprint 4 — Dashboard y Automatizaciones

**Meta:** El panel operativo reemplaza completamente el Excel en el día a día.

1. Dashboard con KPIs y alertas
2. Vista de calendario de vencimientos
3. Scheduled Job para transiciones de estado al cambio de mes (RN-12)
4. Exportación de listados a Excel/PDF
5. Notificaciones de vencimientos próximos
6. Pruebas de usuario con el equipo operativo
7. Capacitación y puesta en producción

**Entregable:** El Excel se congela. El equipo opera exclusivamente desde Laravel.

---

## Resumen del módulo

| Elemento | Cantidad |
|---|---|
| Tablas nuevas en MySQL | 9 |
| Tablas que se amplían (Access) | 2 (affiliates, affiliations) |
| Registros a migrar del Excel | 891 afiliados |
| Credenciales a cifrar y migrar | ~2,121 |
| Servicios de negocio | 4 |
| Datos de catálogo (seeders) | 3 (17 + 95 + 6 registros) |
| Sprints de implementación | 4 |

---

*Documento preparado para el equipo de desarrollo de Serviconli*
*Fuentes: DataSegura_SERVICONLI_2025.xlsx + AplicativoV6.accdb + KNOWLEDGE_BASE_EXCEL.md + KNOWLEDGE_BASE_SERVICONLI.md*
*Este documento describe la ARQUITECTURA, no el código. El código se implementa sprint a sprint.*
