import { test, expect } from '@playwright/test';

test.describe('PWA installability', () => {

    test('manifest icons are square with matching declared sizes', async ({ request }) => {
        const manifest = await request.get('/manifest.json');
        expect(manifest.ok()).toBeTruthy();

        const data = await manifest.json();
        expect(data.icons).toBeDefined();
        expect(data.icons.length).toBeGreaterThanOrEqual(2);

        for (const icon of data.icons) {
            const [declaredW, declaredH] = icon.sizes.split('x').map(Number);
            expect(declaredW).toBe(declaredH);

            const iconResp = await request.get(icon.src);
            expect(iconResp.ok()).toBeTruthy();

            const contentType = iconResp.headers()['content-type'] || '';
            expect(contentType).toContain('image');
        }
    });

    test('script has data-navigate-once to prevent wire:navigate re-declaration', async ({ page }) => {
        await page.goto('/home');
        const script = page.locator('script[data-navigate-once]');
        await expect(script).toHaveCount(1);
    });

    test('install card exists in dashboard markup', async ({ page }) => {
        await page.goto('/home');
        await expect(page.locator('#home-install-card')).toHaveCount(1);
    });

    test('install banner exists in layout markup', async ({ page }) => {
        await page.goto('/home');
        await expect(page.locator('#install-banner')).toHaveCount(1);
    });

    test('mobile-web-app-capable meta tag is present', async ({ page }) => {
        await page.goto('/home');
        const meta = page.locator('meta[name="mobile-web-app-capable"]');
        await expect(meta).toHaveAttribute('content', 'yes');
    });

    test('offline.html is precached by service worker', async ({ page }) => {
        await page.goto('/home');

        const cacheKeys = await page.evaluate(async () => {
            if (!('caches' in window)) return [];
            const names = await caches.keys();
            const keys = [];
            for (const name of names) {
                const cache = await caches.open(name);
                const reqs = await cache.keys();
                keys.push(...reqs.map(r => new URL(r.url).pathname));
            }
            return keys;
        });

        expect(cacheKeys).toContain('/offline.html');
    });
});
