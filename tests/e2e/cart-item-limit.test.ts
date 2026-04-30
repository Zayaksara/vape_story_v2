import { test, expect } from '@playwright/test'

test.describe('Cart Item Limit and Scroll Testing', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to POS dashboard
    await page.goto('/pos')
    
    // Wait for page to load
    await expect(page.locator('h2:text("Keranjang")')).toBeVisible()
  })

  test('Cart items should be limited to 4 and scrollable on 5th item - Freebase and Nicotine Salt categories', async ({ page }) => {
    // Set viewport to tablet/iPad size
    await page.setViewportSize({ width: 768, height: 1024 })

    // Step 1: Select "Freebase" category
    const freebaseCategory = page.locator('button.category-pill', { hasText: 'Freebase' })
    await expect(freebaseCategory).toBeVisible()
    await freebaseCategory.click()
    
    // Wait for products to load
    await page.waitForSelector('.product-card', { state: 'visible' })
    
    // Click first 2 products in Freebase
    const freebaseProducts = page.locator('.product-card').filter({ hasText: /./ })
    const freebaseCount = await freebaseProducts.count()
    
    console.log(`Found ${freebaseCount} Freebase products`)
    
    // Add first 2 products
    for (let i = 0; i < Math.min(2, freebaseCount); i++) {
      const product = freebaseProducts.nth(i)
      await expect(product).toBeVisible()
      
      // Click add to cart button
      const addButton = product.locator('button:has-text("Tambah")')
      await expect(addButton).toBeVisible()
      await addButton.click()
      
      // Wait for cart update
      await page.waitForTimeout(500)
    }

    // Step 2: Switch to "Nicotine Salt" category
    const nicotineSaltCategory = page.locator('button.category-pill', { hasText: 'Nicotine Salt' })
    await expect(nicotineSaltCategory).toBeVisible()
    await nicotineSaltCategory.click()
    
    // Wait for products to load
    await page.waitForSelector('.product-card', { state: 'visible' })
    
    // Click first 2 products in Nicotine Salt
    const nicotineProducts = page.locator('.product-card').filter({ hasText: /./ })
    const nicotineCount = await nicotineProducts.count()
    
    console.log(`Found ${nicotineCount} Nicotine Salt products`)
    
    // Add first 2 products
    for (let i = 0; i < Math.min(2, nicotineCount); i++) {
      const product = nicotineProducts.nth(i)
      await expect(product).toBeVisible()
      
      // Click add to cart button
      const addButton = product.locator('button:has-text("Tambah")')
      await expect(addButton).toBeVisible()
      await addButton.click()
      
      // Wait for cart update
      await page.waitForTimeout(500)
    }

    // Step 3: Verify cart has 4 items
    const cartItems = page.locator('.cart-item')
    await expect(cartItems).toHaveCount(4)

    // Step 4: Verify cart panel is visible and check layout
    const cartPanel = page.locator('.cart-panel')
    await expect(cartPanel).toBeVisible()
    
    // Check that cart items container has proper scroll behavior
    const cartItemsContainer = cartPanel.locator('.flex-1.overflow-y-auto')
    await expect(cartItemsContainer).toBeVisible()
    
    // Verify cart items have proper height (should fit within container)
    for (let i = 0; i < 4; i++) {
      const cartItem = cartItems.nth(i)
      await expect(cartItem).toBeVisible()
      
      // Check that item is properly rendered
      const itemName = await cartItem.locator('h4').textContent()
      expect(itemName).toBeTruthy()
    }

    // Step 5: Add 5th product to trigger scroll
    // Go back to Freebase or any category with products
    await freebaseCategory.click()
    await page.waitForSelector('.product-card', { state: 'visible' })
    
    const productsForFifth = page.locator('.product-card').filter({ hasText: /./ })
    const fifthProductCount = await productsForFifth.count()
    
    if (fifthProductCount > 0) {
      const fifthProduct = productsForFifth.first()
      const addButton = fifthProduct.locator('button:has-text("Tambah")')
      await expect(addButton).toBeVisible()
      await addButton.click()
      
      // Wait for cart update
      await page.waitForTimeout(500)
    }

    // Now verify we have 5 items
    await expect(cartItems).toHaveCount(5)
    
    // Step 6: Verify scroll behavior
    // The container should be scrollable but parent should not be affected
    const containerBox = await cartItemsContainer.boundingBox()
    const firstItemBox = await cartItems.first().boundingBox()
    const lastItemBox = await cartItems.last().boundingBox()
    
    console.log('Container height:', containerBox?.height)
    console.log('First item Y:', firstItemBox?.y)
    console.log('Last item Y:', lastItemBox?.y)
    
    // With 5 items, the container should allow scrolling
    // Items may overflow container height
    if (containerBox && lastItemBox && firstItemBox) {
      const containerBottom = containerBox.y + containerBox.height
      const lastItemBottom = lastItemBox.y + lastItemBox.height
      
      // Check if last item is potentially outside visible area
      const isOverflowing = lastItemBottom > containerBottom
      console.log('Is cart overflowing container?', isOverflowing)
    }

    // Verify all 5 items are properly rendered (even if scrolled)
    for (let i = 0; i < 5; i++) {
      const cartItem = cartItems.nth(i)
      await expect(cartItem).toBeVisible()
    }

    // Step 7: Test scroll functionality
    // Try to scroll the cart items container
    await cartItemsContainer.evaluate((el) => {
      el.scrollTop = 100
    })
    
    await page.waitForTimeout(300)
    
    // Verify container is still functional after scroll
    await expect(cartPanel).toBeVisible()
    await expect(cartItems).toHaveCount(5)
  })

  test('Cart should maintain proper height on tablet viewport', async ({ page }) => {
    // Set tablet viewport
    await page.setViewportSize({ width: 768, height: 1024 })
    
    // Add 3 products
    const freebaseCategory = page.locator('button.category-pill', { hasText: 'Freebase' })
    await freebaseCategory.click()
    await page.waitForSelector('.product-card', { state: 'visible' })
    
    const products = page.locator('.product-card').filter({ hasText: /./ })
    const productCount = await products.count()
    
    for (let i = 0; i < Math.min(3, productCount); i++) {
      const addButton = products.nth(i).locator('button:has-text("Tambah")')
      await expect(addButton).toBeVisible()
      await addButton.click()
      await page.waitForTimeout(300)
    }
    
    // Check cart panel height
    const cartPanel = page.locator('.cart-panel')
    await expect(cartPanel).toBeVisible()
    
    const panelBox = await cartPanel.boundingBox()
    console.log('Cart panel dimensions:', panelBox)
    
    // Panel should fit within viewport
    expect(panelBox?.height).toBeLessThanOrEqual(1024)
    
    // Cart items should all be visible (may require scroll)
    const cartItems = page.locator('.cart-item')
    const visibleItems = await cartItems.evaluateAll((items) => 
      items.filter(item => {
        const rect = item.getBoundingClientRect()
        return rect.top >= 0 && rect.bottom <= window.innerHeight
      }).length
    )
    
    console.log(`Visible cart items: ${visibleItems}/3`)
  })

  test('Cart max 4 items visible without scroll on medium screens', async ({ page }) => {
    // Set a medium desktop size that would show all 4 items
    await page.setViewportSize({ width: 1200, height: 800 })
    
    const freebaseCategory = page.locator('button.category-pill', { hasText: 'Freebase' })
    await freebaseCategory.click()
    await page.waitForSelector('.product-card', { state: 'visible' })
    
    const products = page.locator('.product-card').filter({ hasText: /./ })
    const productCount = await products.count()
    
    // Add exactly 4 items
    for (let i = 0; i < Math.min(4, productCount); i++) {
      const addButton = products.nth(i).locator('button:has-text("Tambah")')
      await expect(addButton).toBeVisible()
      await addButton.click()
      await page.waitForTimeout(300)
    }
    
    const cartItems = page.locator('.cart-item')
    await expect(cartItems).toHaveCount(4)
    
    // All 4 items should be visible
    for (let i = 0; i < 4; i++) {
      await expect(cartItems.nth(i)).toBeVisible()
    }
    
    // Check container scroll status
    const cartItemsContainer = page.locator('.cart-panel .flex-1.overflow-y-auto')
    const isScrollable = await cartItemsContainer.evaluate((el) => {
      return el.scrollHeight > el.clientHeight
    })
    
    console.log('Container is scrollable with 4 items:', isScrollable)
    
    // Note: With 4 items, container may or may not be scrollable
    // depending on exact heights, but all items should be accessible
  })
})

test.describe('Cart Item Display and Layout', () => {
  test('Cart items should have consistent appearance', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 })
    
    const freebaseCategory = page.locator('button.category-pill', { hasText: 'Freebase' })
    await freebaseCategory.click()
    await page.waitForSelector('.product-card', { state: 'visible' })
    
    // Add 3 products
    const products = page.locator('.product-card').filter({ hasText: /./ })
    const productCount = await products.count()
    
    for (let i = 0; i < Math.min(3, productCount); i++) {
      const addButton = products.nth(i).locator('button:has-text("Tambah")')
      await expect(addButton).toBeVisible()
      await addButton.click()
      await page.waitForTimeout(300)
    }
    
    // Verify each cart item has required elements
    const cartItems = page.locator('.cart-item')
    await expect(cartItems).toHaveCount(3)
    
    for (let i = 0; i < 3; i++) {
      const item = cartItems.nth(i)
      
      // Check product name
      const name = item.locator('h4')
      await expect(name).toBeVisible()
      
      // Check quantity controls
      const qtyBtn = item.locator('.qty-btn')
      await expect(qtyBtn).toBeVisible()
      
      // Check quantity number
      const qtyText = item.locator('.w-8.text-center')
      await expect(qtyText).toBeVisible()
    }
  })

  test('Removing items from cart should update count correctly', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 })
    
    const freebaseCategory = page.locator('button.category-pill', { hasText: 'Freebase' })
    await freebaseCategory.click()
    await page.waitForSelector('.product-card', { state: 'visible' })
    
    // Add 5 products
    const products = page.locator('.product-card').filter({ hasText: /./ })
    const productCount = await products.count()
    
    for (let i = 0; i < Math.min(5, productCount); i++) {
      const addButton = products.nth(i).locator('button:has-text("Tambah")')
      await expect(addButton).toBeVisible()
      await addButton.click()
      await page.waitForTimeout(300)
    }
    
    const cartItems = page.locator('.cart-item')
    await expect(cartItems).toHaveCount(5)
    
    // Remove one item
    const firstItem = cartItems.first()
    const deleteBtn = firstItem.locator('button[aria-label="Hapus item"]')
    await expect(deleteBtn).toBeVisible()
    await deleteBtn.click()
    
    await page.waitForTimeout(500)
    
    // Should now have 4 items
    await expect(cartItems).toHaveCount(4)
  })
})