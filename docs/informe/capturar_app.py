"""Capturas de evidencia del sistema en ejecución (http://127.0.0.1:8000) y del panel de SonarQube.

Uso:
  python capturar_app.py app                      -> módulo de reportes
  python capturar_app.py sonar <usuario> <clave>  -> panel de SonarQube (http://localhost:9000)
"""
import json
import pathlib
import sys

from playwright.sync_api import sync_playwright

CAPTURAS = pathlib.Path(__file__).resolve().parent / 'capturas'
CAPTURAS.mkdir(exist_ok=True)
USUARIOS = json.loads((pathlib.Path(__file__).resolve().parents[2] / 'cypress/fixtures/usuarios.json').read_text(encoding='utf-8'))
APP = 'http://127.0.0.1:8000'


def app(pg):
    u = USUARIOS['encargado']
    pg.goto(f'{APP}/login')
    pg.fill('#email', u['email'])
    pg.fill('#password', u['password'])
    pg.click('button:has-text("Ingresar")')
    pg.wait_for_url(f'{APP}/')
    pg.goto(f'{APP}/reportes-estadisticos')
    pg.screenshot(path=str(CAPTURAS / 'reportes_general.png'), clip={'x': 0, 'y': 0, 'width': 1366, 'height': 1180})
    pg.select_option('#estado', 'Devuelto')
    pg.select_option('#categoria', label='Celular')
    pg.click('#btn-filtrar')
    pg.wait_for_load_state()
    pg.screenshot(path=str(CAPTURAS / 'reportes_filtrado.png'), clip={'x': 0, 'y': 0, 'width': 1366, 'height': 1000})
    pg.goto(f'{APP}/reportes-estadisticos/imprimir')
    pg.screenshot(path=str(CAPTURAS / 'reportes_imprimir.png'), clip={'x': 0, 'y': 0, 'width': 1366, 'height': 1100})
    pg.goto(f'{APP}/objetos')
    pg.screenshot(path=str(CAPTURAS / 'app_objetos.png'))


def sonar(pg, usuario, clave):
    s = 'http://localhost:9000'
    pg.goto(f'{s}/sessions/new')
    pg.fill('#login-input', usuario)
    pg.fill('#password-input', clave)
    pg.keyboard.press('Enter')
    pg.wait_for_timeout(3000)
    for nombre, url in [('sonar_proyectos', f'{s}/projects'),
                        ('sonar_resumen', f'{s}/dashboard?id=recuperaya&codeScope=overall'),
                        ('sonar_issues', f'{s}/project/issues?id=recuperaya&issueStatuses=ACCEPTED%2CFALSE_POSITIVE'),
                        ('sonar_cobertura', f'{s}/component_measures?id=recuperaya&metric=coverage&view=list'),
                        ('sonar_medidas', f'{s}/component_measures?id=recuperaya'),
                        ('sonar_seguridad', f'{s}/security_hotspots?id=recuperaya'),
                        ('sonar_duplicacion', f'{s}/component_measures?id=recuperaya&metric=duplicated_lines_density&view=list')]:
        pg.goto(url)
        pg.wait_for_load_state('networkidle', timeout=90000)
        pg.wait_for_timeout(6000)
        for boton in ['Dismiss', 'Got it', 'Close']:  # avisos emergentes de SonarQube
            if pg.get_by_role('button', name=boton).count():
                pg.get_by_role('button', name=boton).first.click()
                pg.wait_for_timeout(800)
        pg.screenshot(path=str(CAPTURAS / f'{nombre}.png'))
        print('captura', nombre)


with sync_playwright() as p:
    b = p.chromium.launch(channel='msedge')
    pg = b.new_page(viewport={'width': 1366, 'height': 900})
    pg.set_default_timeout(120000)
    pg.set_default_navigation_timeout(120000)
    if sys.argv[1] == 'app':
        app(pg)
    else:
        sonar(pg, sys.argv[2], sys.argv[3])
    b.close()
print('ok')
