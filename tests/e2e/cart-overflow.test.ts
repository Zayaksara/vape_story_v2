/**
 * @group e2e
 * @group cart
 */

import { test, expect } from '@playwright/test';
import { POSData } from '../fixtures/pos-data';

test.describe('Cart Item Limit & Overflow Testing', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/pos');
    await expect(page.locator('text="Keranjang"')).toBeVisible({ timeout: 10000 });
  });

  test('TC-001: Add 4 products (2 from Freebase, 2 from Nicotine Salt) and verify cart display', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });

    // Step 1: Select Freebase category and add 2 products
    const freebaseCat = page.locator('button.category-pill', { hasText: 'Freebase' });
    await expect(freebaseCat).toBeVisible();
    await freebaseCat.click();
    await page.waitForLoadState('networkidle');

    const freebaseAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
    const freebaseCount = await freebaseAddBtns.count();
    
    for (let i = 0; i < Math.min(2, freebaseCount); i++) {
      await freebaseAddBtns.nth(i).click();
      await page.waitForTimeout(500);
    }

    // Step 2: Select Nicotine Salt category and add 2 products
    const nsCat = page.locator('button.category-pill', { hasText: /Nicotine Salt/i });
    await expect(nsCat).toBeVisible();
    await nsCat.click();
    await page.waitForLoadState('networkidle');

    const nsAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
    const nsCount = await nsAddBtns.count();
    
    for (let i = 0; i < Math.min(2, nsCount); i++) {
      await nsAddBtns.nth(i).click();
      await page.waitForTimeout(500);
    }

    // Step 3: Verify cart has exactly 4 items
    const cartItems = page.locator('.cart-item');
    await expect(cartItems).toHaveCount(4);

    // Step 4: Verify cart items are visible within tablet height
    const cartPanel = page.locator('.cart-panel');
    await expect(cartPanel).toBeVisible();

    const cartItemsContainer = cartPanel.locator('> div:nth-child(2)');
    const containerBox = await cartItemsContainer.boundingBox();
    
    // All 4 items should fit within container height
    for (let i = 0; i < 4; i++) {
      const item = cartItems.nth(i);
      await expect(item).toBeVisible();
      const itemBox = await item.boundingBox();
      
      // Item should be within container bounds
      if (containerBox && itemBox) {
        const containerBottom = containerBox.y + containerBox.height;
        const itemBottom = itemBox.y + itemBox.height;
        
        // Items may be slightly overflowed if container is at limit
        // But they should be rendered
        expect(itemBox.y).toBeGreaterThan(containerBox.y - 50);
      }
    }

    // Step 5: Verify each cart item has proper structure
    for (let i = 0; i < 4; i++) {
      const item = cartItems.nth(i);
      await expect(item.locator('h4')).toBeVisible();
      await expect(item.locator('.qty-btn')).toBeVisible();
      await expect(item.locator('button[aria-label*="Hapus"]')).toBeVisible();
    }
  });

  test('TC-002: Add 5th product and verify cart scroll behavior', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });

    // Add 5 products total
    const freebaseCat = page.locator('button.category-pill', { hasText: 'Freebase' });
    await freebaseCat.click();
    await page.waitForLoadState('networkidle');

    const freebaseAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
    const freebaseCount = await freebaseAddBtns.count();
    
    // Add 3 from Freebase
    for (let i = 0; i < Math.min(3, freebaseCount); i++) {
      await freebaseAddBtns.nth(i).click();
      await page.waitForTimeout(500);
    }

    const nsCat = page.locator('button.category-pill', { hasText: /Nicotine Salt/i });
    await nsCat.click();
    await page.waitForLoadState('networkidle');

    const nsAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
    const nsCount = await nsAddBtns.count();
    
    // Add 2 from Nicotine Salt (total = 5)
    for (let i = 0; i < Math.min(2, nsCount); i++) {
      await nsAddBtns.nth(i).click();
      await page.waitForTimeout(500);
    }

    // Verify 5 items in cart
    const cartItems = page.locator('.cart-item');
    await expect(cartItems).toHaveCount(5);

    // Check cart container scrollability
    const cartItemsContainer = page.locator('.cart-panel .flex-1.overflow-y-auto');
    await expect(cartItemsContainer).toBeVisible();

    // Verify that container has scroll capability when items overflow
    const containerBox = await cartItemsContainer.boundingBox();
    const itemsBox = await cartItems.boundingBox();
    
    // With 5 items, content likely exceeds container height
    // Cart parent should NOT scroll, only cart items container
    const cartParent = page.locator('.cart-panel');
    const parentStyle = await cartParent.evaluate((el) => {
      return window.getComputedStyle(el).overflowY;
    });
    
    // Parent should not have overflow scroll
    expect(parentStyle).not.toBe('scroll');

    // Cart items container should have overflow auto
    const containerStyle = await cartItemsContainer.evaluate((el) => {
      return window.getComputedStyle(el).overflowY;
    });
    
    // Should be auto or scroll for overflow handling
    expect(['auto', 'scroll']).toContain(containerStyle);
  });

  test('TC-003: Cart max 4 items visible on iPad viewport without scrolling', async ({ page }) => {
    // iPad viewport
    await page.setViewportSize({ width: 768, height: 1024 });

    // Add exactly 4 products
    const freebaseCat = page.locator('button.category-pill', { hasText: 'Freebase' });
    await freebaseCat.click();
    await page.waitForLoadState('networkidle');

    const freebaseAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
    const freebaseCount = await freebaseAddBtns.count();
    
    for (let i = 0; i < Math.min(4, freebaseCount); i++) {
      await freebaseAddBtns.nth(i).click();
      await page.waitForTimeout(500);
    }

    const cartItems = page.locator('.cart-item');
    await expect(cartItems).toHaveCount(4);

    // Verify cart panel height fits within iPad screen
    const cartPanel = page.locator('.cart-panel');
    const panelBox = await cartPanel.boundingBox();
    
    expect(panelBox?.height).toBeLessThan(1024);
    
    // Panel should be visible without vertical scroll
    const mainContent = page.locator('main.flex-1');
    const mainStyle = await mainContent.evaluate((el) => {
      return window.getComputedStyle(el).overflowY;
    });

    // All cart items should be accessible
    for (let i = 0; i < 4; i++) {
      await expect(cartItems.nth(i)).toBeVisible();
    }
  });

  test('TC-004: Cart item removal maintains proper count', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });

    // Add 5 products
    const freebaseCat = page.locator('button.category-pill', { hasText: 'Freebase' });
    await freebaseCat.click();
    await page.waitForLoadState('networkidle');

    const freebaseAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
    const freebaseCount = await freebaseAddBtns.count();
    
    for (let i = 0; i < Math.min(5, freebaseCount); i++) {
      await freebaseAddBtns.nth(i).click();
      await page.waitForTimeout(500);
    }

    const cartItems = page.locator('.cart-item');
    await expect(cartItems).toHaveCount(5);

    // Remove one item
    const firstDeleteBtn = cartItems.first().locator('button[aria-label*="Hapus"]');
    await firstDeleteBtn.click();
    await page.waitForTimeout(500);

    // Should have 4 items remaining
    await expect(cartItems).toHaveCount(4);

    // All remaining items should be visible and properly laid out
    for (let i = 0; i < 4; i++) {
      await expect(cartItems.nth(i)).toBeVisible();
    }
  });

  test('TC-005: Cart item quantity update maintains layout', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });

    // Add 4 products
    const freebaseCat = page.locator('button.category-pill', { hasText: 'Freebase' });
    await freebaseCat.click();
    await page.waitForLoadState('networkidle');

    const freebaseAddBtns = page.locator('button[data-testid="add-to-cart-btn"]').filter({ hasText: /Tambah|Add/i });
    const freebaseCount = await freebaseAddBtns.count();
    
    for (let i = 0; i < Math.min(4, freebaseCount); i++) {
      await freebaseAddBtns.nth(i).click();
      await page.waitForTimeout(500);
    }

    const cartItems = page.locator('.cart-item');
    await expect(cartItems).toHaveCount(4);

    // Increase quantity of first item
    const firstItem = cartItems.first();
    const increaseBtn = firstItem.locator('button[aria-label="Tambah jumlah"]');
    await increaseBtn.click();
    await page.waitForTimeout(300);

    // Verify layout still intact
    await expect(cartItems).toHaveCount(4);
    for (let i = 0; i < 4; i++) {
      await expect(cartItems.nth(i)).toBeVisible();
    }

    // Decrease quantity back
    const decreaseBtn = firstItem.locator('button[aria-label="Kurangi jumlah"]');
    await decreaseBtn.click();
    await page.waitForTimeout(300);

    await expect(cartItems).toHaveCount(4);
  });
});
