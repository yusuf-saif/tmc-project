import { chromium } from '@playwright/test';

async function globalSetup() {
    const browser = await chromium.launch();
    const page = await browser.newPage();
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name=email]', 'member@test.com');
    await page.fill('input[name=password]', 'password');
    await page.click('button[type=submit]');
    await page.waitForURL('**/home');
    await page.context().storageState({
        path: 'tests/e2e/.auth/member.json',
    });
    await browser.close();
}

export default globalSetup;
