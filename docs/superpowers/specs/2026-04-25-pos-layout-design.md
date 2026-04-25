# POS Layout Design

## Overview
Design untuk layout khusus POS (Point of Sale) yang terpisah dari admin layout, dengan sidebar yang bisa di-toggle melalui hamburger menu di PosHeader.

## Problem Statement
- POS dashboard saat ini menggunakan admin layout yang salah
- Terdapat dua header yang bertumpuk: AppSidebarHeader (admin) dan PosHeader (POS)
- Fungsi toggle sidebar ada di AppSidebarHeader, seharusnya di-integrasikan ke PosHeader
- POS membutuhkan sidebar dan menu yang berbeda dari admin

## Solution Architecture

### 1. New Components
- **PosSidebar.vue**: Sidebar khusus POS dengan menu POS-specific
- **PosLayout.vue**: Layout container khusus untuk POS pages

### 2. Modified Components
- **PosHeader.vue**: Integrasikan fungsi toggle sidebar ke hamburger menu yang sudah ada

### 3. Removed Dependencies
- POS tidak lagi menggunakan `AppSidebarHeader.vue` dan `AppSidebar.vue`

## Component Structure

```
PosLayout.vue
├── PosSidebar.vue (toggleable)
├── PosHeader.vue (enhanced with sidebar toggle)
└── <slot> (POS content)
```

## Detailed Design

### PosSidebar.vue
**Purpose**: Sidebar navigation khusus untuk POS dengan menu yang relevan untuk kasir

**Features**:
- Toggle visibility (hidden by default)
- Slide-in/Overlay behavior
- Responsive design (mobile full width, desktop ~250px)
- Menu items:
  - Dashboard POS
  - Products
  - Riwayat Transaksi Harian
  - Return

**Tech Stack**:
- Vue 3 Composition API
- Tailwind CSS
- Lucide icons

**State Management**:
- Use sidebar state from `SidebarProvider`
- Toggle controlled by PosHeader

**UI Elements**:
```vue
- Sidebar container
- Navigation items (Dashboard, Products, Riwayat Transaksi, Return)
- Active state highlighting
- Hover effects
- Responsive width handling
```

### PosLayout.vue
**Purpose**: Layout wrapper khusus untuk semua POS pages

**Features**:
- SidebarProvider dengan default-open=false (hidden by default)
- Responsive layout
- Integrates PosSidebar and PosHeader
- Container untuk POS content

**Tech Stack**:
- Vue 3 Composition API
- Inertia.js
- Shadcn/ui sidebar components

**Structure**:
```vue
<template>
  <SidebarProvider :default-open="false">
    <PosSidebar />
    <SidebarInset>
      <PosHeader />
      <slot />
    </SidebarInset>
  </SidebarProvider>
</template>
```

### PosHeader.vue (Enhanced)
**Purpose**: Header POS yang berfungsi juga sebagai sidebar toggle controller

**New Features**:
- Hamburger menu di samping nama kasir berfungsi sebagai sidebar toggle
- Icon state change: burger ↔ close based on sidebar state
- Integrate with `useSidebar()` from shadcn/ui

**Existing Features** (tetap dipertahankan):
- Cashier name
- Transaction ID
- Current time
- Cart button with count

**Tech Stack**:
- Vue 3 Composition API
- shadcn/ui SidebarTrigger/useSidebar
- Lucide icons

**New Props/State**:
```typescript
import { useSidebar } from '@/components/ui/sidebar'

const { toggle, isOpen } = useSidebar()
```

**UI Changes**:
```vue
<!-- Hamburger menu di samping nama kasir -->
<button @click="toggle" aria-label="Toggle sidebar">
  <Menu v-if="!isOpen" />
  <X v-else />
</button>
```

## Menu Structure (POS Sidebar)

### Menu Items
1. **Dashboard POS**
   - Icon: LayoutGrid
   - Route: pos.dashboard
   - Active ketika di POS Dashboard

2. **Products**
   - Icon: Package
   - Route: pos.products (perlu dibuat)
   - Untuk manajemen produk kasir

3. **Riwayat Transaksi Harian**
   - Icon: FileText
   - Route: pos.transactions.daily (perlu dibuat)
   - Laporan transaksi harian

4. **Return**
   - Icon: RotateCcw
   - Route: pos.returns (perlu dibuat)
   - Manajemen produk yang di-return

## Implementation Phases

### Phase 1: Core Structure
- Create PosSidebar.vue with menu items
- Create PosLayout.vue with sidebar integration
- Update POS dashboard to use PosLayout

### Phase 2: Sidebar Functionality
- Implement sidebar toggle in PosHeader
- Test show/hide behavior
- Responsive design implementation

### Phase 3: Routing Integration
- Setup routes for POS menu items
- Update navigation logic
- Test navigation between POS pages

## Responsive Behavior

### Desktop (>1024px)
- Sidebar width: 250px
- Hidden by default
- Slide-in from left when toggled
- Overlay or push content (decide based on UX preference)

### Tablet (768px - 1024px)
- Sidebar width: 250px
- Hidden by default
- Slide-in from left
- Overlay recommended

### Mobile (<768px)
- Sidebar width: 100%
- Hidden by default
- Full-screen overlay
- Slide-in from left
- Close on content click

## State Management

### Sidebar State
```typescript
// Use shadcn/ui SidebarProvider
// Default: closed (default-open: false)
// Controlled by PosHeader hamburger menu
```

### Navigation State
```typescript
// Use Inertia.js route for active state detection
// Current route determines active menu item
```

## Success Criteria

1. ✅ POS dashboard uses PosLayout (not admin layout)
2. ✅ Only ONE header visible (PosHeader)
3. ✅ Sidebar hidden by default
4. ✅ Hamburger menu in PosHeader toggles sidebar
5. ✅ Sidebar contains POS-specific menu items
6. ✅ Responsive design works on mobile, tablet, desktop
7. ✅ No interference with admin layout

## Future Considerations

### Additional POS Features
- Real-time inventory updates in sidebar
- Notifications for low stock
- Quick actions (new transaction, quick product search)

### Performance
- Lazy load sidebar content
- Optimize icon loading
- Cache menu items

### Accessibility
- Keyboard navigation for sidebar
- ARIA labels for toggle button
- Focus management when sidebar opens/closes

## Notes

- This design separates POS completely from admin layout
- Future POS pages (Products, Transactions, Returns) will also use PosLayout
- Admin layout remains unchanged and unaffected
- Breadcrumbs will be removed from POS pages (handled by sidebar navigation)
