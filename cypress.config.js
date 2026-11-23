import { defineConfig } from 'cypress';

/**
 * Configuración de Cypress para RecuperaYa.
 * La aplicación se levanta con: APP_ENV=cypress php artisan serve --port=8000
 * (usa la base de datos de pruebas definida en .env.cypress).
 */
export default defineConfig({
    e2e: {
        baseUrl: 'http://127.0.0.1:8000',
        specPattern: 'cypress/e2e/**/*.cy.js',
        supportFile: 'cypress/support/e2e.js',
        viewportWidth: 1366,
        viewportHeight: 900,
        defaultCommandTimeout: 8000,
        video: true,
        screenshotOnRunFailure: true,
        screenshotsFolder: 'cypress/screenshots',
        videosFolder: 'cypress/videos',
        reporter: 'junit',
        reporterOptions: {
            mochaFile: 'cypress/reports/resultado-[hash].xml',
            toConsole: false,
        },
    },
});
