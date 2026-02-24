# Referencias: Afiliado vs Paciente

**Regla de negocio:** En el sistema todo se gestiona con **afiliado** (Affiliate). No se debe usar el concepto "paciente" en la UI ni en mensajes al usuario.

---

## Cambios realizados (todo apunta a “afiliado” en lo visible)

- **UI (Vue):** Todas las etiquetas y textos que decían "Paciente" o "paciente" se reemplazaron por "Afiliado" o "afiliado" en:
  - Appointments (Show, Edit, Create, Index)
  - AppointmentRequests (Create, Index)
  - Admin: WhatsAppSends, Communications, Metrics/Annotations
- **Backend:** Mensaje en `AppointmentController` y comentario en `AppointmentStatus` actualizados a "afiliado".
- **Función en Vue:** `patientStatusBadge` renombrada a `affiliateStatusBadge` en AppointmentRequests/Create.vue.

---

## Lo que sigue con nombre “patient” (solo interno / legado)

| Dónde | Qué | Nota |
|------|-----|------|
| **BD** | Columna `affiliates.patient_type` | Valores: `cotizante`, `beneficiario`. Es el "tipo de afiliado". Nombre legado; opcional renombrar a `affiliate_type` en una migración futura. |
| **Backend** | Enum `App\Modules\Patients\Enums\PatientType` | Mismo concepto: cotizante/beneficiario. Comentario en el enum aclara que es "tipo de afiliado". |
| **Backend** | Requests y Resource usan clave `patient_type` | CreateAffiliateRequest, UpdateAffiliateRequest, AffiliateResource exponen `patient_type` / `patient_type_label`. La API sigue enviando esas claves; el frontend las usa. Si se renombra a `affiliate_type`, habría que actualizar API y Vue a la vez. |
| **Vue (Afiliates)** | Props `patientTypes`, `form.patient_type` | Coinciden con la API. En pantalla se muestra "Cotizante" / "Beneficiario" (labels del enum), no la palabra "paciente". |
| **Migraciones ya ejecutadas** | Comentarios o columnas `patient_id` en migraciones antiguas | Histórico (refactor patients → affiliates). No modificar migraciones ya corridas. |
| **Scripts** | `import_patients.php`, `import_beneficiaries.php` | Usan `Patient::`; son scripts legacy. Deberían pasarse a Affiliate o deprecarse. |

No existe modelo `Patient` ni tabla `patients` en el código activo; la tabla es `affiliates` y el modelo es `Affiliate`. Las relaciones en citas y solicitudes son `affiliate_id` y `affiliate()`.

---

## Resumen

- **Usuario y documentación:** Solo se habla de **afiliado**.
- **Código y BD:** Se usa **Affiliate** y **affiliate_id**. El único nombre "patient" que queda es el de la columna/enum **patient_type** (tipo de afiliado: cotizante/beneficiario), por legado; no hace referencia a un "paciente" como entidad.
