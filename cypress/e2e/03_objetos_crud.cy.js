/**
 * CY-06 a CY-09 · Registro, validación, edición y eliminación de objetos encontrados (RF-03).
 */
describe('Gestión de objetos encontrados (encargado de custodia)', () => {
    const marca = `CY${Date.now()}`;
    const descripcion = `Mochila negra con llavero de prueba ${marca}`;

    beforeEach(() => cy.login('encargado'));

    it('CY-06 · Registra un objeto encontrado con foto y preguntas de verificación', () => {
        cy.registrarObjeto(descripcion);
        cy.location('pathname').should('eq', '/objetos');
        cy.get('.alerta.exito').should('contain', 'registrado y publicado en el catálogo');
        cy.get('.alerta.exito').invoke('text').should('match', /OBJ-\d{4}/);
        cy.screenshot('CY-06_objeto_registrado');
    });

    it('CY-07 · Valida el formulario: descripción de menos de 10 caracteres', () => {
        cy.visit('/objetos/crear');
        cy.get('#categoria_id').select(1);
        cy.get('#lugar_id').select(1);
        cy.get('#color').type('Azul');
        cy.get('#descripcion').type('Corta');
        cy.get('#foto').selectFile('cypress/fixtures/objeto.png');
        cy.get('#pregunta1').type('¿Marca?');
        cy.get('#respuesta1').type('X');
        cy.get('#pregunta2').type('¿Color?');
        cy.get('#respuesta2').type('Y');
        cy.contains('button', 'Registrar objeto').click();
        cy.location('pathname').should('eq', '/objetos/crear');
        cy.get('.con-error #descripcion').should('exist');
        cy.screenshot('CY-07_validacion_descripcion');
    });

    it('CY-08 · Edita el color de un objeto registrado', () => {
        cy.visit(`/objetos?q=${marca}`);
        cy.contains('tr', 'Negro').contains('a', 'Editar').click();
        cy.get('#color').clear().type('Rojo');
        cy.contains('button', 'Guardar cambios').click();
        cy.get('.alerta.exito').should('contain', 'actualizado');
        cy.visit(`/objetos?q=${marca}`);
        cy.get('table').should('contain', 'Rojo');
        cy.screenshot('CY-08_objeto_editado');
    });

    it('CY-09 · Elimina el objeto confirmando el diálogo', () => {
        cy.visit(`/objetos?q=${marca}`);
        cy.on('window:confirm', (texto) => {
            expect(texto).to.contain('¿Eliminar el objeto');
            return true;
        });
        cy.contains('button', 'Eliminar').click();
        cy.get('.alerta.exito').should('contain', 'eliminado');
        cy.visit(`/objetos?q=${marca}`);
        cy.get('table').should('not.exist');
        cy.screenshot('CY-09_objeto_eliminado');
        cy.screenshot('CY-09b_ejecutor_cypress', { capture: 'runner' });
    });
});
