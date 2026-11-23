"""Correcciones de SonarQube en public/css/app.css posteriores a la fusión de selectores.

- css:S4658  bloque vacío ".oculto-movil { }"
- css:S9423  !important en .nueva (se resuelve con mayor especificidad: .lista li.nueva)
- css:S9440  transiciones dentro de @media (prefers-reduced-motion: no-preference)

Uso: python ajustes_css.py public/css/app.css
"""
import re
import sys

ruta = sys.argv[1]
css = open(ruta, encoding='utf-8').read()


def reemplazar(viejo, nuevo):
    global css
    assert viejo in css, viejo
    css = css.replace(viejo, nuevo, 1)


reemplazar('.oculto-movil { }\n', '')
reemplazar('.nueva { border-left: 4px solid var(--acento); padding-left: 10px !important; border-left-color: var(--acento);',
           '.lista li.nueva { padding-left: 10px; }\n.nueva { border-left: 4px solid var(--acento);')

transiciones = []
for selector, valor in [('.kpi', 'transform .15s, box-shadow .15s'),
                        ('.objeto', 'transform .18s, box-shadow .18s'),
                        ('.objeto .foto', 'transform .4s')]:
    patron = re.compile(r'(^' + re.escape(selector) + r' \{[^}]*?);?\s*transition: ' + re.escape(valor) + ';', re.M)
    css, n = patron.subn(r'\1;', css)
    assert n == 1, selector
    transiciones.append(f'  {selector} {{ transition: {valor}; }}')
reemplazar('  transition: transform .12s, box-shadow .12s, filter .12s;\n', '')
transiciones.append('  .boton { transition: transform .12s, box-shadow .12s, filter .12s; }')

css = (css.rstrip() + '\n\n/* Animaciones solo si el usuario no pidió reducir el movimiento (accesibilidad, WCAG 2.3.3) */\n'
       '@media (prefers-reduced-motion: no-preference) {\n' + '\n'.join(transiciones) + '\n}\n')
open(ruta, 'w', encoding='utf-8', newline='\n').write(css)
print('ajustes aplicados')
