# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: pos-sync-analysis.test.ts >> POS Payment Synchronization Analysis >> check transaction sync after payment
- Location: tests\e2e\pos-sync-analysis.test.ts:222:3

# Error details

```
Test timeout of 30000ms exceeded.
```

```
Error: page.goto: net::ERR_ABORTED; maybe frame was detached?
Call log:
  - navigating to "http://localhost:8000/pos/transactions/today", waiting until "load"

```

# Test source

```ts
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
  199 |           body: JSON.stringify(testData)
  200 |         })
  201 |         
  202 |         return {
  203 |           status: res.status,
  204 |           statusText: res.statusText,
  205 |           body: await res.json().catch(() => null)
  206 |         }
  207 |       } catch (error) {
  208 |         return { error: error.message }
  209 |       }
  210 |     }, csrfToken, products)
  211 |     
  212 |     console.log('Payment API Response:', JSON.stringify(response, null, 2))
  213 |     
  214 |     // Analyze response for issues
  215 |     if (response.status === 422) {
  216 |       console.log('Validation error detected!')
  217 |     } else if (response.status === 200) {
  218 |       console.log('Payment successful!')
  219 |     }
  220 |   })
  221 | 
  222 |   test('check transaction sync after payment', async ({ page }) => {
  223 |     // Login
  224 |     await page.goto('/login')
  225 |     await page.fill('input[name="email"]', 'cashier@vape.com')
  226 |     await page.fill('input[name="password"]', 'cashier123')
  227 |     await page.click('button[type="submit"]')
  228 |     await page.waitForLoadState('networkidle')
  229 |     
  230 |     // Get initial transaction count
> 231 |     await page.goto('/pos/transactions/today')
      |                ^ Error: page.goto: net::ERR_ABORTED; maybe frame was detached?
  232 |     await page.waitForLoadState('networkidle')
  233 |     
  234 |     const initialCount = await page.locator('tbody tr').count()
  235 |     console.log('Initial transaction count:', initialCount)
  236 |     
  237 |     // Make a payment via API
  238 |     const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content') || ''
  239 |     
  240 |     // Get a product ID from dashboard
  241 |     await page.goto('/pos/dashboard')
  242 |     await page.waitForLoadState('networkidle')
  243 |     
  244 |     const products = await page.evaluate(() => {
  245 |       const elements = document.querySelectorAll('[data-product-id], .product-card')
  246 |       if (elements.length > 0) {
  247 |         return elements[0].getAttribute('data-product-id') || elements[0].getAttribute('data-id')
  248 |       }
  249 |       return null
  250 |     })
  251 |     
  252 |     if (products) {
  253 |       const result = await page.evaluate(async (token, productId) => {
  254 |         try {
  255 |           const res = await fetch('/pos/payment/process', {
  256 |             method: 'POST',
  257 |             headers: {
  258 |               'Content-Type': 'application/json',
  259 |               'X-Requested-With': 'XMLHttpRequest',
  260 |               'X-CSRF-TOKEN': token,
  261 |             },
  262 |             body: JSON.stringify({
  263 |               items: [{
  264 |                 product_id: productId,
  265 |                 quantity: 1,
  266 |                 unit_price: 50000,
  267 |                 discount: 0,
  268 |                 total: 50000
  269 |               }],
  270 |               total_amount: 50000,
  271 |               paid_amount: 50000,
  272 |               discount_amount: 0,
  273 |               tax_amount: 0,
  274 |               payment_method: 'cash'
  275 |             })
  276 |           })
  277 |           return await res.json()
  278 |         } catch (error) {
  279 |           return { error: error.message }
  280 |         }
  281 |       }, csrfToken, products)
  282 |       
  283 |       console.log('Payment result:', result)
  284 |       
  285 |       // Refresh report
  286 |       await page.goto('/pos/transactions/today')
  287 |       await page.waitForLoadState('networkidle')
  288 |       
  289 |       const newCount = await page.locator('tbody tr').count()
  290 |       console.log('New transaction count:', newCount)
  291 |       
  292 |       if (result.success && newCount > initialCount) {
  293 |         console.log('SUCCESS: Transaction synchronized!')
  294 |       } else if (result.success && newCount === initialCount) {
  295 |         console.log('SYNC ISSUE: Payment succeeded but transaction not visible in report')
  296 |       }
  297 |     }
  298 |   })
  299 | })
```