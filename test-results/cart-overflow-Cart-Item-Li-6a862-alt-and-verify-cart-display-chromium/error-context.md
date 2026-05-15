# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: cart-overflow.test.ts >> Cart Item Limit & Overflow Testing >> TC-001: Add 4 products (2 from Freebase, 2 from Nicotine Salt) and verify cart display
- Location: tests\e2e\cart-overflow.test.ts:15:3

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: locator('text="Keranjang"')
Expected: visible
Timeout: 10000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" with timeout 10000ms
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
  1   | /**
  2   |  * @group e2e
  3   |  * @group cart
  4   |  */
  5   | 
  6   | import { test, expect } from '@playwright/test';
  7   | import { POSData } from '../fixtures/pos-data';
  8   | 
  9   | test.describe('Cart Item Limit & Overflow Testing', () => {
  10  |   test.beforeEach(async ({ page }) => {
  11  |     await page.goto('/pos');
> 12  |     await expect(page.locator('text="Keranjang"')).toBeVisible({ timeout: 10000 });
      |                                                    ^ Error: expect(locator).toBeVisible() failed
  13  |   });
  14  | 
  15  |   test('TC-001: Add 4 products (2 from Freebase, 2 from Nicotine Salt) and verify cart display', async ({ page }) => {
  16  |     await page.setViewportSize({ width: 768, height: 1024 });
  17  | 
  18  |     // Step 1: Select Freebase category and add 2 products
  19  |     const freebaseCat = page.locator('button.category-pill', { hasText: 'Freebase' });
  20  |     await expect(freebaseCat).toBeVisible();
  21  |     await freebaseCat.click();
  22  |     await page.waitForLoadState('networkidle');
  23  | 
  24  |     const freebaseAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
  25  |     const freebaseCount = await freebaseAddBtns.count();
  26  |     
  27  |     for (let i = 0; i < Math.min(2, freebaseCount); i++) {
  28  |       await freebaseAddBtns.nth(i).click();
  29  |       await page.waitForTimeout(500);
  30  |     }
  31  | 
  32  |     // Step 2: Select Nicotine Salt category and add 2 products
  33  |     const nsCat = page.locator('button.category-pill', { hasText: /Nicotine Salt/i });
  34  |     await expect(nsCat).toBeVisible();
  35  |     await nsCat.click();
  36  |     await page.waitForLoadState('networkidle');
  37  | 
  38  |     const nsAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
  39  |     const nsCount = await nsAddBtns.count();
  40  |     
  41  |     for (let i = 0; i < Math.min(2, nsCount); i++) {
  42  |       await nsAddBtns.nth(i).click();
  43  |       await page.waitForTimeout(500);
  44  |     }
  45  | 
  46  |     // Step 3: Verify cart has exactly 4 items
  47  |     const cartItems = page.locator('.cart-item');
  48  |     await expect(cartItems).toHaveCount(4);
  49  | 
  50  |     // Step 4: Verify cart items are visible within tablet height
  51  |     const cartPanel = page.locator('.cart-panel');
  52  |     await expect(cartPanel).toBeVisible();
  53  | 
  54  |     const cartItemsContainer = cartPanel.locator('> div:nth-child(2)');
  55  |     const containerBox = await cartItemsContainer.boundingBox();
  56  |     
  57  |     // All 4 items should fit within container height
  58  |     for (let i = 0; i < 4; i++) {
  59  |       const item = cartItems.nth(i);
  60  |       await expect(item).toBeVisible();
  61  |       const itemBox = await item.boundingBox();
  62  |       
  63  |       // Item should be within container bounds
  64  |       if (containerBox && itemBox) {
  65  |         const containerBottom = containerBox.y + containerBox.height;
  66  |         const itemBottom = itemBox.y + itemBox.height;
  67  |         
  68  |         // Items may be slightly overflowed if container is at limit
  69  |         // But they should be rendered
  70  |         expect(itemBox.y).toBeGreaterThan(containerBox.y - 50);
  71  |       }
  72  |     }
  73  | 
  74  |     // Step 5: Verify each cart item has proper structure
  75  |     for (let i = 0; i < 4; i++) {
  76  |       const item = cartItems.nth(i);
  77  |       await expect(item.locator('h4')).toBeVisible();
  78  |       await expect(item.locator('.qty-btn')).toBeVisible();
  79  |       await expect(item.locator('button[aria-label*="Hapus"]')).toBeVisible();
  80  |     }
  81  |   });
  82  | 
  83  |   test('TC-002: Add 5th product and verify cart scroll behavior', async ({ page }) => {
  84  |     await page.setViewportSize({ width: 768, height: 1024 });
  85  | 
  86  |     // Add 5 products total
  87  |     const freebaseCat = page.locator('button.category-pill', { hasText: 'Freebase' });
  88  |     await freebaseCat.click();
  89  |     await page.waitForLoadState('networkidle');
  90  | 
  91  |     const freebaseAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
  92  |     const freebaseCount = await freebaseAddBtns.count();
  93  |     
  94  |     // Add 3 from Freebase
  95  |     for (let i = 0; i < Math.min(3, freebaseCount); i++) {
  96  |       await freebaseAddBtns.nth(i).click();
  97  |       await page.waitForTimeout(500);
  98  |     }
  99  | 
  100 |     const nsCat = page.locator('button.category-pill', { hasText: /Nicotine Salt/i });
  101 |     await nsCat.click();
  102 |     await page.waitForLoadState('networkidle');
  103 | 
  104 |     const nsAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
  105 |     const nsCount = await nsAddBtns.count();
  106 |     
  107 |     // Add 2 from Nicotine Salt (total = 5)
  108 |     for (let i = 0; i < Math.min(2, nsCount); i++) {
  109 |       await nsAddBtns.nth(i).click();
  110 |       await page.waitForTimeout(500);
  111 |     }
  112 | 
```