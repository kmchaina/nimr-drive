<template>
    <button
        type="button"
        @click="toggle"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border transition-all"
        :class="[
            'bg-[color:var(--ui-surface)] border-[color:var(--ui-border)]',
            'text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)]'
        ]"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        aria-label="Toggle theme"
    >
        <svg v-if="isDark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364l-1.414 1.414M7.05 16.95l-1.414 1.414m0-11.314l1.414 1.414m11.314 11.314l1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z"/>
        </svg>
        <span class="text-xs font-semibold">
            {{ isDark ? 'Dark' : 'Light' }}
        </span>
    </button>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const isDark = ref(false)

const syncFromDom = () => {
    isDark.value = document.documentElement.classList.contains('dark')
}

const setTheme = (theme) => {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark')
        localStorage.setItem('theme', 'dark')
    } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('theme', 'light')
    }
    syncFromDom()
}

const toggle = () => {
    setTheme(isDark.value ? 'light' : 'dark')
}

onMounted(() => {
    syncFromDom()
})
</script>


