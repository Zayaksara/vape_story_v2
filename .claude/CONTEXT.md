# Laravel Brain AI Context
> Project: story_vape | Analyzed: 2026-05-10T07:34:52+07:00 | Focal: Full project summary | Budget: 6000 tokens

## Call Chain (depth ≤ 3)
TodayTransactionController@getData

## Complexity Hotspots
| Label | Cyclomatic | Lines |
|-------|-----------|-------|
| ProcessPaymentController@process | 7 | 93 |
| DashboardController@resolvePeriod | 6 | 37 |
| TodayTransactionController@averageBatchCostByProductId | 6 | 28 |
| ProductService@applyCategoryFilter | 3 | 11 |
| ProductService@resolveSelectedCategory | 3 | 12 |
| DashboardController@metric | 2 | 14 |
| ProfileController@update | 2 | 14 |
| SecurityController@edit | 2 | 15 |
| ProductService@applySearchFilter | 2 | 13 |
| ProductService@applyUnitFilter | 2 | 16 |
| ProductService@parseUnit | 2 | 12 |
| DashboardController@buildStats | 1 | 12 |
| DashboardController@index | 1 | 19 |
| DashboardController@querySaleStats | 1 | 31 |
| DashboardController@index | 1 | 9 |
| ProductController@index | 1 | 32 |
| TodayTransactionController@getData | 1 | 33 |
| TodayTransactionController@index | 1 | 44 |
| TodayTransactionController@mapSalesToTransactions | 1 | 41 |
| ProfileController@destroy | 1 | 13 |
| ProfileController@edit | 1 | 7 |
| SecurityController@update | 1 | 10 |
| ProductService@applyStockStatusFilter | 1 | 12 |
| ProductService@getAllProductsForCounts | 1 | 7 |
| ProductService@getAvailableUnits | 1 | 23 |
| ProductService@getCategories | 1 | 4 |
| ProductService@getFilteredProducts | 1 | 13 |

## Database Operations
- eloquent create sales (via ProcessPaymentController@process)
- eloquent query batches (via ProcessPaymentController@process)
- eloquent create stock_mutations (via ProcessPaymentController@process)
- eloquent create sale_items (via ProcessPaymentController@process)
- eloquent with products (via DashboardController@index)
- eloquent all categories (via DashboardController@index)
- eloquent orderBy categories (via ProductController@index)
- eloquent with products (via ProductController@index)
- eloquent where products (via ProductController@index)
- eloquent with sales (via TodayTransactionController@index)

## Backend Packages (composer.json)
| Package | Version | Dev |
|---------|---------|-----|
| fakerphp/faker | ^1.24 | yes |
| inertiajs/inertia-laravel | ^3.0 |  |
| laramint/laravel-brain | ^1.2 | yes |
| laravel/boost | ^2.4 | yes |
| laravel/fortify | ^1.34 |  |
| laravel/framework | ^13.0 |  |
| laravel/mcp | ^0.7.0 |  |
| laravel/pail | ^1.2.5 | yes |
| laravel/pint | ^1.27 | yes |
| laravel/sail | ^1.53 | yes |
| laravel/tinker | ^3.0 |  |
| laravel/wayfinder | ^0.1.14 |  |
| mockery/mockery | ^1.6 | yes |
| nunomaduro/collision | ^8.9 | yes |
| pestphp/pest | ^4.6 | yes |
| pestphp/pest-plugin-laravel | ^4.1 | yes |
| spatie/laravel-permission | ^7.3 |  |

## Frontend Packages (package.json)
| Package | Version | Dev |
|---------|---------|-----|
| @eslint/js | ^9.19.0 | yes |
| @inertiajs/vite | ^3.0.0 |  |
| @inertiajs/vue3 | ^3.0.0 |  |
| @laravel/vite-plugin-wayfinder | ^0.1.3 | yes |
| @playwright/test | ^1.59.1 | yes |
| @stylistic/eslint-plugin | ^5.10.0 | yes |
| @tailwindcss/vite | ^4.1.11 | yes |
| @tanstack/vue-table | ^8.21.3 |  |
| @types/node | ^22.13.5 | yes |
| @vitejs/plugin-vue | ^6.0.0 | yes |
| @vue/eslint-config-typescript | ^14.3.0 | yes |
| @vueuse/core | ^12.8.2 |  |
| class-variance-authority | ^0.7.1 |  |
| clsx | ^2.1.1 |  |
| concurrently | ^9.0.1 | yes |
| date-fns | ^4.1.0 |  |
| eslint | ^9.17.0 | yes |
| eslint-config-prettier | ^10.0.1 | yes |
| eslint-import-resolver-typescript | ^4.4.4 | yes |
| eslint-plugin-import | ^2.32.0 | yes |
| eslint-plugin-vue | ^9.32.0 | yes |
| laravel-vite-plugin | ^3.0.0 |  |
| lucide-vue-next | ^0.468.0 |  |
| prettier | ^3.4.2 | yes |
| prettier-plugin-tailwindcss | ^0.6.11 | yes |
| radix-ui | ^1.4.3 |  |
| react-day-picker | ^9.14.0 |  |
| reka-ui | ^2.9.6 |  |
| shadcn | ^4.6.0 | yes |
| tailwind-merge | ^3.2.0 |  |
| tailwindcss | ^4.1.1 |  |
| tw-animate-css | ^1.2.5 |  |
| typescript | ^5.2.2 | yes |
| typescript-eslint | ^8.23.0 | yes |
| vite | ^8.0.0 | yes |
| vue | ^3.5.13 |  |
| vue-input-otp | ^0.3.2 |  |
| vue-sonner | ^2.0.0 |  |
| vue-tsc | ^2.2.4 | yes |