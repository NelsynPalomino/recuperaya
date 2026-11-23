"""Convierte los diagramas HTML de esta carpeta a imágenes PNG (usa Playwright con Microsoft Edge)."""
import pathlib
import sys

from playwright.sync_api import sync_playwright

carpeta = pathlib.Path(__file__).resolve().parent
nombres = sys.argv[1:] or [p.stem for p in carpeta.glob('*.html')]
with sync_playwright() as p:
    navegador = p.chromium.launch(channel='msedge')
    pagina = navegador.new_page(viewport={'width': 1560, 'height': 800}, device_scale_factor=2)
    for nombre in nombres:
        pagina.goto((carpeta / f'{nombre}.html').as_uri())
        pagina.screenshot(path=str(carpeta / f'{nombre}.png'), full_page=True)
        print('generado', nombre + '.png')
    navegador.close()
