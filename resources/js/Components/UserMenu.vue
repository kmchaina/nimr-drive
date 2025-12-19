<template>
    <div class="relative">
        <button
            type="button"
            @click="open = !open"
            class="inline-flex items-center gap-3 p-2 rounded-2xl border transition-all"
            :class="[
                'bg-[color:var(--ui-surface)] border-[color:var(--ui-border)]',
                'hover:bg-[color:var(--ui-hover)]'
            ]"
        >
            <div class="relative">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-sky-600 flex items-center justify-center text-white font-bold text-xs shadow-lg shadow-indigo-500/20">
                    {{ initials }}
                </div>
                <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-[color:var(--ui-surface)]"></div>
            </div>
            <div class="hidden sm:block text-left">
                <div class="text-sm font-semibold leading-tight text-[color:var(--ui-fg)]">
                    {{ user.display_name || user.name }}
                </div>
                <div class="text-xs text-[color:var(--ui-muted)] leading-tight">
                    @{{ user.ad_username }}
                </div>
            </div>
            <svg class="w-4 h-4 text-[color:var(--ui-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 mt-3 w-56 rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface-strong)] backdrop-blur-2xl shadow-2xl overflow-hidden z-50"
            >
                <a
                    href="/profile"
                    class="flex items-center gap-3 px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] transition-colors"
                >
                    <svg class="w-4 h-4 text-[color:var(--ui-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Profile
                </a>

                <button
                    type="button"
                    @click="$emit('logout')"
                    class="w-full flex items-center gap-3 px-4 py-3 text-sm text-rose-600 dark:text-rose-300 hover:bg-rose-500/10 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign out
                </button>
            </div>
        </transition>

        <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    user: { type: Object, required: true },
})

defineEmits(['logout'])

const open = ref(false)

const initials = computed(() => {
    const name = (props.user.display_name || props.user.name || '').trim()
    if (!name) return '?'
    const parts = name.split(/\s+/).slice(0, 2)
    return parts.map(p => p[0]?.toUpperCase()).join('')
})
</script>


