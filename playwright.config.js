import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/setup/auth.setup.js',
    use: {
        baseURL: 'http://127.0.0.1:8000',
        headless: true,
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        storageState: 'tests/e2e/.auth/member.json',
    },
    timeout: 15000,
});
