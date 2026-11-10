"""Fusiona los selectores CSS duplicados de primer nivel (regla css:S4666 de SonarQube).

Cada selector repetido se une en su ÚLTIMA aparición (la que gana en la cascada):
las propiedades que la última regla ya redefine se descartan y el resto se conserva.
Uso: python fusionar_css.py public/css/app.css
"""
import re
import sys

ruta = sys.argv[1]
css = open(ruta, encoding='utf-8').read()


def tokenizar(texto):
    """Divide la hoja en elementos de primer nivel: ('comentario'|'regla'|'arroba'|'espacio', texto)."""
    i, n, items = 0, len(texto), []
    while i < n:
        if texto.startswith('/*', i):
            j = texto.index('*/', i) + 2
            items.append(('comentario', texto[i:j]))
            i = j
        elif texto[i].isspace():
            j = i
            while j < n and texto[j].isspace():
                j += 1
            items.append(('espacio', texto[i:j]))
            i = j
        else:
            j, prof = i, 0
            while j < n:
                if texto.startswith('/*', j):
                    j = texto.index('*/', j) + 2
                    continue
                c = texto[j]
                if c in '"\'':
                    j = texto.index(c, j + 1) + 1
                    continue
                if c == '{':
                    prof += 1
                elif c == '}':
                    prof -= 1
                    if prof == 0:
                        j += 1
                        break
                j += 1
            bloque = texto[i:j]
            items.append(('arroba' if bloque.startswith('@') else 'regla', bloque))
            i = j
    return items


def partes(regla):
    sel, cuerpo = regla.split('{', 1)
    cuerpo = cuerpo.rsplit('}', 1)[0]
    return ' '.join(sel.split()), cuerpo


def declaraciones(cuerpo):
    decl, actual, prof = [], '', 0
    for c in cuerpo:
        if c == '(':
            prof += 1
        elif c == ')':
            prof -= 1
        if c == ';' and prof == 0:
            if actual.strip():
                decl.append(actual.strip())
            actual = ''
        else:
            actual += c
    if actual.strip():
        decl.append(actual.strip())
    return decl


def propiedad(d):
    return d.split(':', 1)[0].strip().lower()


def reglas_internas(bloque):
    """Reglas (selector, declaraciones) dentro de un @media."""
    interior = bloque.split('{', 1)[1].rsplit('}', 1)[0]
    return [partes(t) for tipo, t in tokenizar(interior) if tipo == 'regla']


def conflictos(desde, hasta, sel, props):
    """Ajustes intermedios (entre las posiciones desde..hasta) del mismo selector y propiedad.
    Si una propiedad antigua se mueve después de ellos, hay que repetirlos para no cambiar la cascada."""
    extra = []
    for tipo, t in original[desde + 1:hasta]:
        if tipo == 'arroba' and t.startswith('@media'):
            preludio = t.split('{', 1)[0].strip()
            for s, cuerpo in reglas_internas(t):
                if sel in [x.strip() for x in s.split(',')]:
                    ds = [d for d in declaraciones(cuerpo) if propiedad(d) in props]
                    if ds:
                        extra.append(f'{preludio} {{ {sel} {{ {"; ".join(ds)}; }} }}')
        elif tipo == 'regla':
            s, cuerpo = partes(t)
            if s != sel and sel in [x.strip() for x in s.split(',')]:
                ds = [d for d in declaraciones(cuerpo) if propiedad(d) in props]
                if ds:
                    extra.append(f'{sel} {{ {"; ".join(ds)}; }}')
    return extra


# Declaraciones antiguas que otra regla posterior ya anulaba sobre los mismos elementos
# (verificado en las vistas: todo elemento .kpi también tiene la clase .tarjeta, cuyo padding se redefine después).
ANULADAS = {('.kpi', 'padding')}


def sobrescrita(desde, hasta, sel, prop):
    return (sel, prop) in ANULADAS


descartadas = []
items = tokenizar(css)
original = list(items)  # copia sin modificar para buscar conflictos
reglas = [(k, partes(t)[0]) for k, (tipo, t) in enumerate(items) if tipo == 'regla']
ultima, primera = {}, {}
for k, sel in reglas:
    ultima[sel] = k
    primera.setdefault(sel, k)
pendientes = {}  # selector -> declaraciones previas aún no sobrescritas
fusionados = 0
for k, sel in reglas:
    _, cuerpo = partes(items[k][1])
    decl = declaraciones(cuerpo)
    if k != ultima[sel]:
        pendientes.setdefault(sel, []).extend((k, d) for d in decl)
        items[k] = ('borrado', '')
        fusionados += 1
        continue
    if sel in pendientes:
        nuevas = {propiedad(d) for d in decl}
        previas, vistas = [], set()
        for pos, d in reversed(pendientes[sel]):  # la última declaración previa de cada propiedad es la vigente
            p = propiedad(d)
            if p in nuevas or p in vistas or any(p.startswith(n + '-') for n in nuevas):
                continue  # ya redefinida (o anulada por un shorthand como border/background)
            vistas.add(p)
            if sobrescrita(pos, k, sel, p):
                descartadas.append(f'{sel} {{ {d} }}')
                continue
            previas.insert(0, d)
        todas = previas + decl
        multilinea = '\n' in cuerpo.strip()
        if multilinea:
            texto = sel + ' {\n' + ''.join(f'  {d};\n' for d in todas) + '}'
        else:
            texto = sel + ' { ' + '; '.join(todas) + '; }'
        extra = conflictos(primera[sel], k, sel, {propiedad(d) for d in previas})
        if extra:
            texto += '\n' + '\n'.join(extra)
        items[k] = ('regla', texto)

salida = ''.join(t for tipo, t in items if tipo != 'borrado')
salida = re.sub(r'\n{3,}', '\n\n', salida)
open(ruta, 'w', encoding='utf-8', newline='\n').write(salida)
print(f'reglas duplicadas fusionadas: {fusionados}')
print('declaraciones ya anuladas por otra regla posterior (descartadas):', len(descartadas))
for d in descartadas:
    print('  ', d)
