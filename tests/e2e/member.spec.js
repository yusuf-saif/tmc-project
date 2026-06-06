import { test, expect } from '@playwright/test';

test.describe('Member flows', () => {

    test('home dashboard loads', async ({ page }) => {
        await page.goto('/home');
        await expect(page.locator('text=Assalamu')).toBeVisible();
    });

    test('bottom navigation is visible', async ({ page }) => {
        await page.goto('/home');
        await expect(page.locator('nav.bottom-nav')).toBeVisible();
    });

    test('events list loads', async ({ page }) => {
        await page.goto('/events');
        await expect(page.locator('h1')).toContainText('Events');
    });

    test('resources list loads', async ({ page }) => {
        await page.goto('/resources');
        await expect(page.locator('h1')).toContainText('Resources');
    });

    test('journal screen loads', async ({ page }) => {
        await page.goto('/journal');
        await expect(page.locator('h1')).toContainText('Journal');
    });

    test('souq directory loads', async ({ page }) => {
        await page.goto('/souq');
        await expect(page.locator('h1')).toContainText('Souq');
    });

    test('wallet loads with balance', async ({ page }) => {
        await page.goto('/wallet');
        await expect(page.locator('text=JANNAH COINS')).toBeVisible();
    });

    test('profile loads', async ({ page }) => {
        await page.goto('/profile');
        await expect(page.locator('h1, .font-display').first()).toBeVisible();
    });

    test('legacy card loads', async ({ page }) => {
        await page.goto('/profile/legacy-card');
        await expect(page.locator('text=المحسنات')).toBeVisible();
    });

    test('community page loads', async ({ page }) => {
        await page.goto('/community');
        await expect(page.locator('h1')).toContainText('Community');
    });

});
