"""PRUEBA DE SISTEMA PS-01 (examen final – consigna).

Recorre el sistema completo desde el navegador (Microsoft Edge), como lo haría una persona,
con los tres roles y sobre la aplicación real (XAMPP + MySQL):

  1. El encargado registra un objeto encontrado con foto y 2 preguntas.
  2. La estudiante reporta su pérdida y el sistema le avisa la coincidencia.
  3. La estudiante busca el objeto en el catálogo y lo reclama respondiendo las preguntas.
  4. El encargado verifica el carné y registra la entrega.
  5. La estudiante ve su reclamo como «Entregado».

ATENCIÓN: crea datos reales. Antes, respalde la base con deploy\respaldo_bd.bat.
    python -m pytest tests/Sistema -v -s
"""
import datetime, os, re
from pathlib import Path
from playwright.sync_api import sync_playwright, expect

BASE = os.environ.get("RY_BASE_URL", "http://localhost/recuperaya_laravel/public").rstrip("/")
CAP = Path(__file__).parent / "capturas"; CAP.mkdir(exist_ok=True)
FOTO = Path(__file__).parents[2] / "storage/app/public/objetos/demo_mochila.jpg"
USU = {"encargado": ("encargado@continental.edu.pe", "Encargado2026*"),
       "estudiante": ("72345678@continental.edu.pe", "Usuario2026*")}
MARCA = datetime.datetime.now().strftime("PS01-%H%M%S")   # texto único de esta ejecución


def sesion(nav, rol):
    ctx = nav.new_context(viewport={"width": 1366, "height": 768}, locale="es-PE")
    p = ctx.new_page(); p.goto(BASE + "/login")
    p.fill("input[name=email]", USU[rol][0]); p.fill("input[name=password]", USU[rol][1])
    p.click("button[type=submit]"); p.wait_for_load_state("networkidle")
    return p


def opcion(p, selector, texto):
    """Elige en una lista la primera opción que contiene el texto y devuelve su valor."""
    valor = p.eval_on_selector(selector, "(s,t)=>[...s.options].find(o=>o.text.includes(t)).value", texto)
    p.select_option(selector, valor); return valor


def foto(p, nombre):
    p.screenshot(path=str(CAP / f"{nombre}.png"), full_page=True); print(f"   captura: {nombre}.png")


def test_ps01_flujo_completo_de_recuperacion():
    with sync_playwright() as pw:
        nav = pw.chromium.launch(channel="msedge", headless=os.environ.get("RY_VER") != "1")

        print("\nPaso 1: el encargado registra el objeto encontrado")
        enc = sesion(nav, "encargado"); enc.goto(BASE + "/objetos/crear")
        opcion(enc, "#categoria_id", "Mochila"); opcion(enc, "#lugar_id", "Biblioteca")
        enc.fill("#color", "Azul"); enc.fill("#descripcion", f"Mochila azul con llavero de panda ({MARCA})")
        enc.set_input_files("#foto", str(FOTO))
        enc.fill("#pregunta1", "¿Qué llavero tiene?"); enc.fill("#respuesta1", "Un panda")
        enc.fill("#pregunta2", "¿Qué cuaderno hay adentro?"); enc.fill("#respuesta2", "Cálculo II")
        foto(enc, "PS01-1a_formulario_objeto")
        enc.click("button[type=submit]:has-text('Registrar'), button[type=submit]:has-text('Guardar')")
        enc.wait_for_load_state("networkidle")
        mensaje = enc.locator("body").inner_text()
        codigo = re.search(r"OBJ-\d+", mensaje).group(0)
        foto(enc, "PS01-1b_objeto_registrado"); print("   objeto registrado:", codigo)

        print("Paso 2: la estudiante reporta su pérdida")
        est = sesion(nav, "estudiante"); est.goto(BASE + "/reportes/crear")
        opcion(est, "#categoria_id", "Mochila"); opcion(est, "#lugar_id", "Biblioteca")
        est.fill("#color", "azul"); est.fill("#descripcion", "Perdí mi mochila azul con un llavero de panda")
        est.fill("#fecha_perdida", datetime.date.today().isoformat())
        foto(est, "PS01-2a_formulario_reporte")
        est.click("button:has-text('Registrar reporte')"); est.wait_for_load_state("networkidle")
        expect(est.locator("body")).to_contain_text("coincidencia")
        foto(est, "PS01-2b_aviso_coincidencia")
        est.goto(BASE + "/avisos"); foto(est, "PS01-2c_avisos_estudiante")

        print("Paso 3: la estudiante encuentra el objeto en el catálogo y lo reclama")
        est.goto(BASE + "/catalogo"); est.fill("#q", codigo); est.click("button:has-text('Buscar')")
        est.wait_for_load_state("networkidle"); foto(est, "PS01-3a_busqueda_catalogo")
        est.click(f"a:has-text('{codigo}')"); est.wait_for_load_state("networkidle")
        est.fill("input[name=respuesta1]", "un PANDA "); est.fill("input[name=respuesta2]", "calculo ii")
        foto(est, "PS01-3b_reclamo_respuestas")
        est.click("button:has-text('Enviar reclamo')"); est.wait_for_load_state("networkidle")
        expect(est.locator("body")).to_contain_text("Aprobado")
        foto(est, "PS01-3c_reclamo_aprobado")

        print("Paso 4: el encargado verifica el carné y registra la entrega")
        enc.goto(BASE + "/avisos"); foto(enc, "PS01-4a_avisos_encargado")
        enc.goto(BASE + "/entregas")
        tarjeta = enc.locator(".tarjeta", has_text=codigo).first
        tarjeta.locator("input[name=codigo_carne]").fill("72345678")
        tarjeta.locator("input[name=observacion]").fill("Prueba de sistema PS-01")
        tarjeta.locator("input[name=carne_verificado]").check()
        foto(enc, "PS01-4b_formulario_entrega")
        tarjeta.locator("button:has-text('Registrar entrega')").click(); enc.wait_for_load_state("networkidle")
        expect(enc.locator("table")).to_contain_text(codigo)
        foto(enc, "PS01-4c_entrega_registrada")

        print("Paso 5: la estudiante ve su objeto como entregado")
        est.goto(BASE + "/mis-reclamos")
        expect(est.locator("body")).to_contain_text("Entregado")
        foto(est, "PS01-5_reclamo_entregado")

        est.goto(BASE + f"/catalogo"); est.fill("#q", codigo); est.click("button:has-text('Buscar')")
        est.wait_for_load_state("networkidle")
        expect(est.locator("body")).to_contain_text("No se encontraron")   # ya no se ofrece en el catálogo
        foto(est, "PS01-6_ya_no_aparece_en_catalogo")
        nav.close()
        print("RESULTADO: flujo completo correcto para", codigo)
