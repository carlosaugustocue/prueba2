# Comandos programados en producción

En producción, los comandos de Laravel **no se ejecutan solos**. Laravel usa un **programador de tareas (scheduler)** que debe ser invocado desde el **cron del servidor**.

## 1. Configurar una sola entrada en el cron

En el servidor donde corre la aplicación (usuario con acceso al proyecto), configura **una única** línea de cron:

```bash
* * * * * cd /ruta/completa/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

- **`/ruta/completa/al/proyecto`**: reemplaza por la ruta real del proyecto (ej. `/var/www/serviconli-system`).
- Ese cron se ejecuta **cada minuto**. Laravel decide internamente qué comandos tocan en cada momento.

## 2. Comandos que se ejecutan automáticamente

| Comando | Frecuencia | Qué hace |
|--------|------------|----------|
| `appointments:dispatch-due-reminders` | Cada minuto | Encola recordatorios de citas por WhatsApp que ya debían enviarse. |
| `payroll:mark-overdue` | Una vez al día | Marca como en mora (OVERDUE) las planillas con fecha de vencimiento pasada que no están pagadas. |
| `ss:generate-pila-alerts` | Una vez al día (06:00) | Genera tareas de alerta para Seguridad Social cuando el vencimiento PILA está a 3 días o menos. |

## 3. Comandos que se ejecutan solo de forma manual

Estos **no** están en el scheduler; se usan cuando el equipo lo necesite:

- **`payroll:generate-monthly`** — Genera planillas del mes para todos los afiliados con perfil SS.  
  Ejemplo: `php artisan payroll:generate-monthly --year=2026 --month=4`
- **`payroll:settle-monthly`** — Liquida (settle) planillas pendientes del mes.  
  Ejemplo: `php artisan payroll:settle-monthly --year=2026 --month=4`
- **`ss:generate-pila-alerts`** — Se puede ejecutar a mano si quieres forzar la generación de alertas sin esperar al horario programado:  
  `php artisan ss:generate-pila-alerts`

## 4. Ver qué tareas tiene programadas Laravel

En el servidor:

```bash
php artisan schedule:list
```

Ahí verás todos los comandos y su próxima ejecución.

## 5. Resumen para el equipo de despliegue

1. Asegurar que el cron esté configurado como en la sección 1 (una sola línea que ejecuta `schedule:run` cada minuto).
2. La aplicación debe poder ejecutar `php artisan` (PHP en el PATH, permisos correctos).
3. No hace falta crear entradas de cron separadas para cada comando; el scheduler de Laravel se encarga de lanzarlos en su horario.
