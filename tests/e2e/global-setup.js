const { execFileSync } = require('child_process');
const path = require('path');

module.exports = async function globalSetup() {
    const projectRoot = path.resolve(__dirname, '..', '..');

    execFileSync('php', ['tests/e2e/support/bootstrap-test-data.php'], {
        cwd: projectRoot,
        stdio: 'inherit',
        env: { ...process.env, XDEBUG_MODE: 'off' },
    });
};
