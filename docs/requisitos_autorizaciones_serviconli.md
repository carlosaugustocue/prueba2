# ESPECIFICACIÓN DE REQUISITOS FUNCIONALES
## Módulo de Gestión de Autorizaciones Médicas
### Sistema de Central de Citas — Serviconli

| Campo | Detalle |
|---|---|
| **Proyecto** | Serviconli — Central de Citas Médicas |
| **Versión** | 1.0 |
| **Fecha** | 21 de febrero de 2026 |
| **Elaborado por** | Equipo de Desarrollo Serviconli |
| **Estado** | En revisión |

---

## 1. Actores del Sistema

| Actor | Descripción |
|---|---|
| **Afiliado** | Usuario registrado en Serviconli cuyo pagador directo es Serviconli. Tiene derecho a solicitar citas médicas y gestionar sus autorizaciones ante la EPS. |
| **Personal Administrativo** | Funcionario de Serviconli encargado de gestionar las solicitudes, autorizaciones y citas médicas de los afiliados dentro del sistema. |
| **Sistema** | Componente automatizado de la plataforma que ejecuta validaciones, transiciones de estado, notificaciones y alertas de forma automática. |
| **EPS** | Entidad Promotora de Salud externa que recibe, evalúa y responde las solicitudes de autorización. No interactúa directamente con el sistema. |
| **IPS** | Institución Prestadora de Salud donde se presta el servicio médico autorizado. Puede ser asignada por la EPS en la autorización. |

---

## 2. Flujo General Integrado

El siguiente diagrama describe el flujo general de integración entre los módulos de Solicitudes, Autorizaciones y Citas:

```
Solicitud de Cita Médica
    ↓
¿Requiere autorización? ── No ──▶ Agendamiento directo de la cita
    ↓ Sí
Crear Autorización (Estado: Pendiente de radicación)
    ↓
Radicar ante la EPS (Estado: Radicada)
    ↓
¿Respuesta de la EPS?
    ├─ Aprobada ─▶ Registrar número, IPS y vigencia ─▶ Agendar Cita ─▶ Cita Confirmada
    └─ Negada  ─▶ Registrar motivo ─▶ Apelación / Cierre de solicitud
```

---

## 3. Gestión de Autorizaciones

### RF-AUT-01: Registro de autorización médica

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-01 |
| **Nombre** | Registro de autorización médica |
| **Descripción** | El sistema debe permitir registrar una autorización asociada a una solicitud de cita médica, capturando: tipo de servicio requerido (consulta especializada, procedimiento, examen, cirugía, etc.), diagnóstico o motivo de la solicitud, EPS del afiliado, orden médica de soporte y fecha de radicación ante la EPS. |
| **Actor(es)** | Afiliado / Personal Administrativo |
| **Prioridad** | Alta |

**Precondiciones:**
- El afiliado debe estar registrado y activo en Serviconli.
- Debe existir una solicitud de cita que requiera autorización.
- El afiliado debe contar con una orden médica válida.

**Postcondiciones:**
- Se crea un registro de autorización vinculado a la solicitud.
- La autorización queda en estado `Pendiente de radicación`.

**Flujo Principal:**
1. El usuario accede al módulo de autorizaciones.
2. Selecciona la solicitud de cita asociada.
3. Diligencia los datos requeridos y adjunta la orden médica.
4. El sistema valida la información y crea la autorización.
5. El sistema confirma el registro exitoso.

---

### RF-AUT-02: Carga de documentos de soporte

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-02 |
| **Nombre** | Carga de documentos de soporte |
| **Descripción** | El sistema debe permitir al usuario o al personal administrativo adjuntar los documentos necesarios para la autorización: orden médica, resultados de exámenes previos, historia clínica y cualquier otro documento requerido por la EPS. El sistema debe validar formatos permitidos y tamaño máximo de archivos. |
| **Actor(es)** | Afiliado / Personal Administrativo |
| **Prioridad** | Alta |

**Precondiciones:**
- Debe existir una autorización creada en el sistema.
- Los archivos deben estar en formatos válidos (PDF, JPG, PNG).

**Postcondiciones:**
- Los documentos quedan asociados al registro de autorización.
- El sistema confirma la carga exitosa.

**Flujo Principal:**
1. El usuario accede al detalle de la autorización.
2. Selecciona los archivos a adjuntar.
3. El sistema valida formato y tamaño.
4. Los documentos se almacenan y vinculan a la autorización.
5. Se muestra confirmación al usuario.

---

### RF-AUT-03: Gestión de estados del ciclo de vida de la autorización

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-03 |
| **Nombre** | Gestión de estados del ciclo de vida de la autorización |
| **Descripción** | El sistema debe gestionar los siguientes estados: `Pendiente de radicación`, `Radicada`, `Aprobada`, `Negada`, `Vencida` y `En apelación`. Cada transición de estado debe quedar registrada con fecha, hora y usuario responsable. |
| **Actor(es)** | Personal Administrativo / Sistema |
| **Prioridad** | Alta |

**Precondiciones:**
- Debe existir una autorización registrada en el sistema.

**Postcondiciones:**
- El estado de la autorización se actualiza correctamente.
- Se registra la traza del cambio de estado.
- Se envían notificaciones al afiliado.

**Flujo Principal:**
1. El administrador accede al detalle de la autorización.
2. Actualiza el estado según la respuesta de la EPS.
3. El sistema valida la transición y registra el cambio.
4. Se notifica al afiliado del nuevo estado.

---

### RF-AUT-04: Registro de número de autorización y vigencia

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-04 |
| **Nombre** | Registro de número de autorización y vigencia |
| **Descripción** | Cuando una autorización sea aprobada por la EPS, el sistema debe permitir registrar el número de autorización emitido, la IPS autorizada para la prestación del servicio y la fecha de vigencia de dicha autorización. |
| **Actor(es)** | Personal Administrativo |
| **Prioridad** | Alta |

**Precondiciones:**
- La autorización debe estar en estado `Radicada`.
- La EPS debe haber emitido respuesta aprobatoria.

**Postcondiciones:**
- La autorización pasa a estado `Aprobada`.
- Se registra el número, IPS y vigencia.
- La solicitud vinculada se habilita para agendamiento.

**Flujo Principal:**
1. El administrador recibe la respuesta de la EPS.
2. Accede al registro de autorización en el sistema.
3. Ingresa número de autorización, IPS y fecha de vigencia.
4. El sistema actualiza el estado a `Aprobada`.
5. Se notifica al afiliado.

---

### RF-AUT-05: Notificación de cambios de estado

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-05 |
| **Nombre** | Notificación de cambios de estado |
| **Descripción** | El sistema debe notificar al afiliado cada vez que su autorización cambie de estado, utilizando los canales disponibles: notificación interna, correo electrónico o WhatsApp según la configuración del módulo de comunicaciones. |
| **Actor(es)** | Sistema |
| **Prioridad** | Media |

**Precondiciones:**
- El afiliado debe tener datos de contacto registrados.
- El módulo de comunicaciones debe estar configurado.

**Postcondiciones:**
- El afiliado recibe la notificación por el canal configurado.
- Se registra el envío de la notificación.

**Flujo Principal:**
1. Se produce un cambio de estado en la autorización.
2. El sistema genera la notificación correspondiente.
3. Se envía al afiliado por el canal disponible.
4. Se registra la notificación en el historial.

---

### RF-AUT-06: Alerta de vencimiento de autorización

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-06 |
| **Nombre** | Alerta de vencimiento de autorización |
| **Descripción** | El sistema debe generar alertas automáticas cuando una autorización aprobada esté próxima a vencer (5 y 2 días antes), tanto al afiliado como al personal administrativo. |
| **Actor(es)** | Sistema |
| **Prioridad** | Media |

**Precondiciones:**
- La autorización debe estar en estado `Aprobada`.
- La autorización debe tener fecha de vigencia registrada.

**Postcondiciones:**
- Se envían alertas a los destinatarios correspondientes.
- Si la autorización vence sin uso, cambia a estado `Vencida`.

**Flujo Principal:**
1. El sistema verifica diariamente las autorizaciones aprobadas.
2. Identifica las próximas a vencer.
3. Genera y envía alertas a afiliado y administrador.
4. Si se cumple la fecha sin uso, cambia estado a `Vencida`.

---

### RF-AUT-07: Registro de negación y soporte para apelación

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-07 |
| **Nombre** | Registro de negación y soporte para apelación |
| **Descripción** | Cuando una autorización sea negada, el sistema debe permitir registrar el motivo proporcionado por la EPS y habilitar la opción de registrar una apelación o recurso con documentación adicional. |
| **Actor(es)** | Personal Administrativo / Afiliado |
| **Prioridad** | Alta |

**Precondiciones:**
- La autorización debe estar en estado `Radicada`.
- La EPS debe haber emitido respuesta de negación.

**Postcondiciones:**
- Se registra el motivo de negación.
- La autorización pasa a estado `Negada` o `En apelación`.
- Se notifica al afiliado.

**Flujo Principal:**
1. El administrador registra la negación con su motivo.
2. El sistema actualiza el estado a `Negada`.
3. Se notifica al afiliado.
4. El afiliado o admin puede iniciar apelación.
5. Se adjunta documentación de soporte.
6. El estado cambia a `En apelación`.

---

## 4. Integración con el Módulo de Solicitudes

### RF-AUT-08: Identificación de solicitudes que requieren autorización

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-08 |
| **Nombre** | Identificación de solicitudes que requieren autorización |
| **Descripción** | El sistema debe permitir que al crear o evaluar una solicitud de cita, se indique si el servicio solicitado requiere autorización previa por parte de la EPS. Esta clasificación puede ser manual o basada en reglas configurables según el tipo de servicio. |
| **Actor(es)** | Personal Administrativo / Sistema |
| **Prioridad** | Alta |

**Precondiciones:**
- Debe existir una solicitud de cita creada o en evaluación.
- Las reglas de autorización deben estar configuradas (si aplica).

**Postcondiciones:**
- La solicitud queda marcada como `Requiere autorización`.
- Se habilita el flujo de autorización para esa solicitud.

**Flujo Principal:**
1. Se crea o revisa una solicitud de cita médica.
2. El sistema evalúa si el tipo de servicio requiere autorización.
3. Si requiere, marca la solicitud y bloquea su avance.
4. Se informa al usuario que debe gestionar la autorización.

---

### RF-AUT-09: Vinculación solicitud-autorización

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-09 |
| **Nombre** | Vinculación solicitud-autorización |
| **Descripción** | Cuando una solicitud de cita requiera autorización, el sistema debe permitir crear y vincular una autorización directamente desde la solicitud. La solicitud permanecerá en estado `Pendiente de autorización` hasta que la autorización sea aprobada. |
| **Actor(es)** | Personal Administrativo / Afiliado |
| **Prioridad** | Alta |

**Precondiciones:**
- La solicitud debe estar marcada como `Requiere autorización`.
- No debe existir ya una autorización activa vinculada.

**Postcondiciones:**
- Se crea la autorización vinculada a la solicitud.
- La solicitud cambia a estado `Pendiente de autorización`.

**Flujo Principal:**
1. Desde la solicitud, se accede a "Crear autorización".
2. Se diligencian los datos de la autorización.
3. El sistema crea la autorización y la vincula.
4. La solicitud se actualiza a `Pendiente de autorización`.

---

### RF-AUT-10: Bloqueo de avance sin autorización aprobada

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-10 |
| **Nombre** | Bloqueo de avance sin autorización aprobada |
| **Descripción** | El sistema no debe permitir que una solicitud que requiera autorización avance al estado de cita confirmada mientras la autorización no se encuentre en estado `Aprobada`. El sistema debe informar claramente el motivo del bloqueo. |
| **Actor(es)** | Sistema |
| **Prioridad** | Alta |

**Precondiciones:**
- La solicitud debe requerir autorización.
- La autorización asociada no está en estado `Aprobada`.

**Postcondiciones:**
- Se impide el agendamiento de la cita.
- Se muestra mensaje informativo al usuario.

**Flujo Principal:**
1. Un usuario intenta agendar la cita desde la solicitud.
2. El sistema verifica el estado de la autorización.
3. Si no está aprobada, bloquea la acción.
4. Muestra mensaje indicando el estado de la autorización.

---

### RF-AUT-11: Actualización automática de la solicitud

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-11 |
| **Nombre** | Actualización automática de la solicitud |
| **Descripción** | Cuando la autorización vinculada cambie a estado `Aprobada`, el sistema debe actualizar automáticamente la solicitud para que continúe su flujo de agendamiento. Si la autorización es `Negada`, la solicitud debe reflejar esta situación. |
| **Actor(es)** | Sistema |
| **Prioridad** | Alta |

**Precondiciones:**
- Debe existir una autorización vinculada a la solicitud.
- La autorización debe cambiar de estado.

**Postcondiciones:**
- La solicitud refleja el nuevo estado de la autorización.
- Si es aprobada, se habilita el agendamiento.
- Si es negada, la solicitud se marca correspondiente.

**Flujo Principal:**
1. La autorización cambia de estado.
2. El sistema detecta el cambio automáticamente.
3. Actualiza el estado de la solicitud vinculada.
4. Notifica al afiliado del nuevo estado.

---

## 5. Integración con el Módulo de Citas

### RF-AUT-12: Validación de autorización al confirmar cita

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-12 |
| **Nombre** | Validación de autorización al confirmar cita |
| **Descripción** | Al confirmar o agendar una cita médica, el sistema debe validar que si el servicio requiere autorización, esta exista, esté en estado `Aprobada` y dentro de su período de vigencia. |
| **Actor(es)** | Sistema / Personal Administrativo |
| **Prioridad** | Alta |

**Precondiciones:**
- Debe existir una solicitud con autorización aprobada.
- La autorización debe estar vigente.

**Postcondiciones:**
- La cita se agenda exitosamente.
- La cita queda vinculada a la autorización.

**Flujo Principal:**
1. Se procede a confirmar la cita médica.
2. El sistema valida existencia y estado de autorización.
3. Verifica que la fecha esté dentro de la vigencia.
4. Si es válida, confirma la cita.
5. Si no es válida, bloquea y muestra el motivo.

---

### RF-AUT-13: Asociación cita-autorización

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-13 |
| **Nombre** | Asociación cita-autorización |
| **Descripción** | Cada cita que derive de un servicio autorizado debe quedar vinculada al registro de autorización correspondiente, permitiendo trazabilidad completa entre orden médica, autorización, solicitud y cita. |
| **Actor(es)** | Sistema |
| **Prioridad** | Alta |

**Precondiciones:**
- La autorización debe estar en estado `Aprobada`.
- La cita debe corresponder al servicio autorizado.

**Postcondiciones:**
- La cita queda vinculada a la autorización.
- Se puede consultar la trazabilidad completa.

**Flujo Principal:**
1. Se confirma la cita médica.
2. El sistema vincula la cita con la autorización.
3. Se actualiza el registro para reflejar la asociación.
4. La trazabilidad queda disponible para consulta.

---

### RF-AUT-14: Validación de IPS autorizada

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-14 |
| **Nombre** | Validación de IPS autorizada |
| **Descripción** | Si la autorización emitida por la EPS especifica una IPS determinada, el sistema debe validar que la cita se agende en dicha IPS o alertar en caso de discrepancia. |
| **Actor(es)** | Sistema |
| **Prioridad** | Media |

**Precondiciones:**
- La autorización debe tener IPS registrada.
- Se está agendando una cita con autorización.

**Postcondiciones:**
- La cita se agenda en la IPS correcta.
- En caso de discrepancia, se alerta al usuario.

**Flujo Principal:**
1. El usuario selecciona la IPS para la cita.
2. El sistema compara con la IPS de la autorización.
3. Si coincide, permite continuar.
4. Si no coincide, muestra alerta de discrepancia.

---

### RF-AUT-15: Restricción por vigencia de autorización

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-15 |
| **Nombre** | Restricción por vigencia de autorización |
| **Descripción** | El sistema no debe permitir agendar una cita en una fecha posterior a la vigencia de la autorización. Si el afiliado intenta hacerlo, debe notificarle que requiere renovación ante la EPS. |
| **Actor(es)** | Sistema |
| **Prioridad** | Alta |

**Precondiciones:**
- La autorización debe tener fecha de vigencia registrada.
- Se está seleccionando fecha para la cita.

**Postcondiciones:**
- Se impide agendar fuera de la vigencia.
- Se informa al usuario sobre la necesidad de renovación.

**Flujo Principal:**
1. El usuario selecciona una fecha para la cita.
2. El sistema verifica contra la vigencia de la autorización.
3. Si está dentro de vigencia, permite continuar.
4. Si excede, bloquea y sugiere renovar la autorización.

---

## 6. Consulta y Seguimiento

### RF-AUT-16: Panel de autorizaciones en el dashboard

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-16 |
| **Nombre** | Panel de autorizaciones en el dashboard |
| **Descripción** | El sistema debe integrar en el dashboard indicadores resumen: autorizaciones pendientes de radicación, en espera de respuesta, aprobadas sin cita agendada y próximas a vencer. |
| **Actor(es)** | Personal Administrativo |
| **Prioridad** | Media |

**Precondiciones:**
- El usuario debe tener acceso al dashboard.
- Deben existir registros de autorizaciones en el sistema.

**Postcondiciones:**
- Se visualizan los indicadores actualizados en tiempo real.

**Flujo Principal:**
1. El administrador accede al dashboard.
2. El sistema calcula los indicadores de autorizaciones.
3. Se muestran las tarjetas resumen correspondientes.
4. El usuario puede hacer clic para ver el detalle.

---

### RF-AUT-17: Historial de autorizaciones por afiliado

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-17 |
| **Nombre** | Historial de autorizaciones por afiliado |
| **Descripción** | El sistema debe permitir consultar el historial completo de autorizaciones de un afiliado, incluyendo estado actual, documentos asociados y su relación con solicitudes y citas. |
| **Actor(es)** | Personal Administrativo / Afiliado |
| **Prioridad** | Media |

**Precondiciones:**
- El afiliado debe estar registrado en el sistema.
- Deben existir registros de autorizaciones asociados.

**Postcondiciones:**
- Se muestra el historial completo organizado cronológicamente.

**Flujo Principal:**
1. Se accede al perfil del afiliado o módulo de autorizaciones.
2. Se selecciona "Historial de autorizaciones".
3. El sistema muestra todas las autorizaciones con sus estados.
4. Se puede acceder al detalle de cada una.

---

### RF-AUT-18: Filtros y búsqueda de autorizaciones

| Campo | Detalle |
|---|---|
| **ID** | RF-AUT-18 |
| **Nombre** | Filtros y búsqueda de autorizaciones |
| **Descripción** | El sistema debe permitir buscar y filtrar autorizaciones por: estado, EPS, afiliado, rango de fechas, tipo de servicio y número de autorización. |
| **Actor(es)** | Personal Administrativo |
| **Prioridad** | Media |

**Precondiciones:**
- El usuario debe tener acceso al módulo de autorizaciones.

**Postcondiciones:**
- Se muestra la lista filtrada según los criterios seleccionados.

**Flujo Principal:**
1. El administrador accede al listado de autorizaciones.
2. Aplica uno o más filtros disponibles.
3. El sistema retorna los resultados correspondientes.
4. Se puede exportar o consultar el detalle de cada registro.
