# Servir la aplicación con Apache (puerto 80)

Si **el puerto 80 sí deja acceder** desde otros equipos pero **el 8000 no**, la solución recomendada es usar Apache en el 80 como proxy a Laravel. Así entras con **http://192.168.1.139** (sin :8000) desde el celular u otros PCs.

Hay dos formas: **proxy** (Laravel sigue con `php artisan serve`) o **DocumentRoot** (Apache sirve Laravel directamente).

---

## Opción 1: Apache como proxy al puerto 8000 (recomendado si 8000 no es accesible desde la red)

Laravel sigue corriendo con `php artisan serve --host=127.0.0.1`. Apache recibe las peticiones en el 80 y las reenvía al 8000. Desde el celular u otro equipo usas **http://192.168.1.139** (sin :8000).

**Pasos (desde la raíz del proyecto):**

```bash
# 1. Habilitar módulos de Apache
sudo a2enmod proxy proxy_http rewrite
sudo systemctl reload apache2

# 2. Copiar la configuración de proxy incluida en el proyecto
sudo cp docs/serviconli-proxy.conf /etc/apache2/sites-available/serviconli.conf

# 3. Activar el sitio y recargar Apache
sudo a2ensite serviconli.conf
sudo systemctl reload apache2
```

**4. Arrancar Laravel** (en la carpeta del proyecto, en una terminal):

```bash
php artisan serve --host=127.0.0.1
```

Desde el celular u otro PC abre **http://192.168.1.139** (puerto 80). La página por defecto de Apache será reemplazada por la aplicación.

**Si sigues viendo "Apache2 Default Page":** desactiva el sitio por defecto y recarga:
```bash
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

---

## Opción 2: Apache sirve Laravel (DocumentRoot en `public`)

Apache sirve los archivos desde la carpeta `public` del proyecto. No hace falta tener `php artisan serve` corriendo.

1. **Habilitar módulos:**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl reload apache2
   ```

2. **Virtual host** (sustituye `/ruta/serviconli-system` por la ruta real del proyecto):
   ```bash
   sudo nano /etc/apache2/sites-available/serviconli.conf
   ```
   Contenido:
   ```apache
   <VirtualHost *:80>
       ServerName 192.168.1.139
       DocumentRoot /ruta/serviconli-system/public

       <Directory /ruta/serviconli-system/public>
           AllowOverride All
           Require all granted
           Options -Indexes +FollowSymLinks
       </Directory>
   </VirtualHost>
   ```

3. **Permisos** (el usuario de Apache debe poder leer el proyecto):
   ```bash
   # Ejemplo si el proyecto está en /home/tu_usuario/... 
   sudo chown -R www-data:www-data /ruta/serviconli-system/storage /ruta/serviconli-system/bootstrap/cache
   sudo chmod -R 775 /ruta/serviconli-system/storage /ruta/serviconli-system/bootstrap/cache
   ```

4. **Activar y recargar:**
   ```bash
   sudo a2ensite serviconli.conf
   sudo systemctl reload apache2
   ```

5. **`.env`:** Ajusta `APP_URL` para que coincida con la URL que uses, por ejemplo:
   ```env
   APP_URL=http://192.168.1.139
   ```
   (En entorno local, si en `AppServiceProvider` se fuerza la URL desde la petición, puede no ser necesario.)

Desde el celular abre **http://192.168.1.139**.

---

## Resumen

| Opción | Laravel | En el celular |
|--------|--------|----------------|
| 1 – Proxy | `php artisan serve --host=127.0.0.1` | http://192.168.1.139 |
| 2 – DocumentRoot | No hace falta artisan serve | http://192.168.1.139 |

Si el puerto 80 ya está abierto en el firewall (porque Apache responde), con cualquiera de las dos opciones podrás entrar desde el celular sin usar el puerto 8000.
