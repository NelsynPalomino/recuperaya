/**
 * CY-10 y CY-11 · Navegación entre módulos y control de acceso por rol.
 */
describe('Navegación y control de acceso', () => {
    it('CY-10 · El encargado navega por todos sus módulos desde el menú', () => {
        cy.login('encargado');
        cy.visit('/');
        const modulos = [
            ['Catálogo', '/catalogo'],
            ['Objetos', '/objetos'],
            ['Entregas', '/entregas'],
            ['Reportes', '/reportes-estadisticos'],
            ['Avisos', '/avisos'],
        ];
        modulos.forEach(([texto, ruta]) => {
            cy.get('nav').contains('a', texto).click();
            cy.location('pathname').should('eq', ruta);
            cy.get('nav a.activo').should('contain', texto);
        });
        cy.screenshot('CY-10_navegacion_modulos');
    });

    it('CY-11 · Un estudiante no puede entrar al módulo de reportes (403)', () => {
        cy.login('estudiante');
        cy.visit('/');
        cy.get('nav').should('not.contain', 'Registrar objeto');
        cy.request({ url: '/reportes-estadisticos', failOnStatusCode: false }).its('status').should('eq', 403);
        cy.visit('/reportes-estadisticos', { failOnStatusCode: false });
        cy.screenshot('CY-11_acceso_denegado');
    });
});
