import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('landing page loads', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/Muhsinat/);
    });

    test('register form loads', async ({ page }) => {
        await page.goto('/register');
        await expect(page.locator('input[name=name]')).toBeVisible();
        await expect(page.locator('input[name=email]')).toBeVisible();
        await expect(page.locator('input[name=password]')).toBeVisible();
    });

    test('login form loads', async ({ page }) => {
        await page.goto('/login');
        await expect(page.locator('input[name=email]')).toBeVisible();
    });

    test('invalid login shows error', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name=email]', 'wrong@email.com');
        await page.fill('input[name=password]', 'wrongpassword');
        await page.click('button[type=submit]');
        await expect(page.locator('text=credentials')).toBeVisible();
    });

});
