<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { usePos } from "@/composables/usePos";
import { pos } from "@/routes";
import type { Product, Category } from "@/types/pos";

const props = defineProps<{
  products: Product[];
  categories: Category[];
  cashier: {
    id: number;
    name: string;
    email: string;
  };
}>();

const { cart, searchQuery, currentCategory, addToCart, subtotal, taxAmount, totalAmount, clearCart } = usePos();

const filteredProducts = computed(() => {
  return props.products.filter((p) => {
    const matchCategory =
      !currentCategory.value || p.category_id === currentCategory.value;
    const matchSearch =
      !searchQuery.value ||
      p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.code.toLowerCase().includes(searchQuery.value.toLowerCase());
    return matchCategory && matchSearch && p.is_active;
  });
});

function processPayment() {
  if (cart.value.length === 0) {
    return;
  }

  const items = cart.value.map((item) => ({
    product_id: item.id,
    batch_id: item.selectedBatchId,
    quantity: item.quantity,
    unit_price: item.base_price,
    discount: item.discount,
    total: item.base_price * item.quantity - item.discount,
  }));

  router.post(pos.payment.process().url, {
    items,
    total_amount: totalAmount.value,
    paid_amount: totalAmount.value,
    discount_amount: cart.value.reduce((sum, item) => sum + item.discount, 0),
    tax_amount: taxAmount.value,
    payment_method: "cash",
  }, {
    onSuccess: () => {
      clearCart();
    },
    onError: (errors) => {
      console.error("Payment error:", errors);
    },
  });
}
</script>

<template>
  <Head title="POS Dashboard" />

  <div class="h-screen flex flex-col bg-gray-50">
    <div class="bg-white border-b border-gray-200 px-4 py-3">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-gray-900">POS Kasir</h1>
          <p class="text-sm text-gray-500">Welcome, {{ cashier.name }}</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-medium">{{ cart.length }} items in cart</p>
            <p class="text-lg font-bold text-primary">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalAmount) }}</p>
          </div>
        </div>
      </div>

      <div class="mt-3 flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search products or scan barcode..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="flex-1 flex overflow-hidden">
      <div class="flex-1 flex flex-col overflow-hidden">
        <CategoryNav
          :categories="categories"
          :active="currentCategory"
          @select="currentCategory = $event"
        />

        <div class="flex-1 overflow-y-auto p-4">
          <div v-if="filteredProducts.length === 0" class="text-center py-12">
            <p class="text-gray-500">No products available</p>
          </div>
          <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <ProductCard
              v-for="product in filteredProducts"
              :key="product.id"
              :product="product"
              @add-to-cart="addToCart(product, $event.batchId)"
            />
          </div>
        </div>
      </div>

      <div class="w-full md:w-80 border-l border-gray-200 print:hidden">
        <div class="h-full flex flex-col">
          <div class="flex-1 overflow-y-auto">
            <div class="p-4">
              <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Shopping Cart
                <span class="ml-auto bg-primary text-primary-foreground text-xs px-2 py-1 rounded-full">
                  {{ cart.length > 0 ? cart.reduce((sum, item) => sum + item.quantity, 0) : 0 }}
                </span>
              </h3>

              <div v-if="cart.length === 0" class="text-center py-8 text-gray-500">
                <svg class="h-12 w-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p>Cart is empty</p>
                <p class="text-sm">Add items to start selling</p>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="(item, index) in cart"
                  :key="`${item.id}-${item.selectedBatchId}`"
                  class="bg-gray-50 rounded-lg p-3"
                >
                  <div class="flex gap-3">
                    <div class="w-16 h-16 rounded bg-gray-200 overflow-hidden flex-shrink-0">
                      <img
                        v-if="item.image"
                        :src="`/storage/images/${item.image}`"
                        :alt="item.name"
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs text-center p-1">
                        {{ item.name.substring(0, 2) }}
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <h4 class="font-medium text-sm truncate">{{ item.name }}</h4>
                      <p class="text-xs text-gray-500">Batch: {{ item.selectedBatchId?.substring(0, 8) || 'N/A' }}</p>
                      <p class="text-sm font-semibold text-primary">Rp{{ item.base_price?.toLocaleString('id-ID') }}</p>
                      <div class="flex items-center gap-1 mt-1">
                        <button
                          @click="item.quantity > 1 ? item.quantity-- : item.quantity = 1"
                          class="w-6 h-6 flex items-center justify-center bg-gray-200 rounded text-xs"
                        >
                          -
                        </button>
                        <input
                          v-model.number="item.quantity"
                          type="number"
                          min="1"
                          class="w-10 text-center text-sm border rounded"
                        />
                        <button
                          @click="item.quantity++"
                          class="w-6 h-6 flex items-center justify-center bg-gray-200 rounded text-xs"
                        >
                          +
                        </button>
                      </div>
                    </div>
                    <button
                      @click="cart.splice(index, 1)"
                      class="text-red-500 hover:text-red-700 flex-shrink-0"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <div class="mt-2 text-right">
                    <span class="text-sm font-semibold">Subtotal: </span>
                    <span class="font-semibold">Rp{{ (item.base_price * item.quantity).toLocaleString('id-ID') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-200 bg-white p-4">
            <div class="space-y-2 mb-4">
              <div class="flex justify-between text-sm">
                <span>Subtotal</span>
                <span>Rp{{ subtotal.toLocaleString('id-ID') }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span>Tax (10%)</span>
                <span>Rp{{ taxAmount.toLocaleString('id-ID') }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span>Discount</span>
                <span class="text-red-600">-
                  Rp{{ cart.reduce((sum, item) => sum + item.discount, 0).toLocaleString('id-ID') }}
                </span>
              </div>
              <div class="border-t pt-2">
                <div class="flex justify-between text-lg font-bold">
                  <span>Total</span>
                  <span class="text-primary">Rp{{ totalAmount.toLocaleString('id-ID') }}</span>
                </div>
              </div>
            </div>
            <button
              @click="processPayment"
              :disabled="cart.length === 0"
              class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Process Payment
            </button>
            <button
              @click="clearCart"
              :disabled="cart.length === 0"
              class="w-full mt-2 text-sm text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Clear Cart
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
