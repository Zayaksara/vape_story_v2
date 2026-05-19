<template>
  <div class="ai-fab-root">
    <!-- Backdrop -->
    <Transition name="ai-fab-backdrop">
      <div
        v-if="isOpen"
        class="ai-fab-backdrop"
        aria-hidden="true"
        @click="close"
      />
    </Transition>

    <!-- Panel -->
    <Transition name="ai-fab-panel">
      <div
        v-if="isOpen"
        class="ai-fab-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ai-fab-title"
      >
        <div class="ai-fab-panel__header">
          <div class="ai-fab-panel__brand">
            <div class="ai-fab-panel__avatar">
              <BotMessageSquare class="h-5 w-5" />
            </div>
            <div>
              <p id="ai-fab-title" class="ai-fab-panel__title">AI Asisten</p>
              <p class="ai-fab-panel__sub">
                <span class="ai-fab-pulse" />
                <span>Under Construction</span>
              </p>
            </div>
          </div>
          <button
            type="button"
            class="ai-fab-close"
            aria-label="Tutup panel AI Asisten"
            @click="close"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="ai-fab-panel__body">
          <div class="ai-fab-coming-soon">
            <div class="ai-fab-coming-soon__glow">
              <Sparkles class="h-10 w-10" />
            </div>
            <h4 class="ai-fab-coming-soon__title">Coming Soon</h4>
            <p class="ai-fab-coming-soon__desc">
              AI Asisten akan membantu Anda menganalisa penjualan, memberikan
              prediksi tren, dan menjawab pertanyaan tentang data toko.
            </p>

            <ul class="ai-fab-feature-list">
              <li>
                <span class="ai-fab-feature-list__icon ai-fab-feature-list__icon--teal">
                  <TrendingUp class="h-3.5 w-3.5" />
                </span>
                <span>Prediksi pendapatan & tren produk</span>
              </li>
              <li>
                <span class="ai-fab-feature-list__icon ai-fab-feature-list__icon--amber">
                  <MessageCircle class="h-3.5 w-3.5" />
                </span>
                <span>Tanya jawab dengan data toko</span>
              </li>
              <li>
                <span class="ai-fab-feature-list__icon ai-fab-feature-list__icon--violet">
                  <Lightbulb class="h-3.5 w-3.5" />
                </span>
                <span>Rekomendasi promo & restok</span>
              </li>
            </ul>

            <div class="ai-fab-eta">
              <Clock class="h-3.5 w-3.5" />
              <span>Fitur akan di kembangkan mulai Minggu Depan</span>
            </div>
          </div>
        </div>

        <div class="ai-fab-panel__footer">
          <button
            type="button"
            class="ai-fab-cta"
            disabled
            aria-disabled="true"
          >
            <BotMessageSquare class="h-4 w-4" />
            <span>Mulai chat (segera hadir)</span>
          </button>
        </div>
      </div>
    </Transition>

    <!-- Floating Button -->
    <button
      type="button"
      class="ai-fab-button"
      :class="{ 'ai-fab-button--open': isOpen }"
      :aria-expanded="isOpen"
      aria-label="Buka AI Asisten"
      @click="toggle"
    >
      <span class="ai-fab-button__ring" aria-hidden="true" />
      <Transition name="ai-fab-icon" mode="out-in">
        <X v-if="isOpen" key="close" class="h-6 w-6" />
        <BotMessageSquare v-else key="bot" class="h-6 w-6" />
      </Transition>
      <span v-if="!isOpen" class="ai-fab-button__badge" aria-hidden="true">
        AI
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import {
  BotMessageSquare,
  X,
  Sparkles,
  TrendingUp,
  MessageCircle,
  Lightbulb,
  Clock,
} from 'lucide-vue-next'

const isOpen = ref(false)

function toggle() {
  isOpen.value = !isOpen.value
}

function close() {
  isOpen.value = false
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && isOpen.value) {
    close()
  }
}

watch(isOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  if (typeof document !== 'undefined') document.body.style.overflow = ''
})
</script>

<style scoped>
.ai-fab-root {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 80;
}

/* ── Floating Button ─────────────────────────────────────────────────────── */
.ai-fab-button {
  position: absolute;
  right: 1.5rem;
  bottom: 1.5rem;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 9999px;
  border: none;
  cursor: pointer;
  pointer-events: auto;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow:
    0 10px 25px -5px rgba(20, 184, 166, 0.45),
    0 8px 10px -6px rgba(20, 184, 166, 0.3);
  transition:
    transform 280ms cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 280ms ease,
    background 280ms ease;
}

.ai-fab-button:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow:
    0 14px 30px -5px rgba(20, 184, 166, 0.55),
    0 10px 14px -6px rgba(20, 184, 166, 0.4);
}

.ai-fab-button:focus-visible {
  outline: 3px solid rgba(20, 184, 166, 0.4);
  outline-offset: 3px;
}

.ai-fab-button--open {
  background: linear-gradient(135deg, #475569 0%, #1e293b 100%);
  box-shadow:
    0 10px 25px -5px rgba(15, 23, 42, 0.4),
    0 8px 10px -6px rgba(15, 23, 42, 0.3);
}

/* Pulsing ring — only when closed */
.ai-fab-button__ring {
  position: absolute;
  inset: -4px;
  border-radius: 9999px;
  border: 2px solid rgba(20, 184, 166, 0.4);
  opacity: 0;
  animation: ai-fab-pulse 2.4s ease-out infinite;
}

.ai-fab-button--open .ai-fab-button__ring {
  animation: none;
  opacity: 0;
}

@keyframes ai-fab-pulse {
  0%   { transform: scale(0.95); opacity: 0.7; }
  70%  { transform: scale(1.35); opacity: 0; }
  100% { transform: scale(1.35); opacity: 0; }
}

.ai-fab-button__badge {
  position: absolute;
  top: -2px;
  right: -2px;
  background: #f59e0b;
  color: #ffffff;
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  padding: 1px 5px;
  border-radius: 9999px;
  border: 2px solid #ffffff;
  line-height: 1;
}

/* Icon swap transition */
.ai-fab-icon-enter-active,
.ai-fab-icon-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}
.ai-fab-icon-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.6);
}
.ai-fab-icon-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.6);
}

/* ── Backdrop ────────────────────────────────────────────────────────────── */
.ai-fab-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.35);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
  pointer-events: auto;
}

.ai-fab-backdrop-enter-active,
.ai-fab-backdrop-leave-active {
  transition: opacity 260ms ease, backdrop-filter 260ms ease;
}
.ai-fab-backdrop-enter-from,
.ai-fab-backdrop-leave-to {
  opacity: 0;
}

/* ── Panel ───────────────────────────────────────────────────────────────── */
.ai-fab-panel {
  position: absolute;
  right: 1.5rem;
  bottom: 5.5rem;
  width: min(92vw, 22rem);
  max-height: min(80vh, 32rem);
  display: flex;
  flex-direction: column;
  background: #ffffff;
  border-radius: 1rem;
  border: 1px solid #e5e7eb;
  box-shadow:
    0 25px 50px -12px rgba(15, 23, 42, 0.25),
    0 10px 20px -8px rgba(15, 23, 42, 0.15);
  overflow: hidden;
  pointer-events: auto;
  transform-origin: bottom right;
}

.ai-fab-panel-enter-active {
  transition:
    opacity 260ms ease,
    transform 320ms cubic-bezier(0.34, 1.56, 0.64, 1);
}
.ai-fab-panel-leave-active {
  transition:
    opacity 180ms ease,
    transform 220ms cubic-bezier(0.4, 0, 0.2, 1);
}
.ai-fab-panel-enter-from,
.ai-fab-panel-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.92);
}

/* Header */
.ai-fab-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #ffffff;
}

.ai-fab-panel__brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.ai-fab-panel__avatar {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ai-fab-panel__title {
  font-size: 0.9375rem;
  font-weight: 600;
  margin: 0;
  line-height: 1.15;
}

.ai-fab-panel__sub {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  margin: 0;
  opacity: 0.9;
}

.ai-fab-pulse {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 9999px;
  background: #fde68a;
  box-shadow: 0 0 0 0 rgba(253, 230, 138, 0.6);
  animation: ai-fab-dot-pulse 1.8s ease-in-out infinite;
}

@keyframes ai-fab-dot-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(253, 230, 138, 0.6); }
  50%      { box-shadow: 0 0 0 6px rgba(253, 230, 138, 0); }
}

.ai-fab-close {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: #ffffff;
  width: 2rem;
  height: 2rem;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 150ms ease;
  flex-shrink: 0;
}

.ai-fab-close:hover {
  background: rgba(255, 255, 255, 0.28);
}

/* Body */
.ai-fab-panel__body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem 1rem 0.5rem;
}

.ai-fab-coming-soon {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.5rem;
}

.ai-fab-coming-soon__glow {
  width: 4rem;
  height: 4rem;
  border-radius: 9999px;
  background:
    radial-gradient(circle, rgba(20, 184, 166, 0.2) 0%, rgba(20, 184, 166, 0) 70%),
    linear-gradient(135deg, #ccfbf1 0%, #ecfeff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0d9488;
  margin-bottom: 0.25rem;
  animation: ai-fab-float 3s ease-in-out infinite;
}

@keyframes ai-fab-float {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-4px); }
}

.ai-fab-coming-soon__title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #14b8a6 0%, #6366f1 100%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  letter-spacing: 0.01em;
}

.ai-fab-coming-soon__desc {
  font-size: 0.8125rem;
  color: #475569;
  margin: 0 0 0.5rem 0;
  line-height: 1.5;
}

/* Feature list */
.ai-fab-feature-list {
  list-style: none;
  padding: 0;
  margin: 0.25rem 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
}

.ai-fab-feature-list li {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  background: #f9fafb;
  border: 1px solid #f1f5f9;
  font-size: 0.8125rem;
  color: #334155;
  text-align: left;
}

.ai-fab-feature-list__icon {
  width: 1.625rem;
  height: 1.625rem;
  border-radius: 0.4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ai-fab-feature-list__icon--teal {
  background: #ccfbf1;
  color: #0d9488;
}

.ai-fab-feature-list__icon--amber {
  background: #fef3c7;
  color: #b45309;
}

.ai-fab-feature-list__icon--violet {
  background: #ede9fe;
  color: #6d28d9;
}

.ai-fab-eta {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: 0.75rem;
  padding: 0.35rem 0.75rem;
  background: #fef3c7;
  color: #92400e;
  border-radius: 9999px;
  font-size: 0.6875rem;
  font-weight: 600;
}

/* Footer */
.ai-fab-panel__footer {
  padding: 0.75rem 1rem 1rem;
  border-top: 1px solid #f1f5f9;
  background: #ffffff;
}

.ai-fab-cta {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #ffffff;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: not-allowed;
  opacity: 0.55;
  transition: opacity 150ms ease;
}

/* Responsive: full-width sheet on small screens */
@media (max-width: 480px) {
  .ai-fab-panel {
    right: 0.75rem;
    left: 0.75rem;
    bottom: 5.25rem;
    width: auto;
  }

  .ai-fab-button {
    right: 1rem;
    bottom: 1rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ai-fab-button,
  .ai-fab-button__ring,
  .ai-fab-coming-soon__glow,
  .ai-fab-pulse {
    animation: none !important;
  }
  .ai-fab-panel-enter-active,
  .ai-fab-panel-leave-active,
  .ai-fab-backdrop-enter-active,
  .ai-fab-backdrop-leave-active,
  .ai-fab-icon-enter-active,
  .ai-fab-icon-leave-active {
    transition-duration: 80ms !important;
  }
}
</style>
