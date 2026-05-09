import { test, expect } from '@playwright/test'

/**
 * POS Payment and Transaction Report Synchronization Test
 * 
 * Credentials: cashier@vape.com / cashier123
 */
test.describe('POS Payment Synchronization Analysis', () => {
  // Collect console errors for debugging
  const consoleErrors: string[] = []

  test.beforeEach(async ({ page }) => {
    // Collect all console errors
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(`[${msg.location().url}] ${msg.text()}`)
      }
    })
    page.on('pageerror', err => {
      consoleErrors.push(`Page Error: ${err.message}`)
    })
  })

  test('login and analyze - check POS dashboard page structure', async ({ page }) => {
    // Login first
    await page.goto('/login')
    await page.fill('input[name="email"]', 'cashier@vape.com')
    await page.fill('input[name="password"]', 'cashier123')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    
    // Navigate to POS dashboard
    await page.goto('/pos/dashboard')
    await page.waitForLoadState('networkidle')
    
    // Check if the page loaded successfully
    const currentUrl = page.url()
    console.log('Current URL after login:', currentUrl)
    
    // Take screenshot for analysis
    await page.screenshot({ path: 'test-results/pos-dashboard.png', fullPage: true })
    
    // Log page structure
    const pageInfo = await page.evaluate(() => {
      return {
        title: document.title,
        url: window.location.href,
        hasProductGrid: !!document.querySelector('.product-grid, [data-testid="product-grid"], .product-card'),
        hasCart: !!document.querySelector('.cart-panel, [data-testid="cart"], aside'),
        buttons: Array.from(document.querySelectorAll('button')).map(b => b.textContent?.trim().substring(0, 30)),
        productCards: document.querySelectorAll('.product-card, [data-testid="product-card"]').length,
      }
    })
    
    console.log('Page Info:', JSON.stringify(pageInfo, null, 2))
    console.log('Console Errors:', consoleErrors)
    
    expect(currentUrl).toContain('/pos/dashboard')
  })

  test('analyze - check transaction report page structure', async ({ page }) => {
    // Login
    await page.goto('/login')
    await page.fill('input[name="email"]', 'cashier@vape.com')
    await page.fill('input[name="password"]', 'cashier123')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    
    await page.goto('/pos/transactions/today')
    await page.waitForLoadState('networkidle')
    
    await page.screenshot({ path: 'test-results/transaction-report.png', fullPage: true })
    
    const pageInfo = await page.evaluate(() => {
      return {
        title: document.title,
        url: window.location.href,
        hasReportContainer: !!document.querySelector('.pos-report-container'),
        hasTransactionTable: !!document.querySelector('table'),
        hasSummaryCards: !!document.querySelector('.grid'),
        visibleText: document.body.innerText.substring(0, 200),
      }
    })
    
    console.log('Report Page Info:', JSON.stringify(pageInfo, null, 2))
    
    expect(pageInfo.hasReportContainer || pageInfo.hasTransactionTable).toBeTruthy()
  })

  test('analyze - add product to cart and process payment', async ({ page }) => {
    // Login
    await page.goto('/login')
    await page.fill('input[name="email"]', 'cashier@vape.com')
    await page.fill('input[name="password"]', 'cashier123')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    
    await page.goto('/pos/dashboard')
    await page.waitForLoadState('networkidle')
    
    // Find and click a product
    await page.waitForSelector('body', { timeout: 5000 })
    
    // Try to find add to cart buttons
    const addToCartButtons = await page.locator('button:has-text("+"), [data-testid="add-to-cart"], button[class*="cart"], button[class*="add"]').all()
    console.log(`Found ${addToCartButtons.length} potential add-to-cart buttons`)
    
    if (addToCartButtons.length > 0) {
      await addToCartButtons[0].click()
      await page.waitForTimeout(1000)
      
      // Check if cart updated
      const cartCount = await page.locator('.bg-red-500, .cart-count, [data-testid="cart-count"]').textContent().catch(() => '0')
      console.log('Cart count after adding item:', cartCount)
    }
    
    // Click process payment
    const processPaymentBtn = page.locator('button:has-text("Bayar"), button:has-text("Pay"), [data-testid="process-payment"]')
    
    if (await processPaymentBtn.isVisible()) {
      await processPaymentBtn.click()
      await page.waitForSelector('[role="dialog"], .payment-modal', { timeout: 5000 })
      
      // Select cash payment
      const cashOption = page.locator('text=Tunai, text=Cash, [data-value="cash"]').first()
      if (await cashOption.isVisible()) {
        await cashOption.click()
      }
      
      // Enter cash amount
      const cashInput = page.locator('input[name="cash_received"], input[placeholder*="Tunai"], input[type="number"]').first()
      if (await cashInput.isVisible()) {
        await cashInput.fill('100000')
      }
      
      // Confirm payment
      const confirmBtn = page.locator('button:has-text("Konfirmasi"), button:has-text("Confirm"), button:has-text("Bayar")').last()
      await confirmBtn.click()
      
      // Wait for response
      await page.waitForTimeout(3000)
      
      // Check for success/error
      const successMsg = await page.locator('text=Berhasil, text=Success, text=Sukses').isVisible().catch(() => false)
      const errorMsg = await page.locator('text=Gagal, text=Error, text=error').isVisible().catch(() => false)
      
      console.log('Payment Result - Success:', successMsg, 'Error:', errorMsg)
      console.log('Console Errors:', consoleErrors)
    }
  })

  test('analyze - test payment API with valid data', async ({ page }) => {
    // Login
    await page.goto('/login')
    await page.fill('input[name="email"]', 'cashier@vape.com')
    await page.fill('input[name="password"]', 'cashier123')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    
    // Get products from the page
    const products = await page.evaluate(() => {
      const productElements = document.querySelectorAll('[data-product-id], .product-card')
      return Array.from(productElements).map(el => {
        const id = el.getAttribute('data-product-id') || el.getAttribute('data-id')
        return id
      }).filter(Boolean)
    })
    
    console.log('Available products:', products)
    
    // Get CSRF token
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content') || ''
    
    // Test payment endpoint with actual product IDs
    const response = await page.evaluate(async (token, productIds) => {
      const testData = {
        items: productIds.length > 0 ? [{
          product_id: productIds[0],
          quantity: 1,
          unit_price: 50000,
          discount: 0,
          total: 50000
        }] : [],
        total_amount: 50000,
        paid_amount: 50000,
        discount_amount: 0,
        tax_amount: 0,
        payment_method: 'cash'
      }
      
      try {
        const res = await fetch('/pos/payment/process', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
          },
          body: JSON.stringify(testData)
        })
        
        return {
          status: res.status,
          statusText: res.statusText,
          body: await res.json().catch(() => null)
        }
      } catch (error) {
        return { error: error.message }
      }
    }, csrfToken, products)
    
    console.log('Payment API Response:', JSON.stringify(response, null, 2))
    
    // Analyze response for issues
    if (response.status === 422) {
      console.log('Validation error detected!')
    } else if (response.status === 200) {
      console.log('Payment successful!')
    }
  })

  test('check transaction sync after payment', async ({ page }) => {
    // Login
    await page.goto('/login')
    await page.fill('input[name="email"]', 'cashier@vape.com')
    await page.fill('input[name="password"]', 'cashier123')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    
    // Get initial transaction count
    await page.goto('/pos/transactions/today')
    await page.waitForLoadState('networkidle')
    
    const initialCount = await page.locator('tbody tr').count()
    console.log('Initial transaction count:', initialCount)
    
    // Make a payment via API
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content') || ''
    
    // Get a product ID from dashboard
    await page.goto('/pos/dashboard')
    await page.waitForLoadState('networkidle')
    
    const products = await page.evaluate(() => {
      const elements = document.querySelectorAll('[data-product-id], .product-card')
      if (elements.length > 0) {
        return elements[0].getAttribute('data-product-id') || elements[0].getAttribute('data-id')
      }
      return null
    })
    
    if (products) {
      const result = await page.evaluate(async (token, productId) => {
        try {
          const res = await fetch('/pos/payment/process', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
              items: [{
                product_id: productId,
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
          return await res.json()
        } catch (error) {
          return { error: error.message }
        }
      }, csrfToken, products)
      
      console.log('Payment result:', result)
      
      // Refresh report
      await page.goto('/pos/transactions/today')
      await page.waitForLoadState('networkidle')
      
      const newCount = await page.locator('tbody tr').count()
      console.log('New transaction count:', newCount)
      
      if (result.success && newCount > initialCount) {
        console.log('SUCCESS: Transaction synchronized!')
      } else if (result.success && newCount === initialCount) {
        console.log('SYNC ISSUE: Payment succeeded but transaction not visible in report')
      }
    }
  })
})