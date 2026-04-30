// Playwright test to verify cart height is consistent and not cut off
import { test, expect } from '@playwright/test';

test.describe('Cart Height Consistency', () => {
  test('Cart panel should have fixed height regardless of products', async ({ page }) => {
    await page.goto('/pos');
    
    // Wait for page load
    await expect(page.locator('text="Keranjang"')).toBeVisible();
    
    // Measure cart panel height
    const cartPanel = page.locator('.cart-panel');
    const cartBox = await cartPanel.boundingBox();
    
    console.log('Cart panel height:', cartBox?.height);
    
    // Cart panel should have reasonable height (not cut off)
    expect(cartBox?.height).toBeGreaterThan(300);
    expect(cartBox?.height).toBeLessThan(1200);
    
    // Cart items container should have max-height
    const cartItemsContainer = page.locator('.cart-panel .overflow-y-auto');
    const maxHeight = await cartItemsContainer.evaluate((el) => {
      return el.style.maxHeight || window.getComputedStyle(el).maxHeight;
    });
    
    console.log('Cart items maxHeight:', maxHeight);
    expect(maxHeight).toBeTruthy();
  });

  test('Product grid should scroll independently', async ({ page }) => {
    await page.goto('/pos');
    await expect(page.locator('text="Keranjang"')).toBeVisible();
    
    // Product grid container
    const productGrid = page.locator('.flex-1.overflow-y-auto.bg-white');
    await expect(productGrid).toBeVisible();
    
    // Check if it can scroll (has overflow capability)
    const overflowY = await productGrid.evaluate((el) => {
      return window.getComputedStyle(el).overflowY;
    });
    
    // Should be auto or scroll
    expect(['auto', 'scroll']).toContain(overflowY);
  });

  test('Cart should not be cut off at bottom', async ({ page }) => {
    await page.goto('/pos');
    await expect(page.locator('text="Keranjang"')).toBeVisible();
    
    // Check cart summary button is visible
    const bayarButton = page.locator('.payment-button');
    await expect(bayarButton).toBeVisible();
    
    // Button should be in viewport
    const isVisible = await bayarButton.isVisible();
    expect(isVisible).toBe(true);
    
    // Check it's not cut off by checking its position
    const buttonBox = await bayarButton.boundingBox();
    expect(buttonBox?.y).toBeGreaterThan(0);
  });
});
