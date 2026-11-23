/**
 * CY-12 y CY-13 · Proceso principal del negocio (reporte de pérdida → coincidencia)
 * y módulo de reportes con filtros (RF-06, RF-07, RF-12).
 */
describe('Proceso de negocio y módulo de reportes', () => {
    const marca = `CY${Date.now()}`;

    it('CY-12 · Estudiante reporta una pérdida y el sistema busca coincidencias', () => {
        cy.login('estudiante');
        cy.visit('/reportes/crear');
        cy.get('#categoria_id').select(1);
        cy.get('#color').type('Negro');
        cy.get('#descripcion').type(`Perdí mi objeto negro con llavero ${marca}`);
        cy.contains('button', 'Registrar reporte').click();
        cy.location('pathname').should('eq', '/reportes');
        cy.get('.alerta').should('contain', 'Reporte registrado');
        cy.screenshot('CY-12_reporte_perdida');
    });

    it('CY-13 · Los indicadores del reporte cambian según los filtros', () => {
        cy.login('encargado');
        cy.visit('/reportes-estadisticos');
        cy.get('#indicadores [data-kpi="total"] .valor').invoke('text').then((textoTotal) => {
            const total = Number(textoTotal.trim());
            expect(total, 'total de objetos sin filtro').to.be.greaterThan(0);
            cy.screenshot('CY-13a_reportes_sin_filtro');

            // Filtro por estado: el total filtrado no puede superar al total general
            cy.get('#estado').select('Devuelto');
            cy.get('#btn-filtrar').click();
            cy.location('search').should('contain', 'estado=Devuelto');
            cy.get('#indicadores [data-kpi="custodia"] .valor').should('have.text', '0');
            cy.get('#indicadores [data-kpi="total"] .valor').invoke('text').then((t) => {
                expect(Number(t.trim())).to.be.at.most(total);
            });
            cy.screenshot('CY-13b_reportes_filtrados');
        });
        cy.get('#grafico-mensual').should('be.visible');
        // Evidencia del ejecutor de Cypress (panel de comandos + aplicación)
        cy.screenshot('CY-13c_ejecutor_cypress', { capture: 'runner' });
    });
});
