/**
 * Comandos personalizados de RecuperaYa.
 * cy.login('encargado') inicia sesión con un usuario del seeder (cypress/fixtures/usuarios.json)
 * y guarda la sesión con cy.session para no repetir el formulario en cada prueba.
 */
Cypress.Commands.add('login', (rol) => {
    cy.fixture('usuarios').then((usuarios) => {
        const u = usuarios[rol];
        cy.session(rol, () => {
            cy.visit('/login');
            cy.get('#email').type(u.email);
            cy.get('#password').type(u.password, { log: false });
            cy.get('form').contains('button', 'Ingresar').click();
            cy.location('pathname').should('eq', '/');
        });
    });
});

/** Registra un objeto encontrado desde el formulario y devuelve su descripción única. */
Cypress.Commands.add('registrarObjeto', (descripcion, color = 'Negro') => {
    cy.visit('/objetos/crear');
    cy.get('#categoria_id').select(1);
    cy.get('#lugar_id').select(1);
    cy.get('#color').type(color);
    cy.get('#descripcion').type(descripcion);
    cy.get('#foto').selectFile('cypress/fixtures/objeto.png');
    cy.get('#pregunta1').type('¿Qué marca es?');
    cy.get('#respuesta1').type('Samsung');
    cy.get('#pregunta2').type('¿Qué sticker tiene?');
    cy.get('#respuesta2').type('Un gato');
    cy.get('form').contains('button', /Registrar|Guardar/).click();
});
