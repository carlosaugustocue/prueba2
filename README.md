# Serviconli - Sistema de Gestión de Citas Médicas

Sistema de gestión de citas médicas para la Central de Citas de Serviconli, desarrollado con Laravel 12 (monolito modular) y Vue.js 3.

## 🚀 Características

- ✅ **Gestión de Citas**: Crear, editar, filtrar y dar seguimiento a citas médicas
- ✅ **Gestión de Pacientes**: Registro de pacientes (cotizantes y beneficiarios)
- ✅ **Estados de Cita**: Pendiente → En Progreso → Confirmada → Enviada → Completada
- ✅ **Prioridades**: Urgente, Alta, Media, Baja
- ✅ **Integración WhatsApp**: Envío de confirmaciones y recordatorios automáticos
- ✅ **Historial de Cambios**: Trazabilidad completa de cada cita
- ✅ **Dashboard**: Vista rápida de estadísticas y citas del día

## 📋 Requisitos

- PHP 8.2+
- Composer 2.x
- Node.js 18+ y npm
- MySQL 8.0

## 🔧 Instalación

### 1. Instalar dependencias

```bash
cd /home/ksp/IdeaProjects/serviconli-system
composer install
npm install
```

### 2. Configurar base de datos

Edita el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=serviconli_system
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 3. Crear la base de datos y ejecutar migraciones

```bash
php artisan migrate
php artisan db:seed
```

### 4. Iniciar el proyecto

```bash
# En una terminal:
npm run dev

# En otra terminal:
php artisan serve
```

Visita: http://localhost:8000

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
│   ├── Core/              # Traits, Contracts, Helpers
│   ├── Auth/              # Login, Roles, Usuarios
│   ├── Patients/          # Pacientes, EPS
│   ├── Appointments/      # Citas, Historial, Recordatorios
│   └── Integrations/      # WhatsApp, Email
│
├── Http/Middleware/       # HandleInertiaRequests, CheckRole
└── Providers/             # ModuleServiceProvider
```

## 📱 Configuración de WhatsApp

Agrega al `.env`:

```env
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id
WHATSAPP_ACCESS_TOKEN=tu_access_token
```

## 📝 Licencia

Software propietario - © 2025 Serviconli
