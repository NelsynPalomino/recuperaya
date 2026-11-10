/**
 * CY-01 a CY-03 · Acceso y autenticación de usuarios (RF-01, RF-02).
 */
describe('Autenticación de usuarios', () => {
    it('CY-01 · El encargado inicia sesión con credenciales válidas', () => {
        cy.fixture('usuarios').then(({ encargado }) => {
            cy.visit('/login');
            cy.get('#email').type(encargado.email);
            cy.get('#password').type(encargado.password, { log: false });
            cy.contains('button', 'Ingresar').click();
            cy.location('pathname').should('eq', '/');
            cy.get('nav').should('contain', 'Registrar objeto').and('contain', 'Reportes');
            cy.screenshot('CY-01_login_correcto');
        });
    });

    it('CY-02 · Credenciales incorrectas muestran un mensaje de error', () => {
        cy.visit('/login');
        cy.get('#email').type('admin@continental.edu.pe');
        cy.get('#password').type('ClaveEquivocada1');
        cy.contains('button', 'Ingresar').click();
        cy.location('pathname').should('eq', '/login');
        cy.get('.alerta.error').should('contain', 'Correo o contraseña incorrectos');
        cy.screenshot('CY-02_login_incorrecto');
    });

    it('CY-03 · Una ruta protegida redirige al login si no hay sesión', () => {
        cy.clearAllCookies();
        cy.visit('/objetos');
        cy.location('pathname').should('eq', '/login');
        cy.contains('h2', 'Iniciar sesión').should('be.visible');
    });
});
