# Deploy en Railway (AppSalon PHP MVC)

## 1) Preparar repo
- Sube este proyecto a GitHub (incluye `railway.json` y `server.php`).

## 2) Crear proyecto en Railway
- En Railway: `New Project` -> `Deploy from GitHub repo`.
- Selecciona este repositorio.

## 3) Crear base de datos MySQL
- En el mismo proyecto: `New` -> `Database` -> `MySQL`.
- Railway inyecta variables `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, etc.

## 4) Variables del servicio web
- En tu servicio web agrega:
  - `APP_URL` = URL pública de Railway (ej: `https://tu-app.up.railway.app`)
  - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` (si usarás envío de emails)
  - Opcional: `MAIL_FROM`, `MAIL_FROM_NAME`

## 5) Importar esquema SQL
- Abre una consola MySQL conectada al servicio de Railway (o usa cliente externo).
- Ejecuta el contenido de `appsalon_mvc_php.sql`.

## 6) Verificar despliegue
- Abre la URL pública.
- Prueba:
  - Registro/Login
  - Crear cita
  - Panel admin

## Notas
- El start command ya está definido en `railway.json`.
- La app ya usa rutas relativas para `/api/*` y no depende de `localhost`.
