# RecuperaYa – Dónde está programado cada procedimiento

Abre la carpeta del proyecto en VS Code y usa **Ctrl + P** para abrir rápido cada archivo.

| Proc. | Procedimiento | Ruta (URL) | Controlador | Validación (Form Request) | Vista |
|---|---|---|---|---|---|
| P-01 | Registro de usuario con correo institucional | GET/POST `/registro` | `app/Http/Controllers/AuthController.php` → `showRegistro`, `registro` | `app/Http/Requests/RegistroRequest.php` | `resources/views/auth/registro.blade.php` |
| P-02 | Inicio y cierre de sesión (bloqueo 5 intentos / 15 min) | GET/POST `/login`, POST `/salir` | `AuthController.php` → `login`, `logout` | `LoginRequest.php` | `resources/views/auth/login.blade.php` |
| P-03 | Gestión de roles, usuarios, categorías y lugares | `/admin/usuarios`, `/admin/categorias`, `/admin/lugares` | `app/Http/Controllers/Admin/UsuarioController.php`, `CategoriaController.php`, `LugarController.php` | `UpdateUsuarioRequest.php`, `StoreCatalogoRequest.php` | `resources/views/admin/...` |
| P-04 | Recepción y registro del objeto encontrado | GET `/objetos/crear`, POST `/objetos` | `ObjetoController.php` → `create`, `store` | `StoreObjetoRequest.php` (editar: `UpdateObjetoRequest.php`) | `resources/views/objetos/create.blade.php`, `_form.blade.php` |
| P-05 | Publicación en el catálogo | GET `/catalogo`, `/catalogo/{slug}` | `CatalogoController.php` → `index`, `show` | — | `resources/views/catalogo/index.blade.php`, `show.blade.php` |
| P-06 | Control de objetos no reclamados (90 días) | (automático al abrir inicio, objetos o estadísticas) | `app/Models/Objeto.php` → `marcarNoReclamados()` | — | `objetos/index.blade.php` (filtro por estado) |
| P-07 | Búsqueda en el catálogo con filtros | GET `/catalogo?q=...&categoria=...` | `CatalogoController.php` → `index` | — | `catalogo/index.blade.php` |
| P-08 | Registro del reporte de pérdida | GET `/reportes/crear`, POST `/reportes`, DELETE `/reportes/{id}` | `ReportePerdidaController.php` | `StoreReportePerdidaRequest.php` | `resources/views/reportes/create.blade.php`, `index.blade.php` |
| P-09 | Búsqueda automática de coincidencias | (automático después de P-04 y P-08) | `app/Services/MotorCoincidencias.php` → `puntaje`, `procesarObjeto`, `procesarReporte` | — | — |
| P-10 | Envío de notificaciones | GET `/avisos` | `app/Services/Notificador.php`, `NotificacionController.php` | — | `resources/views/notificaciones/index.blade.php` |
| P-11 | Solicitud de reclamo | POST `/catalogo/{slug}/reclamar`, GET `/mis-reclamos` | `ReclamoController.php` → `store`, `mis` | `StoreReclamoRequest.php` | `catalogo/show.blade.php`, `reclamos/mis.blade.php` |
| P-12 | Validación de las respuestas (2 intentos) | (dentro de P-11) | `app/Services/ReclamoService.php` → `reclamar` | — | — |
| P-13 | Verificación del carné y registro de la entrega | GET/POST `/entregas` | `EntregaController.php` + `ReclamoService.php` → `entregar` | `StoreEntregaRequest.php` | `resources/views/entregas/index.blade.php` |
| P-14 | Generación de reportes e indicadores | GET `/reportes-estadisticos`, `/imprimir` | `EstadisticaController.php` → `index`, `imprimir` | — | `resources/views/estadisticas/...` |
| P-15 | Exportación de datos y predicción con ML | GET `/reportes-estadisticos/excel`, `/admin/reportes-estadisticos/datos-ml` | `EstadisticaController.php` → `csv`, `ml` + `app/Services/EstadisticaService.php` | — | — |

Todas las rutas están en `routes/web.php`. Los permisos por rol están en `app/Providers/AppServiceProvider.php` (Gates `reportar`, `gestionar`, `administrar`).
Las tablas están en `database/migrations/` y los modelos en `app/Models/`.
Las pruebas de cada procedimiento están en `tests/Unit` (P-09) y `tests/Feature` (P-01, P-02, P-04, P-08, P-11, P-12, P-13).
