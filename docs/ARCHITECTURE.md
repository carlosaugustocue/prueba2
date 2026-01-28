# Arquitectura del Sistema - Serviconli
## Sistema de Gestión de Citas Médicas

---

## 1. Visión General

Este proyecto implementa un **monolito modular** en Laravel 12 con una interfaz centrada en **flujos de trabajo** y **productividad**.

### Filosofía de Diseño

> **"La herramienta debe adaptarse al trabajo, no el trabajo a la herramienta"**

El personal de Serviconli necesita:
- ✅ **Encontrar rápido** → Búsqueda potente de pacientes/citas
- ✅ **Actuar rápido** → Acciones con 1-2 clics
- ✅ **Ver lo importante** → Dashboard con alertas y pendientes
- ✅ **Automatizar** → Recordatorios y confirmaciones automáticas

---

## 2. Estructura de Módulos

```
app/
├── Modules/
│   ├── Core/              # Funcionalidades base compartidas
│   ├── Auth/              # Autenticación y autorización
│   ├── Patients/          # Gestión de pacientes
│   ├── Appointments/      # Gestión de citas (módulo principal)
│   └── Integrations/      # WhatsApp, Email, etc.
```

## 3. Flujo de Estados de Cita

```
PENDIENTE → EN_PROGRESO → CONFIRMADA → ENVIADA → COMPLETADA
                                              ↓
                                         CANCELADA
```

## 4. Integración con WhatsApp

El sistema está preparado para integrarse con Meta WhatsApp Business API.
Las plantillas de mensajes están definidas según los requisitos RF-20 y RF-21.

## 5. Base de Datos

- MySQL 8.0+ (configurado en `.env`)
- Tablas principales: users, roles, patients, eps, appointments, reminders, appointment_histories
