## Plan: Minimalist POS Product and Transaction Pages

TL;DR: Build two new POS view-mode pages under the existing `POS` section using Tailwind + Shadcn UI, driven by local mock JSON data. Keep styling strictly minimalist by reusing `resources/css/app.css` theme variables, low-noise borders, and generous spacing.

**Steps**
1. Phase 1: Design system and global styling
   - Audit `resources/css/app.css` and reuse existing CSS variables: `--background`, `--foreground`, `--primary`, `--border`, `--input`, `--radius`, and the muted colors.
   - Define a minimalist token subset in CSS if needed, such as a `--minimal-surface`, `--minimal-border`, `--minimal-muted`, and `--minimal-accent` mapping to the current root variables.
   - Ensure page containers and cards use Tailwind utility classes aligned with the theme: `bg-background`, `text-foreground`, `border border-muted`, `rounded-[var(--radius)]`, `shadow-sm`, `p-8`, `gap-6`, `space-y-6`.
   - Anchor typography with the existing sans-serif stack from `app.css` and enforce hierarchy using `text-2xl`, `text-xl`, `font-semibold`, `text-muted-foreground`, and `text-sm`.

2. Phase 2: Product Management page
   - Use `resources/js/pages/POS/ProductPos.vue` as the page container.
   - Add a top header strip with a page title and a Shadcn `Input` search field.
   - Build a reusable `resources/js/components/pos/ProductCard.vue` component using Shadcn `Card` and `Badge`.
   - Render cards in a responsive grid using classes such as `grid gap-6 sm:grid-cols-2 xl:grid-cols-4` and `min-h-[220px]` cards.
   - Each card should include:
     - thumbnail using aspect-square layout
     - product name in `font-semibold`
     - category in `text-muted-foreground`
     - price in `font-medium`
     - stock badge or quick-view label with low-saturation accent styling
   - Prefer `shadow-sm border` rather than heavy shadows. Keep backgrounds white or very light.
   - Compose a simple inline filter/search state in the page using local mock data.

3. Phase 3: Daily Transaction History page
   - Use `resources/js/pages/POS/ReportTodayTransaction.vue` as the page container.
   - Add a header with title and a summary row of two stat cards for `Total Sales` and `Total Orders`.
   - Build a `resources/js/components/pos/TransactionTable.vue` component using existing Shadcn `Table` wrappers.
   - Structure the table with columns: Timestamp, Transaction ID, Customer, Items, Total, Status.
   - Add status chips using `Badge` with toned semantic backgrounds (success, warning, neutral) and low visual saturation.
   - Create an empty state section with a subtle icon, muted explanatory text, and a small callout style using the minimalist theme.

4. Phase 4: Technical execution workflow
   - Create a local mock data file for products and transactions. Suggested location: `resources/js/data/pos/mockData.ts` or separate `products.ts` and `transactions.ts`.
   - Keep the page components self-contained for view mode, with static arrays and computed filtered results.
   - Implement reusable presentational components first: `ProductCard.vue`, `TransactionStats.vue`, `TransactionTable.vue`, and `EmptyState.vue`.
   - Assemble the page layouts with shared wrapper classes and spacing utilities.
   - Update POS navigation in `resources/js/components/pos/PosSidebar.vue` to point to the new pages, and create corresponding route helpers under `resources/js/routes/pos` if needed.
   - Optionally add backend route definitions in `routes/web.php` for `/pos/products` and `/pos/transactions` if the pages should be navigable through the current app.

**Relevant files**
- `resources/css/app.css` — use theme variables and spacing tokens.
- `resources/js/pages/POS/ProductPos.vue` — Product Management page.
- `resources/js/pages/POS/ReportTodayTransaction.vue` — Transaction History page.
- `resources/js/components/pos/ProductCard.vue` — reusable product card component.
- `resources/js/components/pos/TransactionTable.vue` — reusable transaction table component.
- `resources/js/components/pos/TransactionStats.vue` — daily sales summary card component.
- `resources/js/components/pos/EmptyState.vue` — fallback for no transactions.
- `resources/js/data/pos/products.ts` and `resources/js/data/pos/transactions.ts` — mock JSON data.
- `routes/web.php` — POS page route registration.
- `resources/js/components/pos/PosSidebar.vue` — sidebar navigation update.

**Verification**
1. Open the new pages in the browser and verify the layout uses broad whitespace, muted borders, and low-contrast cards.
2. Confirm the Shadcn `Input`, `Card`, `Table`, and `Badge` wrappers render consistently with the existing theme.
3. Validate that `ProductPos.vue` shows a responsive grid and `ReportTodayTransaction.vue` shows the stats row and a clean table.
4. Check the empty transaction state by temporarily setting the transaction array to empty.
5. Confirm the pages reuse `app.css` variables and do not introduce new hardcoded colors outside the minimalist palette.

**Decisions / assumptions**
- Assume these pages should integrate into the current POS navigation and route system.
- Assume view-mode only with local mock data rather than backend-driven dynamic data in this phase.
- Assume existing Shadcn wrappers are used from `@/components/ui/*` and Tailwind utility classes are sufficient.

**Further considerations**
1. If the app needs the product page to eventually support backend pagination, keep the card grid and search header componentized.
2. If the transaction table should support sorting later, implement the table rows with a stable key and column header variant.
3. Decide whether the accent color remains the current teal (`--primary`) or should be further muted for a stricter minimalist palette.