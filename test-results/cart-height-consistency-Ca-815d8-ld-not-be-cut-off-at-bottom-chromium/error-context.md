# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: cart-height-consistency.test.ts >> Cart Height Consistency >> Cart should not be cut off at bottom
- Location: tests\e2e\cart-height-consistency.test.ts:48:3

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: locator('text="Keranjang"')
Expected: visible
Timeout: 5000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" with timeout 5000ms
  - waiting for locator('text="Keranjang"')

```

# Page snapshot

```yaml
- main [ref=e2]:
  - generic [ref=e4]:
    - heading "404" [level=1] [ref=e5]
    - generic [ref=e6]: Not Found
```

# Test source

```ts
  1  | // Playwright test to verify cart height is consistent and not cut off
  2  | import { test, expect } from '@playwright/test';
  3  | 
  4  | test.describe('Cart Height Consistency', () => {
  5  |   test('Cart panel should have fixed height regardless of products', async ({ page }) => {
  6  |     await page.goto('/pos');
  7  |     
  8  |     // Wait for page load
  9  |     await expect(page.locator('text="Keranjang"')).toBeVisible();
  10 |     
  11 |     // Measure cart panel height
  12 |     const cartPanel = page.locator('.cart-panel');
  13 |     const cartBox = await cartPanel.boundingBox();
  14 |     
  15 |     console.log('Cart panel height:', cartBox?.height);
  16 |     
  17 |     // Cart panel should have reasonable height (not cut off)
  18 |     expect(cartBox?.height).toBeGreaterThan(300);
  19 |     expect(cartBox?.height).toBeLessThan(1200);
  20 |     
  21 |     // Cart items container should have max-height
  22 |     const cartItemsContainer = page.locator('.cart-panel .overflow-y-auto');
  23 |     const maxHeight = await cartItemsContainer.evaluate((el) => {
  24 |       return el.style.maxHeight || window.getComputedStyle(el).maxHeight;
  25 |     });
  26 |     
  27 |     console.log('Cart items maxHeight:', maxHeight);
  28 |     expect(maxHeight).toBeTruthy();
  29 |   });
  30 | 
  31 |   test('Product grid should scroll independently', async ({ page }) => {
  32 |     await page.goto('/pos');
  33 |     await expect(page.locator('text="Keranjang"')).toBeVisible();
  34 |     
  35 |     // Product grid container
  36 |     const productGrid = page.locator('.flex-1.overflow-y-auto.bg-white');
  37 |     await expect(productGrid).toBeVisible();
  38 |     
  39 |     // Check if it can scroll (has overflow capability)
  40 |     const overflowY = await productGrid.evaluate((el) => {
  41 |       return window.getComputedStyle(el).overflowY;
  42 |     });
  43 |     
  44 |     // Should be auto or scroll
  45 |     expect(['auto', 'scroll']).toContain(overflowY);
  46 |   });
  47 | 
  48 |   test('Cart should not be cut off at bottom', async ({ page }) => {
  49 |     await page.goto('/pos');
> 50 |     await expect(page.locator('text="Keranjang"')).toBeVisible();
     |                                                    ^ Error: expect(locator).toBeVisible() failed
  51 |     
  52 |     // Check cart summary button is visible
  53 |     const bayarButton = page.locator('.payment-button');
  54 |     await expect(bayarButton).toBeVisible();
  55 |     
  56 |     // Button should be in viewport
  57 |     const isVisible = await bayarButton.isVisible();
  58 |     expect(isVisible).toBe(true);
  59 |     
  60 |     // Check it's not cut off by checking its position
  61 |     const buttonBox = await bayarButton.boundingBox();
  62 |     expect(buttonBox?.y).toBeGreaterThan(0);
  63 |   });
  64 | });
  65 | 
```