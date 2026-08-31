const { defineConfig } = require('vitest/config');

module.exports = defineConfig({
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
    },
});
