# E2E Tests for Cart Item Limit & Overflow Behavior

## Description
This test suite verifies the cart functionality in the POS system, specifically focusing on:
- Cart item limit (max 4 items visible without scroll)
- Overflow behavior (5th item causes scroll in cart items container)
- Tablet/iPad viewport compatibility
- Category switching (Freebase → Nicotine Salt)

## Test Files

### `tests/e2e/cart-overflow.test.ts`
Main test file for cart overflow behavior.

### `tests/e2e/cart-item-limit.test.ts`
Alternative test file with additional test cases.

### `tests/fixtures/pos-data.ts`
Mock data for POS products and categories.

## Test Scenarios

### TC-001: Add 4 products (2 from Freebase, 2 from Nicotine Salt)
1. Select Freebase category
2. Add 2 products to cart
3. Switch to Nicotine Salt category
4. Add 2 products to cart
5. Verify cart displays 4 items
6. Verify items fit within tablet height
7. Verify cart panel structure

### TC-002: Add 5th product and verify scroll behavior
1. Add 5 products total
2. Verify cart has 5 items
3. Verify cart items container is scrollable (`overflow-y: auto`)
4. Verify cart parent does NOT scroll (`overflow-y: visible`)
5. Verify scroll is contained within cart items container

### TC-003: Cart max 4 items visible on iPad viewport
1. Set iPad viewport (768x1024)
2. Add exactly 4 products
3. Verify all 4 items visible
4. Verify cart panel fits within screen height

### TC-004: Cart item removal maintains proper count
1. Add 5 products
2. Remove 1 item
3. Verify 4 items remain
4. Verify all remaining items visible

### TC-005: Cart item quantity update maintains layout
1. Add 4 products
2. Increase quantity of first item
3. Verify layout intact
4. Decrease quantity back
5. Verify layout intact

## Running the Tests

### Install Playwright
```bash
npm install --save-dev @playwright/test
```

### Install Browsers
```bash
npx playwright install
```

### Run Tests

#### Run all tests
```bash
npx playwright test
```

#### Run specific test file
```bash
npx playwright test tests/e2e/cart-overflow.test.ts
```

#### Run with headed mode (watch mode)
```bash
npx playwright test --headed
```

#### Run with tracing
```bash
npx playwright test --trace on
```

#### Run tests on mobile viewport
```bash
npx playwright test --project="Mobile Chrome"
```

#### Run tests on iPad viewport
```bash
npx playwright test --project="iPad"
```

## Configuration

The `playwright.config.ts` file configures:
- Viewports for different devices (Desktop, Mobile, iPad)
- Web server to auto-start Laravel dev server
- Trace and screenshot settings
- Test directory

## Expected Behavior

### Cart Item Display
- Cart items should be limited to **max 4 items** visible without scrolling
- Cart items container should have `overflow-y: auto` for scroll capability
- Cart parent (main panel) should NOT scroll when cart items overflow
- Each cart item should have:
  - Product image
  - Product name
  - Price per unit
  - Quantity controls (increase/decrease/remove)
  - Subtotal display

### Cart Container Styles
```css
.cart-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden; /* Parent should not scroll */
}

.cart-items-container {
  flex: 1;
  overflow-y: auto; /* Only this scrolls */
  min-height: 0;
}
```

## Debugging

### View Trace Report
```bash
npx playwright show-trace trace.zip
```

### View HTML Report
```bash
npx playwright show-report
```

### Run with Console Logs
```bash
npx playwright test --debug
```

## Troubleshooting

### Tests fail due to missing products
- Ensure database has products in Freebase and Nicotine Salt categories
- Check category IDs match test expectations
- Verify products are active and in stock

### Cart items not visible
- Check cart panel z-index and positioning
- Verify cart items container has proper height constraints
- Check for CSS overflow properties on parent elements

### Scroll behavior not working
- Verify `overflow-y: auto` on cart items container
- Check that cart parent does NOT have `overflow-y: scroll`
- Ensure flex layout is properly configured

## Test Data Setup

For tests to pass, ensure you have:
- At least 2 products in Freebase category
- At least 2 products in Nicotine Salt category
- Products should be in stock and visible

## Browser Compatibility

Tests run on:
- Chromium (Chrome/Edge)
- Firefox
- WebKit (Safari)
- Mobile Chrome
- Mobile Safari
- iPad (gen 7+)

## Notes

- Tests use Playwright's built-in auto-waiting mechanisms
- Each test is isolated and runs in a fresh browser context
- Cart state is not persisted between tests (fresh page load each time)
- Timeout is set to 10 seconds for most operations
- Network idle state is waited for after category switches
