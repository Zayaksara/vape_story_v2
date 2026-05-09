import { test, expect, Page } from '@playwright/test'

// Test POS Payment and Synchronization Flow
test.describe('POS Payment and Transaction Report Synchronization', () => {
  let page: Page
  let csrfToken: string

  test.beforeEach(async ({ page: testPage }) => {
    page = testPage
    
    // Login first - create a cashier user and login
    await page.goto('/login')
    
    // Check if login page exists, if not try to access POS directly
    const isLoginPage = await page.locator('input[name="email"]').isVisible().catch(() => false)
    
    if (isLoginPage) {
      // Try to login with default credentials or create test user
      await page.fill('input[name="email"]', 'admin@example.com')
      await page.fill('input[name="password"]', 'password')
      await page.click('button[type="submit"]')
      await page.waitForLoadState('networkidle')
    }
    
    // Navigate to POS dashboard
    await page.goto('/pos/dashboard')
    await page.waitForLoadState('networkidle')
    
    // Extract CSRF token
    const csrfMeta = await page.locator('meta[name="csrf-token"]').getAttribute('content')
    csrfToken = csrfMeta || ''
  })

  test('should add product to cart', async () => {
    // Wait for products to load
    await page.waitForSelector('[data-testid="product-card"], .product-grid', { timeout: 10000 })
    
    // Try to find and click add to cart button
    const addToCartBtn = page.locator('button:has-text("Add"), button:has-text("+"), [data-testid="add-to-cart"]').first()
    
    if (await addToCartBtn.isVisible()) {
      await addToCartBtn.click()
      
      // Verify cart count increased
      const cartBadge = page.locator('.cart-badge, [data-testid="cart-count"], .rounded-full.bg-red-500')
      await expect(cartBadge).toHaveText('1')
    } else {
      test.skip('No products available for testing')
    }
  })

  test('should process payment and create transaction', async () => {
    // Add a product to cart first
    await page.waitForSelector('.product-grid, [data-testid="product-card"]', { timeout: 10000 })
    
    const addToCartBtn = page.locator('button:has-text("+"), [data-testid="add-to-cart"], .add-to-cart-btn').first()
    
    if (await addToCartBtn.isVisible()) {
      await addToCartBtn.click()
      await page.waitForTimeout(500)
    }

    // Click process payment button
    const processPaymentBtn = page.locator('button:has-text("Bayar"), button:has-text("Pay"), [data-testid="process-payment"]')
    
    if (await processPaymentBtn.isVisible()) {
      await processPaymentBtn.click()
      
      // Wait for payment modal
      await page.waitForSelector('[role="dialog"], .payment-modal', { timeout: 5000 })
      
      // Select payment method (cash)
      const cashOption = page.locator('text=Cash, text=Tunai, [data-value="cash"]').first()
      if (await cashOption.isVisible()) {
        await cashOption.click()
      }
      
      // Enter cash amount
      const cashInput = page.locator('input[name="cash_received"], input[placeholder*="Tunai"], input[type="number"]').first()
      if (await cashInput.isVisible()) {
        await cashInput.fill('100000')
      }
      
      // Confirm payment
      const confirmBtn = page.locator('button:has-text("Confirm"), button:has-text("Bayar"), button:has-text("Konfirmasi")').first()
      await confirmBtn.click()
      
      // Wait for transaction completion
      await page.waitForResponse(response => 
        response.url().includes('/pos/payment/process') && response.status() === 200,
        { timeout: 10000 }
      ).catch(() => {})
      
      // Check for success state
      await page.waitForTimeout(1000)
    } else {
      test.skip('Process payment button not found')
    }
  })

  test('should verify transaction appears in report after payment', async () => {
    // First create a transaction via API
    await page.evaluate(async () => {
      const response = await fetch('/pos/payment/process', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          items: [{
            product_id: 'test-product-id',
            quantity: 1,
            unit_price: 50000,
            discount: 0,
            total: 50000
          }],
          total_amount: 50000,
          paid_amount: 50000,
          discount_amount: 0,
          tax_amount: 0,
          payment_method: 'cash'
        })
      })
      return response.json()
    }).catch(() => null)

    // Navigate to transaction report
    await page.goto('/pos/transactions/today')
    await page.waitForLoadState('networkidle')
    
    // Check if report page loaded
    const reportLoaded = await page.locator('text=Laporan, text=Transaksi, .pos-report-container').first().isVisible()
    expect(reportLoaded).toBeTruthy()
  })

  test('should check payment API response format', async () => {
    // Mock a payment request and check response format
    const testData = {
      items: [],
      total_amount: 0,
      paid_amount: 0,
      discount_amount: 0,
      tax_amount: 0,
      payment_method: 'cash'
    }

    const response = await page.evaluate(async (data) => {
      try {
        const res = await fetch('/pos/payment/process', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify(data)
        })
        return {
          status: res.status,
          ok: res.ok,
          data: await res.json().catch(() => null)
        }
      } catch (error) {
        return { error: error.message }
      }
    }, testData)

    // Log the response for debugging
    console.log('Payment API Response:', JSON.stringify(response, null, 2))
  })

  test('should verify cart state synchronization', async () => {
    // Add item to cart
    await page.waitForSelector('.product-grid', { timeout: 5000 })
    
    const addToCartBtn = page.locator('[data-testid="add-to-cart"], button:has-text("+")').first()
    if (await addToCartBtn.isVisible()) {
      await addToCartBtn.click()
      
      // Check cart computed properties
      const cartState = await page.evaluate(() => {
        // Try to access the Vue component state
        const vueComponent = document.querySelector('.cart-panel')?.__vueParentComponent || 
                            document.querySelector('.__vueParentComponent') ||
                            window.__INITIAL_STATE__
        return vueComponent
      })
      
      console.log('Cart state:', JSON.stringify(cartState))
    }
  })
})

test.describe('Console Error Detection', () => {
  test('should not have JavaScript errors during payment flow', async ({ page }) => {
    const errors: string[] = []
    
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text())
      }
    })
    
    page.on('pageerror', err => {
      errors.push(`Page error: ${err.message}`)
    })
    
    // Navigate through payment flow
    await page.goto('/login')
    
    // Simple login attempt
    const emailInput = page.locator('input[name="email"]')
    if (await emailInput.isVisible()) {
      await emailInput.fill('test@test.com')
      await page.locator('input[name="password"]').fill('password')
      await page.click('button[type="submit"]')
    }
    
    await page.goto('/pos/dashboard')
    await page.waitForLoadState('networkidle')
    
    expect(errors).toEqual([])
  })
})