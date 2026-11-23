"""Convierte la salida real de una consola (archivo de texto) en una imagen PNG estilo terminal de VS Code.

Uso: python capturar_terminal.py <archivo_entrada.txt> <salida.png> "<comando mostrado>" [ultimas_lineas]
"""
import html
import pathlib
import re
import sys

from playwright.sync_api import sync_playwright

entrada, salida, comando = sys.argv[1], sys.argv[2], sys.argv[3]
ultimas = int(sys.argv[4]) if len(sys.argv) > 4 else 60
texto = pathlib.Path(entrada).read_text(encoding='utf-8', errors='replace')
texto = re.sub(r'\x1b\[[0-9;]*m', '', texto)  # quita códigos de color ANSI
lineas = [l.rstrip() for l in texto.splitlines() if 'DevTools listening' not in l and 'Mocha version' not in l]
while lineas and not lineas[-1]:
    lineas.pop()
lineas = lineas[-ultimas:]


def color(l: str) -> str:
    e = html.escape(l)
    if re.search(r'✔|passed|OK \(|All specs passed', l):
        return f'<span style="color:#4ec97b">{e}</span>'
    if re.search(r'✘|✖|FAIL|failed', l):
        return f'<span style="color:#f14c4c">{e}</span>'
    return e


cuerpo = '\n'.join(color(l) for l in lineas)
pagina = f"""<!doctype html><html><head><meta charset="utf-8"><style>
body{{margin:0;background:#1e1e1e;font-family:Consolas,'Cascadia Mono',monospace;font-size:13px;color:#cccccc;width:1180px}}
.barra{{background:#252526;color:#ccc;padding:6px 12px;font-family:'Segoe UI';font-size:12px;border-bottom:1px solid #333}}
.barra b{{color:#fff;border-bottom:1px solid #fff;padding-bottom:4px}}
pre{{margin:0;padding:10px 14px;white-space:pre-wrap;line-height:1.35}}
.cmd{{color:#dcdcaa}}</style></head><body>
<div class="barra"><b>TERMINAL</b> &nbsp; PS C:\\xampp\\htdocs\\recuperaya_laravel</div>
<pre><span class="cmd">PS C:\\xampp\\htdocs\\recuperaya_laravel&gt; {html.escape(comando)}</span>
{cuerpo}</pre></body></html>"""
tmp = pathlib.Path(salida).with_suffix('.html')
tmp.write_text(pagina, encoding='utf-8')
with sync_playwright() as p:
    b = p.chromium.launch(channel='msedge')
    pg = b.new_page(viewport={'width': 1180, 'height': 300}, device_scale_factor=2)
    pg.goto(tmp.resolve().as_uri())
    pg.screenshot(path=salida, full_page=True)
    b.close()
tmp.unlink()
print('generado', salida)
