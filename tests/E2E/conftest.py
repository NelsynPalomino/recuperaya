"""Configuración de las pruebas E2E (de extremo a extremo) con Playwright.

Se ejecutan contra el sistema en funcionamiento (XAMPP o Docker) usando Microsoft Edge.
Son pruebas de solo lectura: navegan y verifican, sin crear ni borrar registros.

    python -m pytest tests/E2E -v
    set RY_BASE_URL=http://localhost:8080   (para probar la versión en Docker)
"""
import os
from pathlib import Path

import pytest
from playwright.sync_api import sync_playwright

BASE_URL = os.environ.get("RY_BASE_URL", "http://localhost/recuperaya_laravel/public").rstrip("/")
CAPTURAS = Path(__file__).parent / "capturas"
CAPTURAS.mkdir(exist_ok=True)

USUARIOS = {
    "admin": ("admin@continental.edu.pe", "Admin2026*"),
    "encargado": ("encargado@continental.edu.pe", "Encargado2026*"),
    "estudiante": ("72345678@continental.edu.pe", "Usuario2026*"),
}


@pytest.fixture(scope="session")
def navegador():
    with sync_playwright() as p:
        # channel="msedge" usa el Edge instalado en Windows (no descarga otro navegador).
        nav = p.chromium.launch(channel="msedge", headless=os.environ.get("RY_VER") != "1")
        yield nav
        nav.close()


@pytest.fixture
def pagina(navegador):
    contexto = navegador.new_context(viewport={"width": 1366, "height": 768}, locale="es-PE")
    pag = contexto.new_page()
    yield pag
    contexto.close()


def url(ruta: str = "/") -> str:
    return BASE_URL + ruta


def iniciar_sesion(pag, rol: str):
    correo, clave = USUARIOS[rol]
    pag.goto(url("/login"))
    pag.fill("input[name=email]", correo)
    pag.fill("input[name=password]", clave)
    pag.click("button[type=submit]")
    pag.wait_for_load_state("networkidle")


def capturar(pag, nombre: str):
    pag.screenshot(path=str(CAPTURAS / f"{nombre}.png"), full_page=True)
