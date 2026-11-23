"""Verificación visual de la refactorización de CSS: captura páginas y compara píxel a píxel.

Uso:
  python comparar_css.py capturar <carpeta>         -> guarda capturas de escritorio (1366) y celular (375)
  python comparar_css.py comparar <antes> <despues>  -> informa el % de píxeles distintos por página
"""
import json
import pathlib
import sys

APP = 'http://127.0.0.1:8000'
PAGINAS = {
    'publico': ['/login', '/registro', '/privacidad'],
    'encargado': ['/', '/catalogo', '/objetos', '/objetos/crear', '/entregas', '/reportes-estadisticos', '/avisos'],
    'estudiante': ['/reportes/crear', '/mis-reclamos'],
}
USUARIOS = json.loads((pathlib.Path(__file__).resolve().parents[2] / 'cypress/fixtures/usuarios.json').read_text(encoding='utf-8'))


def capturar(carpeta: pathlib.Path):
    from playwright.sync_api import sync_playwright
    carpeta.mkdir(parents=True, exist_ok=True)
    with sync_playwright() as p:
        b = p.chromium.launch(channel='msedge')
        for ancho in (1366, 375):
            for rol, rutas in PAGINAS.items():
                ctx = b.new_context(viewport={'width': ancho, 'height': 900}, reduced_motion='reduce')
                pg = ctx.new_page()
                if rol != 'publico':
                    pg.goto(f'{APP}/login')
                    pg.fill('#email', USUARIOS[rol]['email'])
                    pg.fill('#password', USUARIOS[rol]['password'])
                    pg.click('button:has-text("Ingresar")')
                    pg.wait_for_url(f'{APP}/')
                for ruta in rutas:
                    pg.goto(APP + ruta)
                    pg.wait_for_load_state('networkidle')
                    pg.add_style_tag(content='*{animation:none!important;transition:none!important;caret-color:transparent!important}')
                    pg.wait_for_timeout(300)
                    nombre = f'{ancho}_{rol}_{ruta.strip("/").replace("/", "_") or "inicio"}.png'
                    pg.screenshot(path=str(carpeta / nombre), full_page=True)
                ctx.close()
        b.close()
    print('capturas en', carpeta)


def comparar(antes: pathlib.Path, despues: pathlib.Path):
    from PIL import Image, ImageChops
    peor = 0.0
    for a in sorted(antes.glob('*.png')):
        d = despues / a.name
        ia, ib = Image.open(a).convert('RGB'), Image.open(d).convert('RGB')
        if ia.size != ib.size:
            print(f'{a.name:55s} TAMAÑO DISTINTO {ia.size} vs {ib.size}')
            peor = 100
            continue
        dif = ImageChops.difference(ia, ib).convert('L').point(lambda v: 255 if v > 8 else 0)
        pct = sum(1 for v in dif.getdata() if v) * 100 / (ia.size[0] * ia.size[1])
        peor = max(peor, pct)
        print(f'{a.name:55s} {pct:6.3f} % de píxeles distintos')
    print(f'MÁXIMA DIFERENCIA: {peor:.3f} %')


if sys.argv[1] == 'capturar':
    capturar(pathlib.Path(sys.argv[2]))
else:
    comparar(pathlib.Path(sys.argv[2]), pathlib.Path(sys.argv[3]))
