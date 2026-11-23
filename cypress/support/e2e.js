import './commands';

// Laravel muestra errores de JS de terceros (fuentes de Google, etc.) que no forman parte de la prueba.
Cypress.on('uncaught:exception', () => false);
