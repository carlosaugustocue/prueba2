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

### 5. Acceso desde otros equipos en la red interna

La aplicación **no depende de una IP fija**: en entorno local usa el host de cada petición, así que el mismo código y `.env` sirven en cualquier equipo.

1. **En el equipo donde corre el servidor**, inicia Laravel escuchando en todas las interfaces:
   ```bash
   php artisan serve --host=0.0.0.0
   ```
   O: `composer run serve:network`

2. **No hace falta configurar una IP en `.env`**. Mantén `APP_URL=http://localhost:8000`; las URLs se generan con el host desde el que se accede (localhost, 192.168.x.x, etc.).

3. **Desde otro equipo** abre en el navegador la IP del servidor y el puerto, por ejemplo:
   ```
   http://192.168.1.65:8000
   ```

Si usas **Vite en modo dev** (`npm run dev`) y abres desde otro equipo, los assets pueden cargar desde el mismo host. Si el hot reload (HMR) no funciona, en el servidor puedes definir en `.env` la IP de esa máquina en `VITE_HMR_HOST`. En producción usa `npm run build`; el front se sirve desde Laravel.

### Si no puedes conectar desde el celular

**Síntoma:** En el celular pones `http://192.168.1.139:8000` y dice que no se puede acceder (o se corta la conexión), pero el equipo 192.168.1.139 sí responde en otros casos.  
**Causa habitual:** El servidor Laravel se inició con `php artisan serve` (sin opciones) y solo escucha en **localhost**. Por eso desde el celular el puerto 8000 no abre.  
**Solución:** Detén el servidor (Ctrl+C), y vuelve a arrancarlo escuchando en toda la red:

```bash
php artisan serve --host=0.0.0.0
```

O en un solo paso (muestra la URL y arranca):

```bash
composer run serve:network
```

Luego en el celular abre **http://192.168.1.139:8000** (usa la IP de tu PC).

**Si en el celular sale "No se puede acceder a este sitio" (ERR_CONNECTION_FAILED):** la conexión no llega al PC. Revisa en este orden:

| Paso | Qué hacer |
|------|-----------|
| 1 | En el PC, en la terminal donde corre Laravel, confirma que arrancaste con `php artisan serve --host=0.0.0.0`. Si no, detén (Ctrl+C) y vuelve a ejecutar ese comando. |
| 2 | **Firewall:** en el PC (Linux) abre el puerto 8000: `sudo ufw allow 8000` y luego `sudo ufw reload`. Comprueba con `sudo ufw status`. |
| 3 | **IP correcta:** en el PC ejecuta `php artisan serve:network-info` o `hostname -I` y usa esa IP en el celular (ej. `http://192.168.1.139:8000`). |
| 4 | Prueba desde **otro PC** en la misma Wi‑Fi abriendo `http://<IP-del-servidor>:8000`. Si ahí tampoco abre, el fallo es del servidor o del firewall del PC; si ahí sí abre, puede ser aislamiento de la red o del celular. |
| 5 | Algunos routers tienen **aislamiento de clientes** (evitan que los dispositivos Wi‑Fi se hablen entre sí). Si es posible, desactívalo en el router o prueba con el celular en la misma red cableada que el PC. |

---

1. **Ver la URL correcta**: En el PC donde corre el servidor ejecuta:
   ```bash
   php artisan serve:network-info
   ```
   Te mostrará la dirección (ej. `http://192.168.1.65:8000`) para usar en el celular.

2. **Servidor en todas las interfaces**: El servidor debe estar arrancado con:
   ```bash
   php artisan serve --host=0.0.0.0
   ```
   (o `composer run serve:network`). Si usas solo `php artisan serve`, solo escucha en localhost y el celular no podrá conectar.

3. **Firewall**: En el PC, el puerto **8000** debe estar permitido. En Linux con UFW:
   ```bash
   sudo ufw allow 8000
   sudo ufw reload
   ```
   En otros firewalls, abre una regla de entrada para el puerto TCP 8000.

4. **Misma red**: El celular debe estar en la misma red Wi‑Fi (o red interna) que el PC. No uses datos móviles.

5. **Probar desde otro PC**: Si desde otro ordenador en la red puedes abrir `http://<IP-PC>:8000`, el problema suele ser solo el firewall del celular o la red; si tampoco desde el PC, revisa el firewall del servidor y que el servidor esté con `--host=0.0.0.0`.

**Si el puerto 80 sí deja acceder desde otros equipos pero el 8000 no:** la forma más sencilla es servir la app con Apache en el puerto 80 (proxy a Laravel). Así entras con **http://192.168.1.139** (sin :8000). Pasos en **`docs/APACHE.md`** (Opción 1: copiar `docs/serviconli-proxy.conf`, activar módulos proxy y el sitio, y arrancar Laravel con `php artisan serve --host=127.0.0.1`).

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
- **[docs/PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md](docs/PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md)** — Plan paso a paso: refactor Pacientes → Afiliados y Módulo de Seguridad Social (arquitectura y desarrollo)
- **[docs/mapeo_datasegura_a_base_de_datos.md](docs/mapeo_datasegura_a_base_de_datos.md)** — Mapeo DataSegura (Excel) → BD: affiliates, social_security_profiles, payers, novelties, operator_credentials (referencia obligatoria para ImportService y esquema)

## 📱 Configuración de WhatsApp

En `.env`:

```env
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id
WHATSAPP_ACCESS_TOKEN=tu_access_token
```

## 📝 Licencia

Software propietario - © 2025 Serviconli
