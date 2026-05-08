# POS Today Transaction Report - Design Spec

**Date:** 2026-05-08
**Component:** `resources/js/pages/POS/ReportTodayTransaction.vue`
**Reference:** `ReportTodayTransaksi.jsx` (adapted to ecosystem)

## Overview
Create a daily sales report page that displays transaction data with statistics, search/filtering, and expandable transaction details. All data must be real (no mock data) and fully integrated with the existing POS ecosystem.

## Architecture

### Component Structure
```
ReportTodayTransaction.vue
├── Header Section
│   ├── Title + Date/Time Display
│   └── Action Buttons (Print, Export)
├── Statistics Grid (2x2)
│   ├── Total Transactions
│   ├── Total Sales
│   ├── Cash Total
│   └── Items Sold
└── Transaction Table
    ├── Search & Filter Controls
    ├── Table Header
    ├── Transaction Rows (expandable)
    └── Footer Summary
```

### Data Flow
1. **Props:** Receive from controller
   - `transactions: TransactionWithItems[]`
   - `summary: DailySummary`
   - `selectedDate: string`
   - `today: string`

2. **State Management:**
   - Search query for filtering
   - Payment method filter
   - Expanded transaction IDs
   - Loading state for date navigation
   - Current date (for navigation)

3. **Computed Properties:**
   - Filtered transactions (search + payment filter)
   - Formatted date/time displays
   - Payment method percentages

4. **User Actions:**
   - Date navigation (previous/next/today)
   - Search input
   - Payment method filter
   - Expand/collapse transaction details
   - Print report
   - Export data

## Components & Features

### 1. Header Section
**Layout:** Full-width gradient header
**Background:** `linear-gradient(135deg, #1e293b 0%, #334155 100%)`
**Content:**
- Title: "Laporan Penjualan Hari Ini"
- Date/time display (Indonesian locale)
- Print button (with Printer icon)
- Export button (with Download icon)

### 2. Statistics Cards (2x2 Grid)
**Layout:** CSS Grid `grid-cols-2` with responsive `minmax(200px, 1fr)`
**Card Structure:**
- Icon container with colored background
- Label (small, muted text)
- Value (large, bold)
- Subtitle (small, muted text)

**Card Types:**
1. **Total Transactions**
   - Icon: `ShoppingCart`
   - Color: `#14b8a6` (teal-500)
   - Value: Transaction count
   - Subtitle: "transaksi hari ini"

2. **Total Sales**
   - Icon: `DollarSign`
   - Color: `#6366f1` (indigo-500)
   - Value: Total sales amount (formatted IDR)
   - Subtitle: Average per transaction

3. **Cash Total**
   - Icon: `Wallet`
   - Color: `#f59e0b` (amber-500)
   - Value: Cash payment total (formatted IDR)
   - Subtitle: Cash transaction count

4. **Items Sold**
   - Icon: `Package`
   - Color: `#ef4444` (red-500)
   - Value: Total items sold
   - Subtitle: "total item"

### 3. Transaction Table
**Components:** Shadcn Table components (`Table`, `TableHeader`, `TableBody`, `TableRow`, `TableCell`, etc.)

**Columns:**
1. **Jam** - Time of transaction (HH:MM format)
2. **No. Struk** - Receipt/invoice number (monospace font)
3. **Pembayaran** - Payment method with icon
4. **Kasir** - Cashier name
5. **Jumlah** - Transaction total (right-aligned)
6. **Status** - Status badge (success/pending/failed)
7. **Expand** - Chevron icon (expandable indicator)

**Search & Filter:**
- Search input: Filter by receipt number or cashier name
- Payment filter dropdown: All, Cash, QRIS, Transfer, E-Wallet
- Real-time filtering (no submit button)

**Expandable Rows:**
- Click anywhere on row to expand/collapse
- Show individual line items
- Display: `qty x product_name = price`
- Separator between items
- Total highlighted in teal

**Footer:**
- Left: "Menampilkan X dari Y transaksi"
- Right: "Total: RpX.XXX.XXX" (filtered total)

## Design System Integration

### CSS Variables
Use existing POS theme variables from `resources/css/app.css`:
- `--pos-brand-primary: #14b8a6`
- `--pos-bg-secondary: #f9fafb`
- `--pos-text-secondary: #334155`
- `--pos-text-muted: #6b7280`
- `--pos-border: #e5e7eb`
- `--pos-success-bg: #dcfce7`
- `--pos-success-text: #16a34a`
- `--pos-warning-bg: #fef3c7`
- `--pos-warning-text: #d97706`

### Component Library
- **UI Framework:** Shadcn/ui components
- **Icons:** Lucide Vue (`lucide-vue-next`)
- **Routing:** Inertia.js
- **Styling:** Tailwind CSS with custom CSS variables
- **Typography:** Nunito Sans (already loaded)

### Status Colors
- **Success:** Green badge (`--pos-success-bg`, `--pos-success-text`)
- **Pending:** Amber badge (`--pos-warning-bg`, `--pos-warning-text`)
- **Failed:** Red badge (`--pos-danger-bg`, `--pos-danger-text`)

### Payment Method Icons
- **Cash:** `Wallet` icon
- **QRIS:** `QrCode` icon
- **Transfer:** `CreditCard` icon
- **E-Wallet:** `Smartphone` icon

## Data Requirements

### Backend Modifications
**Controller:** `app/Http/Controllers/POS/TodayTransactionController.php`
- Modify `payment_methods` array to return amounts instead of counts
- Calculate totals: `where('payment_method', 'X')->sum('total_amount')`

**Model:** `app/Models/Transaction.php`
- Already has correct relationships (cashier, items)
- Payment method enum handling
- Status enum handling

### Type Safety
**File:** `resources/js/types/pos.ts`
- Already defines required interfaces:
  - `TransactionWithItems`
  - `DailySummary`
  - `PaymentMethod`

## Responsive Design

### Breakpoints
- **Desktop (1024px+):** 2x2 stat grid, full table width
- **Tablet (768px-1023px):** 2x2 stat grid, scrollable table
- **Mobile (<768px):** 2x2 stat grid, horizontally scrollable table

### Touch Optimizations
- Minimum touch target: 44x44px
- Expandable rows work on tap
- Dropdown works on mobile

## Accessibility

### Requirements
- **Keyboard Navigation:** Tab order matches visual order
- **Focus States:** Visible focus rings (`--pos-border-focus`)
- **Screen Readers:** ARIA labels for icons
- **Color Contrast:** 4.5:1 minimum ratio
- **Reduced Motion:** Respect `prefers-reduced-motion`

### Implementation
- `aria-label` for icon-only buttons
- `role="tablist"` for category filters (if added)
- `aria-expanded` for expandable rows
- `aria-sort` for sortable columns (if added)

## Performance

### Optimizations
- **Computed Properties:** Cache filtered transactions
- **Debounced Search:** 300ms delay (optional)
- **Virtual Scrolling:** Consider for large datasets (future enhancement)
- **Lazy Loading:** Already handled by Inertia.js

## Error Handling

### States
1. **No Data:** Empty state message with icon
2. **Loading States:** Skeleton loaders or spinners
3. **Error States:** Error toasts with retry buttons
4. **Network Errors:** Graceful degradation

## Future Enhancements (Out of Scope)

- Date range picker
- Hourly sales chart
- Product performance breakdown
- Export to PDF/Excel
- Print functionality implementation
- Advanced filtering (customer, product, amount range)

## Success Criteria

✅ Real data from backend (no mock data)
✅ 2x2 statistics grid with correct calculations
✅ Searchable, filterable transaction table
✅ Expandable transaction rows with item details
✅ Date navigation (previous/next/today)
✅ Responsive design for all screen sizes
✅ Accessible keyboard navigation
✅ POS design system integration
✅ Indonesian language support
✅ Payment method totals (not counts)