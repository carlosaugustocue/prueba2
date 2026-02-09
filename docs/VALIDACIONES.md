# Validaciones del sistema

Resumen de validaciones implementadas y sugerencias de mejoras.

## Implementadas

### Solicitudes de cita (Create)
- Paciente, tipo y prioridad requeridos.
- Especialidad requerida cuando tipo = Especialista (backend + frontend).
- Notas del cliente opcionales, max 1000 caracteres.

### Solicitudes de cita (Anotaciones)
- Al guardar anotaciones: al menos uno de `note` u `operator_notes` requerido; max 5000 caracteres. Contenido vacío (solo espacios) rechazado en controlador.

### Citas (Create)
- Paciente, tipo, prioridad, fecha y hora requeridos.
- Especialidad requerida cuando tipo = Especialista.
- Fecha hoy o futura; hora no pasada cuando la fecha es hoy.

### Citas (Update)
- Si se envía tipo = Especialista, especialidad requerida.
- Fecha/hora: si se envían ambas, no pueden ser pasadas.

### Pacientes (Create/Update)
- Documento, nombres, apellidos, EPS, tipo de paciente requeridos.
- Beneficiario: cotizante y parentesco requeridos; cotizante debe existir y ser tipo cotizante.
- Documento único en creación; único excluyendo el propio en edición.
- Teléfono/WhatsApp/email opcionales con max; email con formato; birth_date antes de hoy.

### Usuarios (Admin)
- Nombre, email, rol requeridos; email único (ignorando el propio en update).
- Contraseña: min 8, confirmación (solo en create; en update opcional si se cambia).

### Login
- Email y contraseña requeridos; email con formato.

---

## Sugerencias (opcionales)

### Pacientes
- **document_number**: formato según `document_type` (ej. solo dígitos para CC, regex por tipo de documento).
- **first_name / last_name**: `min:2` para evitar una sola letra.
- **phone / whatsapp**: regex o `min` para longitud mínima (ej. 10 dígitos).
- **birth_date**: `after:1900-01-01` para evitar fechas absurdas.
- Mensajes en español para todos los atributos (required, exists, etc.).

### Citas
- **authorization_number**: si en tu flujo es obligatorio en ciertos tipos de cita, usar `required_if:type,...` o regla a medida.

### Usuarios (Admin)
- **password**: regla de complejidad (mayúscula, número, carácter especial) si es requisito de seguridad.

### General
- Sanitizar HTML en textos largos (notas, anotaciones) si hay riesgo de XSS.
- Límites de tasa (throttling) en login y en APIs públicas si aplica.
