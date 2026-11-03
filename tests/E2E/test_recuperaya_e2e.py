"""Pruebas E2E de RecuperaYa (Playwright + pytest).

Cada prueba abre el navegador, actúa como un usuario real y verifica lo que se ve en pantalla.
Códigos E2E-01 … E2E-12 para el informe de pruebas.
"""
import re

from playwright.sync_api import expect

from conftest import capturar, iniciar_sesion, url


def test_e2e01_portada_de_acceso(pagina):
    pagina.goto(url("/login"))
    expect(pagina).to_have_title(re.compile("Iniciar sesión"))
    expect(pagina.get_by_text("Sede Huancayo")).to_be_visible()
    expect(pagina.locator("input[name=email]")).to_be_visible()
    capturar(pagina, "E2E-01_portada")


def test_e2e02_credenciales_incorrectas_muestran_error(pagina):
    pagina.goto(url("/login"))
    pagina.fill("input[name=email]", "no.existe@continental.edu.pe")
    pagina.fill("input[name=password]", "Clave1234")
    pagina.click("button[type=submit]")
    expect(pagina.get_by_text("Correo o contraseña incorrectos")).to_be_visible()
    capturar(pagina, "E2E-02_login_incorrecto")


def test_e2e03_registro_rechaza_correo_no_institucional(pagina):
    pagina.goto(url("/registro"))
    pagina.fill("input[name=name]", "Prueba Externa")
    pagina.fill("input[name=email]", "prueba@gmail.com")
    pagina.fill("input[name=password]", "Clave2026")
    pagina.fill("input[name=password_confirmation]", "Clave2026")
    pagina.check("input[name=acepta]")
    pagina.click("button[type=submit]")
    expect(pagina.locator(".error-campo", has_text="Debes usar tu correo institucional")).to_be_visible()
    expect(pagina).to_have_url(re.compile("/registro"))
    capturar(pagina, "E2E-03_registro_gmail_rechazado")


def test_e2e04_estudiante_ingresa_y_ve_su_menu(pagina):
    iniciar_sesion(pagina, "estudiante")
    menu = pagina.locator("nav.menu")
    expect(menu.get_by_role("link", name="Reportar pérdida")).to_be_attached()
    expect(menu.get_by_role("link", name="Mis reclamos")).to_be_attached()
    expect(menu.get_by_role("link", name="Registrar objeto")).to_have_count(0)
    capturar(pagina, "E2E-04_inicio_estudiante")


def test_e2e05_estudiante_no_puede_entrar_a_gestion(pagina):
    iniciar_sesion(pagina, "estudiante")
    respuesta = pagina.goto(url("/objetos"))
    assert respuesta.status == 403
    capturar(pagina, "E2E-05_acceso_denegado_403")


def test_e2e06_catalogo_busca_por_texto(pagina):
    iniciar_sesion(pagina, "estudiante")
    pagina.goto(url("/catalogo"))
    pagina.fill("input[name=q]", "calculadora")
    pagina.get_by_role("button", name="Buscar").click()
    expect(pagina.locator(".objeto").first).to_contain_text("Útiles / calculadora")
    capturar(pagina, "E2E-06_busqueda_catalogo")


def test_e2e07_lugares_de_la_sede_huancayo(pagina):
    iniciar_sesion(pagina, "estudiante")
    pagina.goto(url("/reportes/crear"))
    opciones = pagina.locator("select[name=lugar_id] option").all_inner_texts()
    assert any("Pabellón F – Hub de Información" in o for o in opciones)
    assert any("Servicios higiénicos – Pabellón L" in o for o in opciones)
    assert len(opciones) >= 47
    capturar(pagina, "E2E-07_formulario_reporte")


def test_e2e08_detalle_con_url_amigable(pagina):
    iniciar_sesion(pagina, "encargado")
    pagina.goto(url("/catalogo"))
    pagina.locator(".objeto").first.click()
    expect(pagina).to_have_url(re.compile(r"/catalogo/[a-z0-9-]+-obj-\d{4}$"))
    expect(pagina.get_by_text("Código")).to_be_visible()
    capturar(pagina, "E2E-08_detalle_objeto")


def test_e2e09_encargado_ve_indicadores(pagina):
    iniciar_sesion(pagina, "encargado")
    expect(pagina.get_by_text("Objetos en custodia")).to_be_visible()
    expect(pagina.get_by_role("link", name="Registrar objeto encontrado")).to_be_visible()
    capturar(pagina, "E2E-09_inicio_encargado")


def test_e2e10_administrador_gestiona_usuarios_y_reportes(pagina):
    iniciar_sesion(pagina, "admin")
    pagina.goto(url("/admin/usuarios"))
    expect(pagina.get_by_text("Usuarios y roles")).to_be_visible()
    capturar(pagina, "E2E-10a_usuarios")
    pagina.goto(url("/reportes-estadisticos"))
    expect(pagina.get_by_text("Objetos registrados", exact=True)).to_be_visible()
    capturar(pagina, "E2E-10b_reportes")


def test_e2e11_menu_responsivo_en_celular(navegador):
    contexto = navegador.new_context(viewport={"width": 375, "height": 812}, is_mobile=True)
    pagina = contexto.new_page()
    iniciar_sesion(pagina, "estudiante")
    menu = pagina.locator("nav.menu")
    expect(menu).to_be_hidden()
    pagina.locator("label.boton-menu").click()
    expect(menu).to_be_visible()
    capturar(pagina, "E2E-11_menu_movil")
    contexto.close()


def test_e2e12_cerrar_sesion(pagina):
    iniciar_sesion(pagina, "estudiante")
    pagina.get_by_role("button", name="Salir").click()
    expect(pagina).to_have_url(re.compile("/login"))
    respuesta = pagina.goto(url("/catalogo"))
    expect(pagina).to_have_url(re.compile("/login"))
    assert respuesta.ok
