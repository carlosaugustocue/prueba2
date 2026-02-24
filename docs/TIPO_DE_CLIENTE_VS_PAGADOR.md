# Diferencia entre Tipo de cliente y Pagador

En el perfil de seguridad social del afiliado aparecen dos conceptos que no son lo mismo. Este documento aclara la diferencia y cómo se usan en el sistema.

---

## 1. Tipo de cliente (`client_type`)

| Qué es | Dónde está | Valores típicos |
|--------|------------|------------------|
| **Clasificación del afiliado** según su relación o régimen con Serviconli / con el sistema. | Tabla `client_types`; en el perfil SS: `social_security_profiles.client_type_id` → `ClientType` (code: SERVICONLI, INDEPENDENT, DEPENDENT, FOREIGN_RESIDENT). | **SERVICONLI**, INDEPENDENT, DEPENDENT, FOREIGN_RESIDENT |

**Significado de “SERVICONLI” como tipo de cliente:**  
El afiliado es **cliente de Serviconli** en el sentido operativo: la empresa los administra y les ofrece trámite de citas como valor agregado. Es la **relación comercial/operativa** con Serviconli.

- Viene del origen de datos (ej. DataSegura, columna B).
- No indica quién paga los aportes a la seguridad social; indica **quién los administra / de quién es cliente**.

---

## 2. Pagador (`payer`)

| Qué es | Dónde está | Ejemplo |
|--------|------------|--------|
| **La empresa o persona (NIT/documento)** que **paga** los aportes a salud, pensión, ARL, etc. | Tabla `payers`; en el perfil SS: `social_security_profiles.payer_id` → `Payer` (name, document_number, etc.). Un mismo pagador puede tener muchos afiliados. | Serviconli S.A.S. (NIT 900.xxx.xxx), Constructora ABC (NIT 800.xxx.xxx), etc. |

**Significado:**  
El **pagador** es la entidad legal que aparece como responsable del pago ante la PILA. Puede ser:

- La misma empresa Serviconli (cuando paga bajo su NIT).
- Otra empresa (el empleador o contratante real).

En el sistema, el registro de Serviconli como empresa pagadora se marca con el campo **`is_serviconli`** en la tabla `payers` (un solo registro en `true`).

---

## 3. Resumen de la diferencia

| Concepto | Pregunta que responde | Ejemplo |
|----------|------------------------|---------|
| **Tipo de cliente** | ¿Qué tipo de relación tiene este afiliado con Serviconli? (cliente nuestro, independiente, dependiente, etc.) | “SERVICONLI” = lo administramos y le damos trámite de citas. |
| **Pagador** | ¿Qué NIT/empresa paga los aportes a seguridad social? | “Serviconli S.A.S.” (NIT X) o “Constructora Y” (NIT Z). |

Un afiliado puede tener:

- **Tipo de cliente = SERVICONLI** (es nuestro cliente, lo administramos) y **Pagador = otra empresa** (esa empresa paga los aportes).
- O **Tipo de cliente = SERVICONLI** y **Pagador = Serviconli** (cuando el NIT que paga es el de Serviconli).

Por eso en la ficha del afiliado pueden verse ambos datos y no siempre coinciden.

---

## 4. Uso en el sistema (citas y autorizaciones)

Para decidir **qué afiliados pueden tener solicitudes de cita, citas y autorizaciones** gestionadas en el sistema, se usa el scope **`whereServiconliManaged()`**, que incluye a un afiliado si se cumple **al menos una** de estas dos condiciones:

1. Su **pagador** es Serviconli (`payer.is_serviconli = true`), **o**
2. Su **tipo de cliente** es SERVICONLI (`client_type.code = 'SERVICONLI'`).

Así se incluye tanto a quienes pagan con el NIT de Serviconli como a quienes aparecen como “Tipo de cliente: SERVICONLI” en la ficha (clientes administrados por Serviconli con trámite de citas).

Si el negocio decide más adelante usar **solo pagador** o **solo tipo de cliente** como criterio único, basta con cambiar el scope que se use en la búsqueda y validaciones (por ejemplo volver a `whereServiconliAsPayer()` si se decide que solo cuente el pagador).

---

## 5. Glosario rápido

- **Tipo de cliente:** clasificación del afiliado (SERVICONLI, INDEPENDENT, DEPENDENT, FOREIGN_RESIDENT). Responde: “¿qué tipo de relación tiene con Serviconli?”.
- **Pagador:** empresa/NIT que paga los aportes (tabla `payers`). Responde: “¿qué NIT paga la seguridad social?”.
- **`is_serviconli` (en payers):** indica que ese registro de pagador es el NIT de Serviconli como empresa pagadora.
