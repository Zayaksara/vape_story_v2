# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: pos-sync-analysis.test.ts >> POS Payment Synchronization Analysis >> analyze - add product to cart and process payment
- Location: tests\e2e\pos-sync-analysis.test.ts:90:3

# Error details

```
Error: page.goto: net::ERR_INSUFFICIENT_RESOURCES at http://localhost:8000/pos/dashboard
Call log:
  - navigating to "http://localhost:8000/pos/dashboard", waiting until "load"

```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test'
  2   | 
  3   | /**
  4   |  * POS Payment and Transaction Report Synchronization Test
  5   |  * 
  6   |  * Credentials: cashier@vape.com / cashier123
  7   |  */
  8   | test.describe('POS Payment Synchronization Analysis', () => {
  9   |   // Collect console errors for debugging
  10  |   const consoleErrors: string[] = []
  11  | 
  12  |   test.beforeEach(async ({ page }) => {
  13  |     // Collect all console errors
  14  |     page.on('console', msg => {
  15  |       if (msg.type() === 'error') {
  16  |         consoleErrors.push(`[${msg.location().url}] ${msg.text()}`)
  17  |       }
  18  |     })
  19  |     page.on('pageerror', err => {
  20  |       consoleErrors.push(`Page Error: ${err.message}`)
  21  |     })
  22  |   })
  23  | 
  24  |   test('login and analyze - check POS dashboard page structure', async ({ page }) => {
  25  |     // Login first
  26  |     await page.goto('/login')
  27  |     await page.fill('input[name="email"]', 'cashier@vape.com')
  28  |     await page.fill('input[name="password"]', 'cashier123')
  29  |     await page.click('button[type="submit"]')
  30  |     await page.waitForLoadState('networkidle')
  31  |     
  32  |     // Navigate to POS dashboard
  33  |     await page.goto('/pos/dashboard')
  34  |     await page.waitForLoadState('networkidle')
  35  |     
  36  |     // Check if the page loaded successfully
  37  |     const currentUrl = page.url()
  38  |     console.log('Current URL after login:', currentUrl)
  39  |     
  40  |     // Take screenshot for analysis
  41  |     await page.screenshot({ path: 'test-results/pos-dashboard.png', fullPage: true })
  42  |     
  43  |     // Log page structure
  44  |     const pageInfo = await page.evaluate(() => {
  45  |       return {
  46  |         title: document.title,
  47  |         url: window.location.href,
  48  |         hasProductGrid: !!document.querySelector('.product-grid, [data-testid="product-grid"], .product-card'),
  49  |         hasCart: !!document.querySelector('.cart-panel, [data-testid="cart"], aside'),
  50  |         buttons: Array.from(document.querySelectorAll('button')).map(b => b.textContent?.trim().substring(0, 30)),
  51  |         productCards: document.querySelectorAll('.product-card, [data-testid="product-card"]').length,
  52  |       }
  53  |     })
  54  |     
  55  |     console.log('Page Info:', JSON.stringify(pageInfo, null, 2))
  56  |     console.log('Console Errors:', consoleErrors)
  57  |     
  58  |     expect(currentUrl).toContain('/pos/dashboard')
  59  |   })
  60  | 
  61  |   test('analyze - check transaction report page structure', async ({ page }) => {
  62  |     // Login
  63  |     await page.goto('/login')
  64  |     await page.fill('input[name="email"]', 'cashier@vape.com')
  65  |     await page.fill('input[name="password"]', 'cashier123')
  66  |     await page.click('button[type="submit"]')
  67  |     await page.waitForLoadState('networkidle')
  68  |     
  69  |     await page.goto('/pos/transactions/today')
  70  |     await page.waitForLoadState('networkidle')
  71  |     
  72  |     await page.screenshot({ path: 'test-results/transaction-report.png', fullPage: true })
  73  |     
  74  |     const pageInfo = await page.evaluate(() => {
  75  |       return {
  76  |         title: document.title,
  77  |         url: window.location.href,
  78  |         hasReportContainer: !!document.querySelector('.pos-report-container'),
  79  |         hasTransactionTable: !!document.querySelector('table'),
  80  |         hasSummaryCards: !!document.querySelector('.grid'),
  81  |         visibleText: document.body.innerText.substring(0, 200),
  82  |       }
  83  |     })
  84  |     
  85  |     console.log('Report Page Info:', JSON.stringify(pageInfo, null, 2))
  86  |     
  87  |     expect(pageInfo.hasReportContainer || pageInfo.hasTransactionTable).toBeTruthy()
  88  |   })
  89  | 
  90  |   test('analyze - add product to cart and process payment', async ({ page }) => {
  91  |     // Login
  92  |     await page.goto('/login')
  93  |     await page.fill('input[name="email"]', 'cashier@vape.com')
  94  |     await page.fill('input[name="password"]', 'cashier123')
  95  |     await page.click('button[type="submit"]')
  96  |     await page.waitForLoadState('networkidle')
  97  |     
> 98  |     await page.goto('/pos/dashboard')
      |                ^ Error: page.goto: net::ERR_INSUFFICIENT_RESOURCES at http://localhost:8000/pos/dashboard
  99  |     await page.waitForLoadState('networkidle')
  100 |     
  101 |     // Find and click a product
  102 |     await page.waitForSelector('body', { timeout: 5000 })
  103 |     
  104 |     // Try to find add to cart buttons
  105 |     const addToCartButtons = await page.locator('button:has-text("+"), [data-testid="add-to-cart"], button[class*="cart"], button[class*="add"]').all()
  106 |     console.log(`Found ${addToCartButtons.length} potential add-to-cart buttons`)
  107 |     
  108 |     if (addToCartButtons.length > 0) {
  109 |       await addToCartButtons[0].click()
  110 |       await page.waitForTimeout(1000)
  111 |       
  112 |       // Check if cart updated
  113 |       const cartCount = await page.locator('.bg-red-500, .cart-count, [data-testid="cart-count"]').textContent().catch(() => '0')
  114 |       console.log('Cart count after adding item:', cartCount)
  115 |     }
  116 |     
  117 |     // Click process payment
  118 |     const processPaymentBtn = page.locator('button:has-text("Bayar"), button:has-text("Pay"), [data-testid="process-payment"]')
  119 |     
  120 |     if (await processPaymentBtn.isVisible()) {
  121 |       await processPaymentBtn.click()
  122 |       await page.waitForSelector('[role="dialog"], .payment-modal', { timeout: 5000 })
  123 |       
  124 |       // Select cash payment
  125 |       const cashOption = page.locator('text=Tunai, text=Cash, [data-value="cash"]').first()
  126 |       if (await cashOption.isVisible()) {
  127 |         await cashOption.click()
  128 |       }
  129 |       
  130 |       // Enter cash amount
  131 |       const cashInput = page.locator('input[name="cash_received"], input[placeholder*="Tunai"], input[type="number"]').first()
  132 |       if (await cashInput.isVisible()) {
  133 |         await cashInput.fill('100000')
  134 |       }
  135 |       
  136 |       // Confirm payment
  137 |       const confirmBtn = page.locator('button:has-text("Konfirmasi"), button:has-text("Confirm"), button:has-text("Bayar")').last()
  138 |       await confirmBtn.click()
  139 |       
  140 |       // Wait for response
  141 |       await page.waitForTimeout(3000)
  142 |       
  143 |       // Check for success/error
  144 |       const successMsg = await page.locator('text=Berhasil, text=Success, text=Sukses').isVisible().catch(() => false)
  145 |       const errorMsg = await page.locator('text=Gagal, text=Error, text=error').isVisible().catch(() => false)
  146 |       
  147 |       console.log('Payment Result - Success:', successMsg, 'Error:', errorMsg)
  148 |       console.log('Console Errors:', consoleErrors)
  149 |     }
  150 |   })
  151 | 
  152 |   test('analyze - test payment API with valid data', async ({ page }) => {
  153 |     // Login
  154 |     await page.goto('/login')
  155 |     await page.fill('input[name="email"]', 'cashier@vape.com')
  156 |     await page.fill('input[name="password"]', 'cashier123')
  157 |     await page.click('button[type="submit"]')
  158 |     await page.waitForLoadState('networkidle')
  159 |     
  160 |     // Get products from the page
  161 |     const products = await page.evaluate(() => {
  162 |       const productElements = document.querySelectorAll('[data-product-id], .product-card')
  163 |       return Array.from(productElements).map(el => {
  164 |         const id = el.getAttribute('data-product-id') || el.getAttribute('data-id')
  165 |         return id
  166 |       }).filter(Boolean)
  167 |     })
  168 |     
  169 |     console.log('Available products:', products)
  170 |     
  171 |     // Get CSRF token
  172 |     const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content') || ''
  173 |     
  174 |     // Test payment endpoint with actual product IDs
  175 |     const response = await page.evaluate(async (token, productIds) => {
  176 |       const testData = {
  177 |         items: productIds.length > 0 ? [{
  178 |           product_id: productIds[0],
  179 |           quantity: 1,
  180 |           unit_price: 50000,
  181 |           discount: 0,
  182 |           total: 50000
  183 |         }] : [],
  184 |         total_amount: 50000,
  185 |         paid_amount: 50000,
  186 |         discount_amount: 0,
  187 |         tax_amount: 0,
  188 |         payment_method: 'cash'
  189 |       }
  190 |       
  191 |       try {
  192 |         const res = await fetch('/pos/payment/process', {
  193 |           method: 'POST',
  194 |           headers: {
  195 |             'Content-Type': 'application/json',
  196 |             'X-Requested-With': 'XMLHttpRequest',
  197 |             'X-CSRF-TOKEN': token,
  198 |           },
```