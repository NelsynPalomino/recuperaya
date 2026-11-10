"""Genera el informe técnico individual (Word) de RecuperaYa a partir de las evidencias del proyecto.

Uso: python docs/informe/generar_informe.py   (desde la raíz del proyecto)
Lee: diagramas/*.png, capturas/*.png, cypress/screenshots, cypress/reports/*.xml y sonar-metricas.json
"""
import glob
import json
import pathlib
import xml.etree.ElementTree as ET

from docx import Document
from docx.enum.section import WD_ORIENT
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

RAIZ = pathlib.Path(__file__).resolve().parents[2]
DOCS = RAIZ / 'docs' / 'informe'
CAP = DOCS / 'capturas'
DIA = DOCS / 'diagramas'
SHOT = RAIZ / 'cypress' / 'screenshots'
MORADO = RGBColor(0x3B, 0x1D, 0x8F)
SONAR = json.loads((DOCS / 'sonar-metricas.json').read_text(encoding='utf-8'))

doc = Document()
sec = doc.sections[0]
sec.page_width, sec.page_height = Cm(21), Cm(29.7)
sec.left_margin = sec.right_margin = Cm(2.5)
sec.top_margin = sec.bottom_margin = Cm(2.2)
ANCHO = Cm(16)

estilo = doc.styles['Normal']
estilo.font.name = 'Arial'
estilo.element.rPr.rFonts.set(qn('w:eastAsia'), 'Arial')
estilo.font.size = Pt(11)
estilo.paragraph_format.space_after = Pt(6)
estilo.paragraph_format.line_spacing = 1.15
for n, tam in [(1, 16), (2, 13), (3, 11.5)]:
    h = doc.styles[f'Heading {n}']
    h.font.name = 'Arial'
    h.element.rPr.rFonts.set(qn('w:eastAsia'), 'Arial')
    h.font.size = Pt(tam)
    h.font.bold = True
    h.font.color.rgb = MORADO
    h.paragraph_format.space_before = Pt(14 if n == 1 else 10)
    h.paragraph_format.space_after = Pt(6)

contador = {'fig': 0, 'tab': 0}


def p(texto='', negrita=False, alinear=None, tam=None, color=None, cursiva=False):
    par = doc.add_paragraph()
    if alinear == 'c':
        par.alignment = WD_ALIGN_PARAGRAPH.CENTER
    elif alinear != 'i':
        par.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    # **negrita** en línea
    partes = texto.split('**')
    for i, t in enumerate(partes):
        r = par.add_run(t)
        r.bold = negrita or i % 2 == 1
        r.italic = cursiva
        if tam:
            r.font.size = Pt(tam)
        if color:
            r.font.color.rgb = color
    return par


def viñetas(items, numerada=False):
    for it in items:
        par = doc.add_paragraph(style='List Number' if numerada else 'List Bullet')
        partes = it.split('**')
        for i, t in enumerate(partes):
            par.add_run(t).bold = i % 2 == 1
        par.paragraph_format.space_after = Pt(2)


def sombrear(celda, color):
    tcPr = celda._tc.get_or_add_tcPr()
    shd = OxmlElement('w:shd')
    shd.set(qn('w:val'), 'clear')
    shd.set(qn('w:color'), 'auto')
    shd.set(qn('w:fill'), color)
    tcPr.append(shd)


def tabla(titulo, encabezado, filas, anchos=None, tam=9.5):
    contador['tab'] += 1
    cap = p(f'Tabla {contador["tab"]}. {titulo}', negrita=True, alinear='i', tam=10, color=MORADO)
    cap.paragraph_format.keep_with_next = True
    t = doc.add_table(rows=1, cols=len(encabezado))
    t.style = 'Table Grid'
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    for i, h in enumerate(encabezado):
        c = t.rows[0].cells[i]
        c.text = ''
        r = c.paragraphs[0].add_run(h)
        r.bold = True
        r.font.size = Pt(tam)
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        sombrear(c, '3B1D8F')
    for k, fila in enumerate(filas):
        celdas = t.add_row().cells
        for i, v in enumerate(fila):
            celdas[i].text = ''
            partes = str(v).split('**')
            for j, txt in enumerate(partes):
                r = celdas[i].paragraphs[0].add_run(txt)
                r.font.size = Pt(tam)
                r.bold = j % 2 == 1
            if k % 2 == 1:
                sombrear(celdas[i], 'F3F0FA')
    if anchos:
        for fila in t.rows:
            for i, a in enumerate(anchos):
                fila.cells[i].width = Cm(a)
    doc.add_paragraph().paragraph_format.space_after = Pt(2)
    return t


def figura(ruta, titulo, ancho=ANCHO):
    ruta = pathlib.Path(ruta)
    if not ruta.exists():
        p(f'[Falta la imagen {ruta.name}]', cursiva=True)
        return
    contador['fig'] += 1
    par = doc.add_paragraph()
    par.alignment = WD_ALIGN_PARAGRAPH.CENTER
    par.paragraph_format.keep_with_next = True
    par.add_run().add_picture(str(ruta), width=ancho)
    c = p(f'Figura {contador["fig"]}. {titulo}', alinear='c', tam=9.5, cursiva=True)
    c.paragraph_format.space_after = Pt(10)


def codigo(texto, tam=8.5):
    t = doc.add_table(rows=1, cols=1)
    t.style = 'Table Grid'
    c = t.rows[0].cells[0]
    sombrear(c, 'F6F6F8')
    c.text = ''
    for i, linea in enumerate(texto.strip('\n').split('\n')):
        par = c.paragraphs[0] if i == 0 else c.add_paragraph()
        par.paragraph_format.space_after = Pt(0)
        par.paragraph_format.line_spacing = 1.0
        r = par.add_run(linea)
        r.font.name = 'Consolas'
        r.font.size = Pt(tam)
    doc.add_paragraph().paragraph_format.space_after = Pt(2)


def salto():
    doc.add_paragraph().add_run().add_break(WD_BREAK.PAGE)


def leer(rel):
    return (RAIZ / rel).read_text(encoding='utf-8')


# ============================== PORTADA ==============================
for _ in range(2):
    doc.add_paragraph()
p('UNIVERSIDAD CONTINENTAL', negrita=True, alinear='c', tam=18, color=MORADO)
doc.add_paragraph()
p('ASIGNATURA: PRUEBAS Y CALIDAD DE SOFTWARE', negrita=True, alinear='c', tam=13)
doc.add_paragraph()
p('INFORME TÉCNICO INDIVIDUAL', negrita=True, alinear='c', tam=20, color=MORADO)
p('Arquitectura de software, integración tecnológica, automatización de pruebas con Cypress, '
  'análisis de calidad con SonarQube y reportes de impacto', alinear='c', tam=12, cursiva=True)
doc.add_paragraph()
p('PROYECTO', negrita=True, alinear='c', tam=11)
p('Sistema web “RecuperaYa” para la gestión de objetos perdidos y encontrados en el campus universitario',
  alinear='c', tam=13, negrita=True)
doc.add_paragraph()
t = doc.add_table(rows=4, cols=2)
t.alignment = WD_TABLE_ALIGNMENT.CENTER
for i, (k, v) in enumerate([('Apellidos y nombres', 'PALOMINO TAYPE, Nelsy'),
                            ('Docente', 'Dr. ARANA CAPARACHIN, Maglioni'),
                            ('Modalidad', 'Individual'),
                            ('Fecha', 'Noviembre de 2026')]):
    t.cell(i, 0).text = k
    t.cell(i, 0).paragraphs[0].runs[0].bold = True
    t.cell(i, 1).text = v
    t.cell(i, 0).width, t.cell(i, 1).width = Cm(5), Cm(9)
for _ in range(4):
    doc.add_paragraph()
p('Huancayo – Perú', alinear='c', tam=12, negrita=True)
p('2026', alinear='c', tam=12)
salto()

# ============================== ÍNDICE ==============================
doc.add_heading('Contenido', level=1)
for linea in ['1. Enunciado del caso práctico',
              '2. Solución del caso (según la guía práctica)',
              '    2.1 Actividad 1 – Arquitectura de software',
              '    2.2 Actividad 2 – Arquitectura tecnológica',
              '    2.3 Actividad 3 – Automatización de pruebas con Cypress',
              '    2.4 Actividad 4 – Análisis de calidad con SonarQube',
              '    2.5 Actividad 5 – Módulo de reportes e impacto funcional',
              '3. Evidencia del proceso de pruebas y calidad de código',
              '4. Conclusiones',
              'Anexo A. Casos prácticos de caja blanca y caja negra',
              'Anexo B. Código fuente de configuración y pruebas']:
    p(linea, alinear='i')
salto()

# ============================== 1. ENUNCIADO ==============================
doc.add_heading('1. Enunciado del caso práctico', level=1)
doc.add_heading('1.1 Contexto y problema', level=2)
p('En el campus de la Universidad Continental (sede Huancayo) los objetos perdidos y encontrados se gestionan de forma '
  '**manual**: la oficina de Bienestar Universitario anota cada objeto en un cuaderno, los estudiantes deben acudir varias '
  'veces a preguntar si su objeto llegó, los avisos se dispersan en grupos de redes sociales y la entrega no cuenta con una '
  'verificación segura de la identidad del dueño. Como consecuencia, muchos objetos nunca regresan a su propietario, se '
  'pierde tiempo de estudiantes y personal, y no existe información para saber qué se pierde, dónde y con qué frecuencia.')
doc.add_heading('1.2 Caso práctico planteado', level=2)
p('Sobre el sistema web **RecuperaYa**, desarrollado durante la asignatura, se solicita (Guía práctica individual de '
  'Arquitectura):')
viñetas(['Documentar la **arquitectura de software** del proyecto y su diagrama, identificando capas, módulos, comunicación, '
         'base de datos e integraciones con APIs.',
         'Documentar la **arquitectura tecnológica**: tecnologías, herramientas, infraestructura, entorno de trabajo y despliegue.',
         'Integrar **Cypress** e implementar como mínimo cinco escenarios de pruebas automatizadas sobre la aplicación real.',
         'Integrar **SonarQube**, ejecutar el análisis estático del código e interpretar sus métricas.',
         'Implementar un **módulo de reportes** con al menos tres indicadores, gráficos y filtros calculados a partir de los '
         'registros del sistema, y evaluar el **impacto funcional** del software.'])
doc.add_heading('1.3 Descripción general del sistema', level=2)
p('RecuperaYa es una aplicación web responsiva (funciona en computadora y celular sin instalar nada). El encargado de custodia '
  'registra cada objeto encontrado con foto y dos preguntas de verificación; el estudiante busca en el catálogo o reporta su '
  'pérdida; el sistema cruza automáticamente objetos y reportes, avisa al posible dueño, este reclama el objeto respondiendo '
  'las preguntas y lo recoge presentando su carné. El administrador gestiona usuarios, categorías y lugares.')
tabla('Usuarios y responsabilidades', ['Rol', 'Responsabilidades en el sistema'], [
    ['Estudiante / docente / personal', 'Registrarse con correo institucional, buscar en el catálogo, reportar pérdidas, reclamar objetos, recibir avisos.'],
    ['Encargado de custodia (Bienestar)', 'Registrar, editar y eliminar objetos; registrar entregas verificando el carné; consultar reportes.'],
    ['Administrador', 'Gestionar usuarios y roles, categorías y lugares; exportar el dataset para machine learning.']],
    anchos=[5, 11])
p('**Objetivo del software:** reducir el tiempo de recuperación de los objetos perdidos, aumentar la tasa de devolución a sus '
  'dueños y entregar con verificación segura de identidad, generando información para la toma de decisiones.')
salto()

# ============================== 2. SOLUCIÓN ==============================
doc.add_heading('2. Solución del caso (según la guía práctica)', level=1)

# ---------- 2.1 Arquitectura de software ----------
doc.add_heading('2.1 Actividad 1 – Documentación de la arquitectura de software', level=2)
doc.add_heading('1.1 Identificación de la arquitectura', level=3)
p('**Tipo de arquitectura:** aplicación web **monolítica cliente-servidor** organizada en **tres capas** (presentación, '
  'lógica de negocio y datos) con el patrón **MVC** del framework Laravel 12. Todo el backend se despliega como una sola '
  'aplicación PHP; el cliente es el navegador web, que se comunica con el servidor mediante HTTP.')
p('**Justificación:** el sistema atiende a una sola institución con un volumen moderado de usuarios, por lo que una arquitectura '
  'monolítica en capas es más simple de desarrollar, probar y desplegar en XAMPP o Docker que una basada en microservicios, '
  'manteniendo la separación de responsabilidades gracias a MVC y a una capa de servicios.')
tabla('Capas y componentes principales', ['Capa', 'Componentes (carpetas reales del proyecto)', 'Responsabilidad'], [
    ['Presentación', 'resources/views (Blade), public/css/app.css', 'Interfaz de usuario responsiva, formularios y mensajes.'],
    ['Aplicación – entrada', 'routes/web.php, Middleware (auth, guest, can:*), app/Http/Requests', 'Enrutar peticiones, controlar el acceso por rol y validar los datos.'],
    ['Aplicación – control', 'app/Http/Controllers (11 controladores + Admin)', 'Recibir la petición, coordinar servicios y modelos y devolver la vista.'],
    ['Aplicación – negocio', 'app/Services: MotorCoincidencias, ReclamoService, EstadisticaService, Notificador, ImagenService, Texto', 'Reglas de negocio: coincidencias, verificación de reclamos, indicadores, avisos.'],
    ['Datos – acceso', 'app/Models (10 modelos Eloquent)', 'Mapeo objeto-relacional y relaciones entre tablas.'],
    ['Datos – persistencia', 'MySQL/MariaDB (database/migrations), storage/app/public', 'Almacenar registros y fotografías.']],
    anchos=[3.2, 6.6, 6.2])
tabla('Módulos funcionales y sus responsabilidades', ['Módulo', 'Responsabilidad', 'Componentes', 'RF'], [
    ['M1 Acceso y usuarios', 'Registro con correo institucional, inicio/cierre de sesión, bloqueo tras 5 intentos, roles.', 'AuthController, Admin\\UsuarioController, Gates', 'RF-01, 02, 13'],
    ['M2 Registro y custodia', 'CRUD de objetos con foto y preguntas de verificación; control de no reclamados (90 días).', 'ObjetoController, ImagenService', 'RF-03, 04'],
    ['M3 Búsqueda y pérdidas', 'Catálogo con filtros y reportes de pérdida del usuario.', 'CatalogoController, ReportePerdidaController', 'RF-05, 06'],
    ['M4 Coincidencias y avisos', 'Cruce automático objeto–reporte con puntaje 0-100 y notificaciones.', 'MotorCoincidencias, Notificador, NotificacionController', 'RF-07, 08'],
    ['M5 Reclamo y entrega', 'Validación de respuestas (máx. 2 intentos), verificación del carné y entrega.', 'ReclamoController, EntregaController, ReclamoService', 'RF-09, 10, 11'],
    ['M6 Reportes y análisis', 'Indicadores, gráficos, filtros, PDF, CSV y dataset para ML.', 'EstadisticaController, EstadisticaService', 'RF-12']],
    anchos=[3.2, 6.3, 4.6, 1.9])
p('**Comunicación entre componentes:** el navegador envía peticiones HTTP (GET, POST, PUT/PATCH, DELETE) con token CSRF y '
  'cookie de sesión → las **rutas** dirigen la petición → el **middleware** verifica la sesión y el permiso del rol → el '
  '**Form Request** valida los datos → el **controlador** invoca a los **servicios** y **modelos** → Eloquent genera las '
  'consultas SQL → el controlador devuelve una **vista Blade** renderizada como HTML. Los mensajes de éxito o error viajan '
  'en la sesión (flash).')
p('**Gestión de la base de datos:** el esquema se crea con **migraciones** de Laravel y los datos iniciales con **seeders**. '
  'El acceso se hace con el ORM **Eloquent** (relaciones belongsTo/hasMany/hasOne), consultas agregadas con el Query Builder '
  'y transacciones (DB::transaction) en la entrega. Las respuestas de verificación y contraseñas se guardan **cifradas** '
  '(bcrypt). Tablas: users, categorias, lugares, objetos, reportes_perdida, coincidencias, reclamos, entregas, notificaciones, '
  'auditorias, sessions, cache y jobs.')
doc.add_heading('1.2 Diagrama de arquitectura de software', level=3)
figura(DIA / 'arquitectura_software.png', 'Diagrama de arquitectura de software de RecuperaYa (elaboración propia).')
p('**Integración con servicios externos (APIs):**')
tabla('APIs y servicios externos', ['API o servicio', 'Funcionalidad', 'Información intercambiada', 'Justificación'], [
    ['Servidor de correo SMTP (Laravel Mail)', 'Enviar avisos de coincidencias, reclamos aprobados/rechazados.', 'Destinatario, asunto, mensaje y enlace.', 'Avisar al dueño aunque no ingrese al sistema; con 3 reintentos ante fallos.'],
    ['Google Fonts (CDN)', 'Cargar la tipografía Poppins.', 'Hoja de estilos (solo lectura).', 'Identidad visual uniforme sin alojar fuentes.'],
    ['Google Colab (exportación CSV)', 'Entrenar el modelo de machine learning.', 'Dataset anónimo: categoría, lugar, franja, foto, coincidencias, recuperado_30d.', 'Predecir qué objetos tienen menos probabilidad de recuperarse.']],
    anchos=[3.6, 4, 4.2, 4.2])
p('El proyecto **no requiere APIs de pago ni de geolocalización**: el servicio es gratuito para la comunidad universitaria y '
  'los lugares del campus son una lista configurable (pabellones, laboratorios, biblioteca), más precisa que unas coordenadas '
  'GPS dentro de un edificio. **Evidencia de funcionamiento:** en desarrollo el correo usa el driver *log* (los mensajes '
  'quedan en storage/logs/laravel.log) y en las pruebas el driver *array*; el envío y la bandeja de avisos se verifican en '
  'las pruebas de integración (CP-31, CB-04).')

# ---------- 2.2 Arquitectura tecnológica ----------
doc.add_heading('2.2 Actividad 2 – Documentación de la arquitectura tecnológica', level=2)
doc.add_heading('2.1 Identificación de las tecnologías', level=3)
tabla('Tecnologías utilizadas', ['Componente', 'Tecnología utilizada', 'Propósito'], [
    ['Frontend', 'HTML5 + plantillas Blade, CSS3 propio (responsivo), JavaScript', 'Construcción de la interfaz de usuario.'],
    ['Backend', 'PHP 8.2.12', 'Implementación de la lógica de negocio.'],
    ['Base de datos', 'MariaDB 10.4 (MySQL) con phpMyAdmin; base MySQL aparte para Cypress; SQLite en memoria para PHPUnit', 'Almacenamiento de información.'],
    ['Framework', 'Laravel 12 (MVC, Eloquent, Blade, Gates, Form Requests)', 'Estructuración del desarrollo.'],
    ['Entorno de desarrollo', 'Visual Studio Code 1.144, Composer, Node.js 24 / npm', 'Edición y desarrollo del código.'],
    ['Control de versiones', 'Git 2.55 con flujo GitFlow (main, develop, feature/*, fix/*)', 'Gestión de cambios del proyecto.'],
    ['Servidor o infraestructura', 'XAMPP (Apache 2.4.58) y Docker Compose (app, MySQL 8, phpMyAdmin)', 'Ejecución y alojamiento del sistema.'],
    ['Automatización de pruebas', 'Cypress 13.17 (E2E), PHPUnit 11 (unitarias/integración), Playwright', 'Ejecución de pruebas automatizadas.'],
    ['Análisis de calidad', 'SonarQube Community Build 26.11 + SonarScanner CLI 8.4', 'Evaluación estática del código fuente.']],
    anchos=[3.8, 7, 5.2])
p('**Justificación:** Laravel ofrece seguridad integrada (CSRF, hash de contraseñas, protección contra asignación masiva e '
  'inyección SQL mediante consultas parametrizadas), lo que reduce defectos; XAMPP es el entorno trabajado en clase y Docker '
  'permite desplegar el mismo sistema en cualquier servidor; Cypress y SonarQube son las herramientas solicitadas por la guía.')
doc.add_heading('2.2 Diagrama de arquitectura tecnológica', level=3)
figura(DIA / 'arquitectura_tecnologica.png', 'Diagrama de arquitectura tecnológica de RecuperaYa (elaboración propia).')
doc.add_heading('2.3 Entorno de trabajo y despliegue', level=3)
tabla('Entorno de trabajo y despliegue', ['Aspecto', 'Descripción'], [
    ['Entorno de desarrollo', 'Laptop con Windows 11, Visual Studio Code y terminal integrada; proyecto en C:\\xampp\\htdocs\\recuperaya_laravel.'],
    ['Administración del código', 'Git con ramas GitFlow. Esta práctica se desarrolló en la rama feature/cypress-sonarqube-reportes.'],
    ['Ejecución de la aplicación', 'composer install → npm install → copiar .env y php artisan key:generate → php artisan migrate --seed → php artisan serve (http://127.0.0.1:8000).'],
    ['Infraestructura', 'Local en XAMPP (Apache + MariaDB). Alternativa en contenedores: docker compose up -d --build (app :8080, phpMyAdmin :8081).'],
    ['Publicar nuevas versiones', 'Se trabaja en una rama feature/*, se ejecutan PHPUnit, Cypress y SonarQube; si pasan, se fusiona en develop y luego en main; en el servidor: git pull, composer install --no-dev, php artisan migrate --force.']],
    anchos=[4, 12])
figura(CAP / 'vscode_cypress.png', 'Entorno de desarrollo: proyecto RecuperaYa abierto en Visual Studio Code (rama feature/cypress-sonarqube-reportes, carpeta cypress/e2e).')
figura(CAP / 'app_objetos.png', 'Software en ejecución: módulo de objetos del encargado de custodia (http://127.0.0.1:8000/objetos).', Cm(14))

# ---------- 2.3 Cypress ----------
doc.add_heading('2.3 Actividad 3 – Integración de Cypress para la automatización de pruebas', level=2)
doc.add_heading('3.1 Configuración de Cypress', level=3)
viñetas(['**Instalación:** npm install --save-dev cypress@13 (queda registrado en package.json).',
         '**Entorno de pruebas:** archivo .env.cypress que apunta a una base MySQL separada (**recuperaya_cypress**) para no '
         'alterar los datos reales de recuperaya_laravel. Se prepara con: APP_ENV=cypress php artisan migrate:fresh --seed y '
         'se carga un historial de prueba con HistorialPruebasSeeder.',
         '**Dirección de la aplicación evaluada:** baseUrl = http://127.0.0.1:8000 (servidor: APP_ENV=cypress php artisan serve).',
         '**Organización:** cypress/e2e (5 archivos .cy.js por funcionalidad), cypress/support/commands.js (comandos '
         'cy.login y cy.registrarObjeto), cypress/fixtures (usuarios del seeder e imagen de prueba), resultados en '
         'cypress/reports (JUnit XML), capturas en cypress/screenshots y videos en cypress/videos.',
         '**Ejecución:** npx cypress run (modo consola) o npx cypress open (modo interactivo).'])
codigo(leer('cypress.config.js'))

# Resultados JUnit
resultados = {}
for f in glob.glob(str(RAIZ / 'cypress/reports/*.xml')):
    for tc in ET.parse(f).getroot().iter('testcase'):
        nombre = tc.get('name')
        cid = nombre[nombre.index('CY-'):nombre.index('CY-') + 5]
        resultados[cid] = (float(tc.get('time')), tc.find('failure') is None)
doc.add_heading('3.2 y 3.3 Diseño e implementación de escenarios automatizados', level=3)
p('Se implementaron **13 escenarios** (la guía exige como mínimo cinco), que cubren todas las categorías sugeridas: acceso y '
  'autenticación, registro de información, edición, eliminación, validación de formularios, navegación entre módulos y '
  'procesos principales del negocio. Todos se ejecutan sobre la aplicación real en funcionamiento.')
ESC = [
    ('CY-01', 'Autenticación', 'Verificar el inicio de sesión con credenciales válidas.', 'encargado@continental.edu.pe / contraseña del seeder', 'Visita /login, escribe correo y contraseña, pulsa Ingresar.', 'Redirige a / y el menú muestra “Registrar objeto” y “Reportes”.'),
    ('CY-02', 'Autenticación', 'Verificar el rechazo de credenciales incorrectas.', 'admin@continental.edu.pe / ClaveEquivocada1', 'Envía el login con contraseña errónea.', 'Permanece en /login con el mensaje “Correo o contraseña incorrectos”.'),
    ('CY-03', 'Seguridad / middleware', 'Verificar que una ruta protegida exige sesión.', 'Sin sesión', 'Borra cookies y visita /objetos.', 'Redirige a /login.'),
    ('CY-04', 'Registro de usuarios', 'Validar que solo se acepten correos institucionales.', 'externoXXXX@gmail.com', 'Llena el registro y envía.', 'Permanece en /registro y marca error en el correo.'),
    ('CY-05', 'Registro de usuarios', 'Registrar un estudiante válido.', 'código@continental.edu.pe, Clave2026*', 'Llena todos los campos, acepta la privacidad y envía.', 'Ingresa al inicio con “Tu cuenta fue creada”.'),
    ('CY-06', 'Objetos (registro)', 'Registrar un objeto encontrado con foto.', 'Mochila negra…, objeto.png, 2 preguntas', 'cy.registrarObjeto(): llena el formulario y adjunta la foto.', 'Mensaje “registrado y publicado” con código OBJ-0000.'),
    ('CY-07', 'Objetos (validación)', 'Validar la descripción mínima (10 caracteres).', 'Descripción “Corta”', 'Llena el formulario con descripción de 5 letras.', 'Permanece en /objetos/crear con error en la descripción.'),
    ('CY-08', 'Objetos (edición)', 'Actualizar el color de un objeto.', 'Color Negro → Rojo', 'Filtra el objeto, pulsa Editar, cambia el color y guarda.', 'Mensaje “actualizado” y la tabla muestra Rojo.'),
    ('CY-09', 'Objetos (eliminación)', 'Eliminar un objeto confirmando el diálogo.', 'Objeto creado en CY-06', 'Pulsa Eliminar y acepta window.confirm.', 'Mensaje “eliminado” y el objeto ya no aparece.'),
    ('CY-10', 'Navegación', 'Recorrer los módulos del encargado.', 'Usuario encargado', 'Clic en Catálogo, Objetos, Entregas, Reportes y Avisos.', 'Cada URL carga y el enlace queda activo.'),
    ('CY-11', 'Control de acceso por rol', 'Impedir que un estudiante vea los reportes.', '72345678@continental.edu.pe', 'Solicita /reportes-estadisticos.', 'Respuesta HTTP 403; el menú no muestra opciones de gestión.'),
    ('CY-12', 'Proceso de negocio', 'Registrar un reporte de pérdida y buscar coincidencias.', 'Categoría 1, color Negro, descripción', 'Llena /reportes/crear y envía.', 'Redirige a /reportes con “Reporte registrado”.'),
    ('CY-13', 'Módulo de reportes', 'Verificar indicadores y filtros por estado.', 'Filtro estado = Devuelto', 'Lee el total, aplica el filtro y compara indicadores.', 'Total > 0; con el filtro “en custodia” = 0 y total filtrado ≤ total.'),
]
for cid, func, obj, datos, proc, esp in ESC:
    tiempo, ok = resultados.get(cid, (0, False))
    tabla(f'Escenario {cid}', ['Campo', 'Descripción'], [
        ['Identificador', cid], ['Funcionalidad', func], ['Objetivo', obj], ['Datos de prueba', datos],
        ['Procedimiento', proc], ['Resultado esperado', esp],
        ['Resultado obtenido', (esp if ok else 'No coincide con lo esperado') + f' (tiempo: {tiempo:.1f} s)'],
        ['Estado', '**APROBADO**' if ok else '**FALLIDO**']], anchos=[4, 12], tam=9)
doc.add_heading('3.4 Ejecución y presentación de resultados', level=3)
total = len(resultados)
aprob = sum(1 for _, ok in resultados.values() if ok)
tabla('Resumen de la ejecución final de Cypress', ['Indicador', 'Resultado'], [
    ['Total de pruebas ejecutadas', str(total)], ['Pruebas aprobadas', str(aprob)], ['Pruebas fallidas', str(total - aprob)],
    ['Porcentaje de aprobación', f'{aprob * 100 / total:.0f} %'], ['Duración total', '1 min 19 s'],
    ['Navegador / base de datos', 'Electron 118 (headless) / MySQL recuperaya_cypress']], anchos=[8, 8])
figura(CAP / 'terminal_cypress.png', 'Ejecución de npx cypress run en la terminal de VS Code: 13 de 13 pruebas aprobadas.')
p('**Errores identificados durante el proceso:**')
tabla('Errores encontrados y su tratamiento', ['N°', 'Ejecución', 'Error observado', 'Causa', 'Solución'], [
    ['1', '1.ª ejecución (5 fallidas de 13)', 'CY-06 a CY-09 y CY-12: “404 Not Found” en /objetos/create y /reportes/create.', 'Defecto del **script de prueba**: el sistema usa rutas con verbos en español (/crear, /editar) definidas con Route::resourceVerbs.', 'Se corrigieron las URL en las pruebas; la aplicación funcionaba bien.'],
    ['2', 'Pruebas PHPUnit del módulo de reportes', 'Los objetos encontrados en la fecha “hasta” no se contaban en los indicadores (total = 0).', 'Defecto **real de la aplicación**: whereBetween comparaba “2026-11-09 00:00:00” con “2026-11-09” como texto en SQLite.', 'Se cambió a whereDate(>=, <=) en EstadisticaService; prueba CP-46 aprobada.']],
    anchos=[0.8, 2.8, 4.2, 4.6, 3.6], tam=8.5)
p('**Funcionalidades con problemas:** tras las correcciones, ninguna funcionalidad evaluada presenta fallas. El defecto 2 '
  'afectaba al módulo de reportes cuando se usa SQLite; en MySQL no se manifestaba porque la columna es de tipo DATE, pero la '
  'corrección hace el cálculo independiente del motor de base de datos.')
p('**Evidencias de la ejecución:**')
for archivo, titulo in [('01_autenticacion.cy.js/CY-02_login_incorrecto.png', 'CY-02: mensaje de credenciales incorrectas.'),
                        ('02_registro_usuario.cy.js/CY-04_registro_correo_invalido.png', 'CY-04: rechazo de un correo no institucional.'),
                        ('03_objetos_crud.cy.js/CY-06_objeto_registrado.png', 'CY-06: objeto registrado con código OBJ.'),
                        ('03_objetos_crud.cy.js/CY-07_validacion_descripcion.png', 'CY-07: validación de la descripción.'),
                        ('03_objetos_crud.cy.js/CY-08_objeto_editado.png', 'CY-08: objeto editado (color Rojo).'),
                        ('03_objetos_crud.cy.js/CY-09_objeto_eliminado.png', 'CY-09: objeto eliminado.'),
                        ('04_navegacion_y_permisos.cy.js/CY-11_acceso_denegado.png', 'CY-11: acceso denegado (403) al estudiante.'),
                        ('05_proceso_negocio_y_reportes.cy.js/CY-12_reporte_perdida.png', 'CY-12: reporte de pérdida registrado.'),
                        ('03_objetos_crud.cy.js/CY-09b_ejecutor_cypress.png', 'ejecutor de Cypress con los escenarios CY-06 a CY-09 aprobados.'),
                        ('05_proceso_negocio_y_reportes.cy.js/CY-13c_ejecutor_cypress.png', 'ejecutor de Cypress con los escenarios CY-12 y CY-13 aprobados.')]:
    figura(SHOT / archivo, f'Captura generada por Cypress – {titulo}', Cm(13))

# ---------- 2.4 SonarQube ----------
doc.add_heading('2.4 Actividad 4 – Integración de SonarQube para el análisis de calidad', level=2)
doc.add_heading('4.1 Configuración de SonarQube', level=3)
viñetas(['**Servidor:** SonarQube Community Build 26.11 instalado localmente en C:\\sonar (con Java 21 Temurin) y ejecutado con '
         'StartSonar.bat; panel en http://localhost:9000.',
         '**Registro del proyecto:** proyecto local con clave **recuperaya** y token de análisis generado en My Account › Security.',
         '**Parámetros:** archivo sonar-project.properties en la raíz del proyecto (fuentes, pruebas, exclusiones de vendor y '
         'node_modules, codificación UTF-8).',
         '**Ejecución del análisis:** sonar-scanner -Dsonar.token=<token> desde la terminal de VS Code.',
         f'**Verificación:** el análisis terminó con “EXECUTION SUCCESS” y los resultados están en el panel ({SONAR["lineas"]} líneas de código analizadas).'])
codigo(leer('sonar-project.properties'))
figura(CAP / 'terminal_sonar_antes.png', 'Primer análisis: ejecución de SonarScanner desde la terminal de VS Code (EXECUTION SUCCESS).')
figura(CAP / 'sonar_proyectos_antes.png', 'Proyecto “RecuperaYa” registrado en SonarQube.')
figura(CAP / 'sonar_resumen_antes.png', 'Primer análisis: panel general y Quality Gate (Passed, 74 problemas de mantenibilidad).')
doc.add_heading('4.2 Análisis de los resultados (primer análisis)', level=3)
tabla('Métricas obtenidas en SonarQube – primer análisis', ['Métrica', 'Resultado', 'Interpretación'], [
    [m['metrica'], m['resultado'], m['interpretacion']] for m in SONAR['metricas']], anchos=[3.4, 3.4, 9.2])
for f, t in [('sonar_issues_antes.png', 'Primer análisis: listado de incidencias detectadas.'),
             ('sonar_seguridad_antes.png', 'Revisión de Security Hotspots (0 pendientes).'),
             ('sonar_duplicacion_antes.png', 'Líneas duplicadas por archivo.')]:
    figura(CAP / f, t)
doc.add_heading('4.3 Interpretación y propuestas de mejora', level=3)
for pregunta, respuesta in SONAR['preguntas']:
    p(f'**{pregunta}**')
    p(respuesta)

doc.add_heading('4.4 Mejoras implementadas y segundo análisis', level=3)
p('Las propuestas de mejora no quedaron solo como recomendación: se implementaron en el código y se volvió a ejecutar el '
  'análisis. Cada cambio se validó con las 67 pruebas PHPUnit y las 13 pruebas Cypress (todas aprobadas) y, en el caso del '
  'CSS, con una **comparación visual automática píxel a píxel** de 12 pantallas en escritorio (1366 px) y celular (375 px).')
tabla('Mejoras implementadas a partir de SonarQube', ['Regla', 'Problema', 'Corrección aplicada', 'Archivo'], [
    r for r in SONAR['mejoras']], anchos=[2.2, 4.2, 6.4, 3.2], tam=8.5)
tabla('Comparación de métricas: antes y después de las mejoras', ['Métrica', 'Primer análisis', 'Segundo análisis', 'Variación'],
      SONAR['comparacion'], anchos=[4.4, 3.6, 3.6, 4.4])
p('**Revisión (triage) de las observaciones restantes.** Como parte del proceso de calidad, cada observación que no se '
  'corrigió fue revisada y clasificada en SonarQube con un comentario que justifica la decisión:')
tabla('Observaciones revisadas y no corregidas', ['Regla', 'Ubicación', 'Clasificación', 'Justificación'],
      SONAR['triaje'], anchos=[2.2, 3.6, 2.6, 7.6], tam=8.5)
figura(CAP / 'terminal_sonar.png', 'Segundo análisis: ejecución de SonarScanner tras las mejoras (EXECUTION SUCCESS).')
figura(CAP / 'sonar_resumen.png', 'Segundo análisis: Quality Gate aprobado y observaciones de mantenibilidad reducidas.')
figura(CAP / 'sonar_issues.png', 'Segundo análisis: incidencias restantes con su clasificación.')
p('**Verificación visual de la refactorización del CSS:** se capturaron 12 pantallas (login, registro, privacidad, inicio, '
  'catálogo, objetos, registrar objeto, entregas, reportes, avisos, reportar pérdida y mis reclamos) en 2 tamaños, antes y '
  'después del cambio, y se compararon píxel a píxel con el script docs/informe/comparar_css.py. Resultado: **24 de 24 '
  'pantallas idénticas (0,000 % de píxeles distintos)**. Durante esta verificación se detectó además que el gráfico “por '
  'lugar” mostraba en orden variable los lugares con el mismo total; se corrigió agregando un orden secundario por nombre.')

# ---------- 2.5 Reportes ----------
doc.add_heading('2.5 Actividad 5 – Reportes funcionales e impacto del software', level=2)
doc.add_heading('5.1 Identificación de indicadores del negocio', level=3)
tabla('Indicadores del módulo de reportes', ['Indicador', 'Cálculo (a partir de la base de datos)', 'Relación con el problema'], [
    ['Objetos registrados', 'COUNT de objetos con fecha de hallazgo en el periodo y filtros.', 'Mide cuántos objetos quedan registrados en lugar de un cuaderno.'],
    ['Tasa de recuperación (%)', 'Objetos “Devuelto” × 100 / objetos registrados.', 'Indicador principal: cuántos objetos regresan a su dueño.'],
    ['Días promedio hasta la entrega', 'Promedio de (fecha de entrega − fecha de hallazgo) uniendo objetos y entregas.', 'Mide la rapidez de la devolución.'],
    ['Reportes de pérdida', 'COUNT de reportes de pérdida en el periodo.', 'Demanda atendida sin acudir a la oficina.'],
    ['Objetos devueltos / en custodia', 'COUNT por estado.', 'Carga de trabajo de la oficina de custodia.'],
    ['Reclamos recibidos / rechazados', 'COUNT de reclamos y de reclamos con estado Rechazado.', 'Seguridad: intentos de reclamo fallidos bloqueados.'],
    ['Objetos por mes, estado, categoría y lugar', 'GROUP BY mes, estado, categoría y lugar.', 'Detectar qué se pierde y dónde, para prevenir.']],
    anchos=[3.8, 6.4, 5.8])
doc.add_heading('5.2 Implementación del módulo de reportes', level=3)
p('El módulo se encuentra en el menú **Reportes** (ruta /reportes-estadisticos, solo encargado y administrador). Está formado '
  'por EstadisticaController, EstadisticaService y las vistas resources/views/estadisticas. En esta práctica se mejoró así:')
viñetas(['**Filtros de consulta:** se añadieron los filtros por **categoría** y **estado** a los de fecha “desde/hasta”, '
         'con validación (exists:categorias, in:estados); los filtros se aplican también al PDF y al CSV.',
         '**Indicadores principales:** 8 tarjetas (se añadieron devueltos, en custodia, reclamos recibidos y rechazados).',
         '**Reportes estadísticos:** gráfico de barras de objetos por mes (nuevo), por estado, por categoría y por lugar.',
         '**Exportación:** vista para imprimir/PDF, archivo Excel (CSV) y dataset para machine learning.',
         '**Información actualizada:** todos los valores se calculan con consultas a la base de datos en cada solicitud; no hay '
         'valores fijos en el código. Lo comprueban las pruebas CP-46 a CP-50 (PHPUnit) y CY-13 (Cypress).'])
figura(CAP / 'reportes_general.png', 'Módulo de reportes: filtros, 8 indicadores y gráfico mensual (periodo 01/01/2026 – hoy).', Cm(15))
figura(CAP / 'reportes_filtrado.png', 'Módulo de reportes filtrado por categoría Celular y estado Devuelto: los indicadores se recalculan.', Cm(15))
figura(CAP / 'reportes_imprimir.png', 'Reporte en formato imprimible / PDF generado desde el módulo.', Cm(15))
p('**Evidencia de que los datos provienen de registros del sistema:** el entorno de pruebas contiene los objetos creados por '
  'el seeder base, los creados por las pruebas Cypress y un historial de 60 objetos de prueba (HistorialPruebasSeeder) '
  'distribuidos entre enero y noviembre de 2026 con sus reclamos, entregas y reportes de pérdida. Al registrar o eliminar un '
  'objeto (CY-06, CY-09) los indicadores cambian, y al aplicar filtros (CY-13) se recalculan. Se aclara que estos son '
  '**datos de prueba** del ambiente de desarrollo, no registros de uso real en la oficina de Bienestar.')
doc.add_heading('5.3 Evaluación del impacto funcional', level=3)
r = SONAR['reporte']
for pregunta, respuesta in [
    ('¿Qué problema busca solucionar el software?', 'La gestión manual de objetos perdidos y encontrados, que provoca visitas repetidas a la oficina, avisos dispersos, entregas sin verificación de identidad y la ausencia de información para decidir.'),
    ('¿Qué información permite visualizar el módulo de reportes?', 'Cantidad de objetos registrados, devueltos y en custodia; tasa de recuperación; días promedio hasta la entrega; reportes de pérdida; reclamos recibidos y rechazados; y su distribución por mes, estado, categoría y lugar, con filtros por fechas, categoría y estado.'),
    ('¿Qué indicadores permiten evaluar los resultados del proceso?', f'Principalmente la **tasa de recuperación** (en el ambiente de prueba: {r["tasa"]} %), los **días promedio hasta la entrega** ({r["dias"]} días) y los **reclamos rechazados** ({r["rechazados"]}), que muestran que la verificación impide entregas a personas que no son las dueñas.'),
    ('¿Cómo facilita el software la toma de decisiones?', 'La oficina puede ver en qué lugares y categorías se concentran las pérdidas para colocar avisos preventivos o puntos de recepción, medir su carga mensual para asignar personal y revisar los objetos que siguen en custodia para aplicar la regla de 90 días.'),
    ('¿Qué actividades o procesos pueden optimizarse?', 'El registro de objetos (formulario con foto en lugar de cuaderno), la búsqueda (catálogo en línea en vez de ir a preguntar), el aviso al dueño (coincidencias y notificaciones automáticas), la entrega (verificación con preguntas y carné) y la elaboración de informes (exportación a PDF/CSV en segundos).'),
    ('¿Qué beneficios concretos se esperan?', 'Mayor número de objetos devueltos, menor tiempo de devolución, menos visitas a la oficina, entregas seguras y trazables (auditoría) y datos para prevenir pérdidas.')]:
    p(f'**{pregunta}**')
    p(respuesta)
tabla('Comparación: situación anterior y situación con el software', ['Aspecto', 'Antes (proceso manual)', 'Con RecuperaYa', 'Tipo'], [
    ['Registro del objeto', 'Anotación en cuaderno, sin foto.', 'Formulario con foto y código OBJ (meta ≤ 3 min).', 'Medido en pruebas'],
    ['Consulta del estudiante', 'Visitas presenciales repetidas.', 'Catálogo en línea y reporte de pérdida.', 'Medido en pruebas'],
    ['Aviso al dueño', 'Publicaciones dispersas en redes.', 'Coincidencias automáticas y notificación.', 'Medido en pruebas'],
    ['Verificación de identidad', 'Sin control formal.', '2 preguntas cifradas + carné; reclamos rechazados registrados.', 'Medido en pruebas'],
    ['Tasa de recuperación', 'No se medía.', f'{r["tasa"]} % en el ambiente de prueba.', 'Potencial (datos de prueba)'],
    ['Tiempo hasta la entrega', 'No se medía.', f'{r["dias"]} días en promedio en el ambiente de prueba.', 'Potencial (datos de prueba)']],
    anchos=[3.4, 4, 5.4, 3.2])
p('**Diferenciación de beneficios:** las funcionalidades (registro, búsqueda, avisos, verificación y reportes) se **midieron** '
  'mediante pruebas automatizadas. Los valores de tasa de recuperación y días promedio provienen de datos de prueba, por lo que '
  'representan **beneficios potenciales**; para demostrar una mejora real se deberá comparar con un periodo de uso efectivo '
  'del sistema en la oficina de Bienestar Universitario.')
salto()

# ============================== 3. EVIDENCIAS ==============================
doc.add_heading('3. Evidencia del proceso de pruebas y calidad de código', level=1)
p('El proceso de calidad aplicado combina tres niveles de prueba y un análisis estático, todos ejecutados desde Visual Studio Code:')
tabla('Resumen del proceso de pruebas y calidad', ['Nivel / herramienta', 'Qué evalúa', 'Cantidad', 'Resultado'], [
    ['PHPUnit – pruebas unitarias', 'Motor de coincidencias y funciones aisladas.', '7', '100 % aprobadas'],
    ['PHPUnit – integración (Feature)', 'Registro, acceso, objetos, reclamos, entregas, reportes.', '39', '100 % aprobadas'],
    ['PHPUnit – caja blanca (Anexo A)', '7 caminos independientes de ReclamoService::reclamar().', '7', '100 % aprobadas'],
    ['PHPUnit – caja negra (Anexo A)', 'Particiones y valores límite del formulario de objetos.', '14', '100 % aprobadas'],
    ['Cypress – E2E', 'Aplicación real en el navegador (13 escenarios).', '13', '100 % aprobadas'],
    ['SonarQube', 'Calidad estática del código fuente (2 análisis).', f'{SONAR["lineas"]} líneas', f'Quality Gate: {SONAR["quality_gate"]}; {SONAR["resumen_mejora"]}']],
    anchos=[4.4, 6.4, 2, 3.2])
p('**Total de pruebas automatizadas:** 67 en PHPUnit (144 aserciones) + 13 en Cypress = **80 pruebas aprobadas**.')
figura(CAP / 'terminal_phpunit.png', 'Ejecución de php artisan test: 67 pruebas aprobadas (144 aserciones).')
salto()

# ============================== 4. CONCLUSIONES ==============================
doc.add_heading('4. Conclusiones', level=1)
viñetas([
    'RecuperaYa tiene una arquitectura **monolítica cliente-servidor en tres capas con MVC** que separa con claridad la interfaz, '
    'la lógica de negocio (controladores y servicios) y los datos; esto facilitó agregar pruebas y nuevos filtros sin afectar otros módulos.',
    'La arquitectura tecnológica (Laravel 12, PHP 8.2, MariaDB, XAMPP/Docker, Git y VS Code) es adecuada para el alcance del proyecto '
    'y permite desplegarlo tanto localmente como en contenedores.',
    'Se integró **Cypress** con 13 escenarios sobre la aplicación real; la ejecución final obtuvo **100 % de aprobación**. Los fallos '
    'iniciales se debieron al script de prueba y permitieron documentar el uso de rutas en español.',
    f'El análisis con **SonarQube** obtuvo un Quality Gate **{SONAR["quality_gate"]}** sin bugs ni vulnerabilidades. Las mejoras '
    f'implementadas redujeron las observaciones de mantenibilidad ({SONAR["resumen_mejora"]}) sin cambiar la apariencia del sistema, '
    'comprobado con una comparación visual automática de 24 pantallas.',
    'Se recuperó la base de datos MySQL de XAMPP (logs de Aria dañados y tablas del sistema reparadas, con respaldo previo); las '
    'pruebas E2E se ejecutan sobre MySQL, la misma tecnología del sistema en producción.',
    'El módulo de reportes se completó con filtros por fecha, categoría y estado, 8 indicadores y gráficos calculados desde la base de '
    'datos; las pruebas detectaron y permitieron corregir un **defecto real** de filtrado de fechas.',
    'Las técnicas de **caja blanca** (complejidad ciclomática V(G) = 7) y **caja negra** (particiones y valores límite) complementan '
    'las pruebas E2E y aumentan la confianza en las reglas de negocio más críticas.'])
salto()

# ============================== ANEXO A ==============================
doc.add_heading('Anexo A. Casos prácticos de caja blanca y caja negra', level=1)
p('Siguiendo la guía “Test y pruebas de caja negra y caja blanca”, se resolvieron dos casos prácticos sobre el propio proyecto. '
  'Las **pruebas de caja blanca** examinan la estructura interna del código (caminos, decisiones); las **pruebas de caja negra** '
  'verifican la funcionalidad solo a partir de entradas y salidas, sin conocer el código.')
doc.add_heading('A.1 Caso de caja blanca: ReclamoService::reclamar()', level=2)
p('**Enunciado:** diseñar los casos de prueba que recorran todos los caminos independientes del método que decide si un '
  'reclamo se aprueba, se rechaza o se informa al usuario (RF-09, RF-10, RN-06: máximo 2 intentos).')
codigo('''public static function reclamar(int $userId, Objeto $objeto, string $r1, string $r2): array
{
    if ($objeto->estado !== Objeto::EN_CUSTODIA) {                       // P1
        return [... 'Este objeto ya no está disponible para reclamo.'];
    }
    $reclamo = Reclamo::where('user_id', $userId)->where('objeto_id', $objeto->id)->latest('id')->first();
    if ($reclamo && $reclamo->estado === Reclamo::RECHAZADO) {          // P2
        return [... 'Ya usaste tus 2 intentos para este objeto...'];
    }
    if ($reclamo && in_array($reclamo->estado, [APROBADO, ENTREGADO])) { // P3
        return [... 'Ya tienes un reclamo aprobado para este objeto.'];
    }
    $reclamo ??= Reclamo::create([...]);                                 // P4
    $reclamo->intentos = $reclamo->intentos + 1;
    if (self::verificarRespuestas($objeto, $r1, $r2)) {                 // P5
        ... aprobar, cambiar el objeto a Reclamado y notificar
    }
    if ($reclamo->intentos >= $max) {                                   // P6
        ... rechazar y notificar
    }
    return [... "Las respuestas no coinciden. Te queda $quedan intento."];
}''')
figura(DIA / 'grafo_caja_blanca.png', 'Grafo de flujo del método ReclamoService::reclamar().', Cm(15))
p('**Complejidad ciclomática:**')
viñetas(['V(G) = número de nodos predicado + 1 = 6 + 1 = **7**',
         'V(G) = aristas − nodos + 2 = 21 − 16 + 2 = **7**',
         'V(G) = número de regiones del grafo = **7**'])
p('Por lo tanto se requieren **7 casos de prueba**, uno por cada camino independiente:')
tabla('Caminos independientes y casos de prueba de caja blanca', ['Caso', 'Camino', 'Datos de entrada', 'Resultado esperado', 'Estado'], [
    ['CB-01', '1-2-3-fin', 'Objeto en estado Devuelto', '“ya no está disponible”', 'Aprobado'],
    ['CB-02', '1-2-4-5-fin', 'Reclamo previo Rechazado', '“Ya usaste tus 2 intentos”', 'Aprobado'],
    ['CB-03', '1-2-4-6-7-fin', 'Reclamo previo Aprobado', '“Ya tienes un reclamo aprobado”', 'Aprobado'],
    ['CB-04', '1-2-4-6-8-9-10-11-12-fin', 'Sin reclamo previo; respuestas “ MARCA ” y “Nada”', 'Aprobado; objeto pasa a Reclamado; intentos = 1', 'Aprobado'],
    ['CB-05', '1-2-4-6-8-9-10-11-13-15-fin', 'Sin reclamo previo; respuesta incorrecta', '“Te queda 1 intento”; reclamo Pendiente', 'Aprobado'],
    ['CB-06', '1-2-4-6-8-10-11-13-14-fin', 'Reclamo pendiente con 1 intento; respuestas incorrectas', 'Reclamo Rechazado; objeto sigue En custodia', 'Aprobado'],
    ['CB-07', '1-2-4-6-8-10-11-12-fin', 'Reclamo pendiente con 1 intento; respuestas correctas', 'Aprobado sin crear otro reclamo; intentos = 2', 'Aprobado']],
    anchos=[1.4, 4, 4, 4.6, 2], tam=8.5)
p('Los 7 casos están automatizados en **tests/Feature/CajaBlancaReclamoTest.php** y se ejecutaron con resultado 7/7 aprobados. '
  'El caso CB-04 confirma además que la comparación ignora mayúsculas y espacios (normalización de texto).')
doc.add_heading('A.2 Caso de caja negra: formulario “Registrar objeto encontrado”', level=2)
p('**Enunciado:** sin conocer el código, diseñar casos de prueba para el formulario de registro de objetos a partir de su '
  'especificación (RF-03): descripción de 10 a 500 caracteres; color de 3 a 40 letras; fecha de hallazgo no futura y con '
  'máximo un año de antigüedad; foto JPG o PNG de hasta 5 MB y mínimo 200 × 200 píxeles.')
tabla('Particiones de equivalencia', ['Campo', 'Clases válidas', 'Clases inválidas'], [
    ['Descripción', 'V1: 10 ≤ longitud ≤ 500', 'I1: longitud < 10 · I2: longitud > 500 · I3: vacía'],
    ['Color', 'V2: solo letras, espacios, comas, 3–40 caracteres', 'I4: contiene números · I5: menos de 3 letras'],
    ['Fecha de hallazgo', 'V3: hoy − 1 año ≤ fecha ≤ hoy', 'I6: fecha futura · I7: más de un año de antigüedad'],
    ['Foto', 'V4: JPG/PNG, ≤ 5 MB, ≥ 200 × 200 px', 'I8: < 200 px · I9: > 5 MB · I10: otro formato (PDF)']],
    anchos=[3, 6, 7])
tabla('Casos de prueba de caja negra (valores límite)', ['Caso', 'Clase', 'Entrada', 'Resultado esperado', 'Obtenido'], [
    ['CN-01', 'I1', 'Descripción de 9 caracteres', 'Rechazado', 'Rechazado ✔'],
    ['CN-02', 'V1', 'Descripción de 10 caracteres', 'Aceptado', 'Aceptado ✔'],
    ['CN-03', 'V1', 'Descripción de 500 caracteres', 'Aceptado', 'Aceptado ✔'],
    ['CN-04', 'I2', 'Descripción de 501 caracteres', 'Rechazado', 'Rechazado ✔'],
    ['CN-05', 'I4', 'Color “Negro 123”', 'Rechazado', 'Rechazado ✔'],
    ['CN-06', 'I5', 'Color “Az” (2 letras)', 'Rechazado', 'Rechazado ✔'],
    ['CN-07', 'V2', 'Color “Azul con rojo”', 'Aceptado', 'Aceptado ✔'],
    ['CN-08', 'V3', 'Fecha = hoy', 'Aceptado', 'Aceptado ✔'],
    ['CN-09', 'I6', 'Fecha = mañana', 'Rechazado', 'Rechazado ✔'],
    ['CN-10', 'I7', 'Fecha = hace 1 año y 1 día', 'Rechazado', 'Rechazado ✔'],
    ['CN-11', 'I8', 'Foto de 199 × 199 px', 'Rechazado', 'Rechazado ✔'],
    ['CN-12', 'V4', 'Foto de 200 × 200 px', 'Aceptado', 'Aceptado ✔'],
    ['CN-13', 'I9', 'Foto de 5121 KB', 'Rechazado', 'Rechazado ✔'],
    ['CN-14', 'I10', 'Archivo PDF', 'Rechazado', 'Rechazado ✔']],
    anchos=[1.6, 1.6, 5.6, 3.6, 3.6], tam=9)
p('Los 14 casos están automatizados en **tests/Feature/CajaNegraObjetoTest.php** (con DataProvider) y se ejecutaron con '
  'resultado 14/14 aprobados. La clase I1 también se verifica de extremo a extremo en el navegador con Cypress (CY-07).')
doc.add_heading('A.3 Otros tipos de prueba aplicados (según la guía)', level=2)
tabla('Tipos de prueba de la guía y su aplicación en RecuperaYa', ['Tipo de prueba', 'Aplicación en el proyecto'], [
    ['Test de seguridad', 'CY-03 y CY-11 (sesión y rol), bloqueo tras 5 intentos (CP-11), CSRF, contraseñas y respuestas cifradas, Security Hotspots de SonarQube.'],
    ['Test de usabilidad', 'Mensajes de error claros por campo, diseño responsivo, navegación verificada en CY-10.'],
    ['Test de performance', 'Tiempos de cada escenario Cypress (entre 0,5 s y 14 s, incluyendo carga de foto) registrados en el reporte JUnit.'],
    ['Test de volumen', 'Historial de 60 objetos con reclamos y entregas para verificar los cálculos del módulo de reportes.'],
    ['Test de instalación', 'Procedimiento documentado (composer install, migrate --seed, serve) y despliegue con Docker Compose.'],
    ['Test de documentación', 'README, INSTALAR.md, PROCEDIMIENTOS.md y comentarios en el código revisados con el análisis de SonarQube.']],
    anchos=[4, 12])

# ============================== ANEXO B ==============================
doc.add_heading('Anexo B. Código fuente de configuración y pruebas', level=1)
for rel, titulo in [('cypress/support/commands.js', 'B.1 Comandos personalizados de Cypress'),
                    ('cypress/e2e/01_autenticacion.cy.js', 'B.2 Escenarios de autenticación (CY-01 a CY-03)'),
                    ('cypress/e2e/03_objetos_crud.cy.js', 'B.3 Escenarios del CRUD de objetos (CY-06 a CY-09)'),
                    ('cypress/e2e/05_proceso_negocio_y_reportes.cy.js', 'B.4 Proceso de negocio y reportes (CY-12, CY-13)')]:
    doc.add_heading(titulo, level=2)
    p(f'Archivo: {rel}', cursiva=True, alinear='i', tam=9)
    codigo(leer(rel), 8)
p('El código completo (incluidos 02_registro_usuario.cy.js y 04_navegacion_y_permisos.cy.js, las pruebas PHPUnit y el módulo '
  'de reportes) se encuentra en la rama feature/cypress-sonarqube-reportes del repositorio del proyecto.', cursiva=True)

# Numeración de páginas en el pie
pie = doc.sections[0].footer.paragraphs[0]
pie.alignment = WD_ALIGN_PARAGRAPH.CENTER
run = pie.add_run()
for tipo, texto in [('begin', None), (None, 'PAGE'), ('end', None)]:
    if tipo:
        e = OxmlElement('w:fldChar')
        e.set(qn('w:fldCharType'), tipo)
    else:
        e = OxmlElement('w:instrText')
        e.set(qn('xml:space'), 'preserve')
        e.text = texto
    run._r.append(e)
doc.sections[0].different_first_page_header_footer = True

salida = DOCS / 'Informe_Individual_Arquitectura_RecuperaYa_PalominoTaype.docx'
doc.save(salida)
print('generado', salida)
