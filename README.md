# Serviconli - Sistema de Gestión de Citas Médicas

Sistema de gestión de citas médicas para la Central de Citas de Serviconli, desarrollado con Laravel 12 (monolito modular), Vue.js 3 e Inertia.

## 🚀 Características

- ✅ **Solicitudes de Cita**: Registro de solicitudes (paciente, tipo, prioridad, especialidad si aplica) y flujo de tramitación
- ✅ **Gestión de Citas**: Crear citas desde solicitudes o directo; editar, filtrar y dar seguimiento
- ✅ **Gestión de Pacientes**: Registro de pacientes (cotizantes y beneficiarios), búsqueda por documento/nombre
- ✅ **Estados de Cita**: Pendiente → En Progreso → Confirmada → Enviada → Completada
- ✅ **Prioridades**: Urgente, Alta, Media, Baja
- ✅ **Integración WhatsApp**: Envío de confirmaciones, recordatorios y aviso por no asistencia
- ✅ **Historial de Cambios**: Trazabilidad completa de cada cita
- ✅ **Dashboard**: Vista rápida de estadísticas y citas del día
- ✅ **Admin**: Usuarios, comunicaciones, métricas, envíos WhatsApp

## 📋 Requisitos

- PHP 8.2+
- Composer 2.x
- Node.js 18+ y npm
- MySQL 8.0

## 🔧 Instalación

### 1. Clonar e instalar dependencias

```bash
git clone <repo>
cd serviconli-system
composer install
npm install
```

### 2. Configurar entorno

Copia `.env.example` a `.env`, configura la aplicación y la base de datos:

```env
APP_NAME=Serviconli
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=serviconli_system
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

Genera la clave de aplicación:

```bash
php artisan key:generate
```

### 3. Base de datos

```bash
php artisan migrate
php artisan db:seed
```

### 4. Iniciar el proyecto

```bash
# Terminal 1 - assets (Vue/Vite):
npm run dev

# Terminal 2 - servidor PHP:
php artisan serve
```

Visita **http://localhost:8000**

Para usar solo assets compilados (sin Vite): `npm run build` y luego `php artisan serve`.

## 👤 Usuarios de Prueba

| Email | Contraseña | Rol |
|-------|------------|-----|
| admin@gruposerviconli.com | password | Administrador |
| supervisor@gruposerviconli.com | password | Supervisor |
| biviana@gruposerviconli.com | password | Agente |

## 🗂️ Estructura del Proyecto (Monolito Modular)

```
app/
├── Modules/
│   ├── Core/                  # Traits, Contracts, Helpers
│   ├── Auth/                  # Login, Roles, Usuarios
│   ├── Patients/              # Pacientes, EPS
│   ├── AppointmentRequests/   # Solicitudes de cita y tramitación
│   ├── Appointments/          # Citas, Historial, Recordatorios
│   ├── AdminUsers/            # CRUD usuarios (admin)
│   ├── AdminCommunications/   # Comunicaciones
│   ├── AdminMetrics/          # Métricas y reportes
│   ├── AdminWhatsApp/         # Envíos WhatsApp pendientes
│   └── Integrations/          # WhatsApp, plantillas
├── Http/Middleware/           # HandleInertiaRequests, CheckRole
└── Providers/                 # AppServiceProvider, ModuleServiceProvider
```

## 📚 Documentación

- **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** — Visión general y flujos del sistema
- **[docs/VALIDACIONES.md](docs/VALIDACIONES.md)** — Validaciones implementadas y sugerencias

## 📱 Configuración de WhatsApp

En `.env`:

```env
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id
WHATSAPP_ACCESS_TOKEN=tu_access_token
```

## 📝 Licencia

Software propietario - © 2025 Serviconli
