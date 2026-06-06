import { test, expect, devices } from '@playwright/test';
const iPhone = devices['iPhone 14'];

test.use({ ...iPhone });

test.describe('Mobile viewport', () => {

    test('home renders correctly on mobile', async ({ page }) => {
        await page.goto('/home');
        // Bottom nav should be in view
        const nav = page.locator('nav.bottom-nav');
        await expect(nav).toBeVisible();
        // No horizontal scroll
        const bodyWidth = await page.evaluate(
            () => document.body.scrollWidth);
        const viewportWidth = await page.evaluate(
            () => window.innerWidth);
        expect(bodyWidth).toBeLessThanOrEqual(viewportWidth + 5);
    });

    test('legacy card fills screen on mobile', async ({ page }) => {
        await page.goto('/profile/legacy-card');
        await expect(page.locator('text=المحسنات')).toBeVisible();
    });

});
