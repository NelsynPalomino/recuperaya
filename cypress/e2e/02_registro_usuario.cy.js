/**
 * CY-04 y CY-05 · Registro de información y validación de formularios (RF-01).
 */
describe('Registro de usuarios', () => {
    const codigo = String(Date.now()).slice(-8);

    it('CY-04 · El formulario rechaza un correo que no es institucional', () => {
        cy.visit('/registro');
        cy.get('#name').type('Usuario Externo');
        cy.get('#email').type(`externo${codigo}@gmail.com`);
        cy.get('#password').type('Clave2026*');
        cy.get('#password_confirmation').type('Clave2026*');
        cy.get('input[name="acepta"]').check();
        cy.contains('button', 'Crear cuenta').click();
        cy.location('pathname').should('eq', '/registro');
        cy.get('.alerta.error').should('be.visible');
        cy.get('.con-error #email').should('exist');
        cy.screenshot('CY-04_registro_correo_invalido');
    });

    it('CY-05 · Un estudiante se registra con su correo institucional', () => {
        cy.visit('/registro');
        cy.get('#name').type('Estudiante Cypress');
        cy.get('#email').type(`${codigo}@continental.edu.pe`);
        cy.get('#codigo_universitario').type(codigo);
        cy.get('#password').type('Clave2026*');
        cy.get('#password_confirmation').type('Clave2026*');
        cy.get('input[name="acepta"]').check();
        cy.contains('button', 'Crear cuenta').click();
        cy.location('pathname').should('eq', '/');
        cy.get('.alerta.exito').should('contain', 'Tu cuenta fue creada');
        cy.screenshot('CY-05_registro_exitoso');
    });
});
