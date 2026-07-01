import { test, expect } from '@playwright/test';

test.describe('Souq payment redirect', () => {

    test('pay listing navigates to external Paystack checkout URL', async ({ page }) => {
        await page.goto('/souq/apply');

        // Verify the "Pay Now" button is visible (approved + unpaid listing exists)
        const payButton = page.locator('button', { hasText: /Pay.*Now/ });
        await expect(payButton).toBeVisible({ timeout: 5000 });

        // Click the Pay button
        await payButton.click();

        // Wait for the browser to navigate to the Paystack checkout URL
        // The bug ($this->redirect) would cause Turbo.visit() to silently fail
        // for cross-origin URLs, so the page would stay on /souq/apply.
        // The fix (return redirect()->away()) returns a proper HTTP 302,
        // causing the browser to navigate to checkout.paystack.com.
        await page.waitForURL(/checkout\.paystack\.com/, { timeout: 15000 });

        // Confirm we actually landed on Paystack
        expect(page.url()).toContain('checkout.paystack.com');
    });

});
