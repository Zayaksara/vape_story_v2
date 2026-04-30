<template>
  <div v-if="isOpen" class="cart-drawer fixed inset-0 z-50 lg:hidden ">
    <!-- Backdrop -->
    <div
      class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
      :class="isOpen ? 'opacity-100' : 'opacity-0'"
      @click="$emit('update:modelValue', false)"
    />

    <!-- Drawer -->
    <div
      class="fixed right-0 top-0 h-[100dvh] w-full max-w-[320px] bg-white shadow-2xl transition-transform duration-300"
      :class="isOpen ? 'translate-x-0' : 'translate-x-full'"
    >
      <CartPanel
        :cart="cart"
        :subtotal="subtotal"
        :discount-amount="discountAmount"
        :tax-amount="taxAmount"
        :total="total"
        :is-processing="isProcessing"
        :cart-count="cartCount"
        :show-cart-button="showCartButton"
        :discount-label="discountLabel"
        @remove-item="$emit('remove-item', $event)"
        @update-quantity="(...args) => $emit('update-quantity', ...args)"
        @clear-cart="$emit('clear-cart')"
        @process-payment="$emit('process-payment')"
        @apply-discount="$emit('apply-discount', $event)"
        @remove-discount="$emit('remove-discount')"
        @toggle-cart="$emit('update:modelValue', false)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CartItem, Discount } from '@/types/pos'
import CartPanel from './CartPanel.vue'

const props = defineProps<{
  modelValue: boolean
  cart: CartItem[]
  subtotal: number
  discountAmount: number
  taxAmount: number
  total: number
  isProcessing: boolean
  cartCount: number
  showCartButton: boolean
  discountLabel?: string
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
  'remove-item': [productId: string]
  'update-quantity': [productId: string, qty: number]
  'clear-cart': []
  'process-payment': []
  'apply-discount': [discount: Discount]
  'remove-discount': []
}>()

const isOpen = computed(() => props.modelValue)
</script>
