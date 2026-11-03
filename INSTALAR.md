# RecuperaYa en Laravel – Guía de instalación

Estos archivos **no son un proyecto completo**: se copian **encima** de un proyecto Laravel nuevo.
Así obtienes la versión oficial de Laravel desde internet y le agregas RecuperaYa.

---

## Paso 1. Instalar los programas (una sola vez)

1. **XAMPP** (PHP 8.2 o superior + MySQL): https://www.apachefriends.org/es/
2. **Composer** (para crear proyectos Laravel): https://getcomposer.org/download/ → descarga `Composer-Setup.exe`.
   Durante la instalación, cuando pida la ruta de PHP, elige `C:\xampp\php\php.exe`.
3. **Activa extensiones de PHP**: en XAMPP → Apache → **Config** → `PHP (php.ini)`. Busca estas líneas y **quita el `;`** del inicio:
   ```
   extension=fileinfo
   extension=gd
   extension=zip
   extension=pdo_sqlite
   extension=sqlite3
   ```
   Guarda el archivo y reinicia Apache.

Comprueba en una ventana de comandos (CMD):
```
php -v
composer -V
```

## Paso 2. Crear el proyecto Laravel

En CMD:
```
cd C:\xampp\htdocs
composer create-project laravel/laravel:^12.0 recuperaya
```
(Tarda unos minutos.)

## Paso 3. Copiar los archivos de RecuperaYa

1. Descomprime `RecuperaYa_Laravel.zip`.
2. Copia **todas** las carpetas de adentro (`app`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `tests`) y el archivo `phpunit.xml` dentro de `C:\xampp\htdocs\recuperaya\`.
3. Cuando Windows pregunte, elige **"Reemplazar los archivos en el destino"**.

## Paso 4. Configurar la base de datos MySQL

1. En XAMPP inicia **Apache** y **MySQL**.
2. Abre http://localhost/phpmyadmin → **Nueva** → nombre `recuperaya` → cotejamiento `utf8mb4_unicode_ci` → **Crear**.
3. Abre el archivo `.env` del proyecto (con VS Code) y cambia estas líneas:
   ```
   APP_NAME=RecuperaYa
   APP_URL=http://127.0.0.1:8000
   APP_LOCALE=es

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=recuperaya
   DB_USERNAME=root
   DB_PASSWORD=

   MAIL_MAILER=log
   ```
   (Si esas líneas de DB aparecen con `#` al inicio, quítaselo.)

## Paso 5. Crear las tablas, los datos de prueba y el enlace de fotos

En CMD, dentro de la carpeta del proyecto:
```
cd C:\xampp\htdocs\recuperaya
php artisan migrate:fresh --seed
php artisan storage:link
php artisan route:list
```
El último comando muestra las rutas creadas con **Route::resource** (por ejemplo `objetos/crear` y `objetos/{objeto}/editar`).

## Paso 6. Abrir el sistema

```
php artisan serve
```
Abre **http://127.0.0.1:8000** en el navegador (deja la ventana de CMD abierta mientras lo usas).

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@continental.edu.pe | Admin2026* |
| Encargado | encargado@continental.edu.pe | Encargado2026* |
| Usuario | 72345678@continental.edu.pe | Usuario2026* |

Respuestas de los objetos de demostración: OBJ-0001 `un perro` / `transparente` · OBJ-0002 `totto` / `audifonos` · OBJ-0003 `3` / `un corazon` · OBJ-0004 `casio` / `calculadora cientifica`.

## Paso 7. Ejecutar las pruebas unitarias y de integración (Sesión 09)

```
php artisan test
```
- `tests/Unit` → **pruebas unitarias** (motor de coincidencias, sin base de datos).
- `tests/Feature` → **pruebas de integración** (Form Request + controlador + base de datos).

Las pruebas usan una base SQLite en memoria, así que **no borran** tus datos de MySQL.

## Qué se aplicó de la Sesión 09

| Tema | Dónde está |
|---|---|
| **Form Request** (validación de cada campo, mensajes y atributos personalizados) | `app/Http/Requests/` (10 archivos) |
| **Asignación masiva** con `$fillable` | `app/Models/Objeto.php`, `User.php`, `ReportePerdida.php`, `Entrega.php` |
| **Asignación masiva** con `$guarded` | `app/Models/Reclamo.php` (protege estado e intentos), `Categoria.php`, `Lugar.php` (`$guarded = []`) |
| **Update** con asignación masiva | `ObjetoController@update`, `CategoriaController@update` |
| **Eliminar registros** (`destroy`, método DELETE) | `ObjetoController@destroy`, `CategoriaController@destroy`, `LugarController@destroy` + botones en las vistas |
| **Route::resource** y verbos en español | `routes/web.php` y `app/Providers/AppServiceProvider.php` (`crear`, `editar`) |
| **URL amigables (slug)** con `getRouteKeyName` | `Objeto.php` y migración `objetos` → `/catalogo/celular-negro-obj-0001` |

## Problemas frecuentes

| Mensaje | Solución |
|---|---|
| `could not find driver` | Activa `extension=pdo_mysql` (y `pdo_sqlite` para las pruebas) en php.ini. |
| `The GD extension is required` / fotos no se suben | Activa `extension=gd` en php.ini y reinicia. |
| Las fotos no se ven | Ejecuta `php artisan storage:link` y revisa `APP_URL=http://127.0.0.1:8000` en `.env`. |
| `SQLSTATE[HY000] [1049] Unknown database` | Crea la base `recuperaya` en phpMyAdmin (paso 4). |
| `Vite manifest not found` | No aplica: estas vistas no usan Vite. Si aparece, borra la línea `@vite` de la vista indicada. |
