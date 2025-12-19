<template>
    <div class="min-h-screen bg-[color:var(--ui-bg)] text-[color:var(--ui-fg)] selection:bg-indigo-500/30">
        <!-- Header -->
        <header class="sticky top-0 z-20 border-b border-[color:var(--ui-border)] bg-[color:var(--ui-surface-strong)] backdrop-blur-xl">
            <div class="mx-auto max-w-5xl px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a
                        href="/dashboard"
                        class="inline-flex items-center justify-center p-2 rounded-xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] hover:bg-[color:var(--ui-hover)] transition-all"
                        title="Back to dashboard"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <div class="text-xs text-[color:var(--ui-muted)]">Profile</div>
                        <div class="text-lg font-semibold font-heading">{{ appName }}</div>
                    </div>
                </div>

                <ThemeToggle />
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-10">
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Identity card -->
                <div class="lg:col-span-2 rounded-3xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface-strong)] backdrop-blur-2xl shadow-xl p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-600 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-500/20">
                                {{ initials }}
                            </div>
                            <div>
                                <div class="text-2xl font-bold tracking-tight font-heading">
                                    {{ user.display_name || user.name }}
                                </div>
                                <div class="mt-1 text-sm text-[color:var(--ui-muted)]">
                                    @{{ user.ad_username }}
                                </div>
                            </div>
                        </div>

                        <button
                            @click="logout"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-sm font-semibold text-rose-600 dark:text-rose-300 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/15 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sign out
                        </button>
                    </div>

                    <div class="mt-6 grid sm:grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                            <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Email</div>
                            <div class="mt-1 text-sm font-medium break-all">{{ user.email }}</div>
                        </div>
                        <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                            <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Last login</div>
                            <div class="mt-1 text-sm font-medium">{{ lastLogin }}</div>
                        </div>
                    </div>
                </div>

                <!-- Quota card -->
                <div class="rounded-3xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface-strong)] backdrop-blur-2xl shadow-xl p-6">
                    <div class="text-sm font-semibold">Storage quota</div>
                    <div class="mt-4 h-2.5 rounded-full bg-black/5 dark:bg-white/5 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"
                            :style="{ width: Math.min(quota.usage_percentage, 100) + '%' }"
                        />
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <div class="font-semibold">{{ formatBytes(quota.used_bytes) }}</div>
                        <div class="text-[color:var(--ui-muted)]">of {{ formatBytes(quota.total_bytes) }}</div>
                    </div>
                    <div class="mt-3 text-xs text-[color:var(--ui-muted)]">
                        Quota is enforced during uploads.
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ThemeToggle from '../Components/ThemeToggle.vue'

const props = defineProps({
    user: { type: Object, required: true },
    quota: { type: Object, required: true },
    appName: { type: String, default: 'NIMR Storage' },
})

const initials = computed(() => {
    const name = (props.user.display_name || props.user.name || '').trim()
    if (!name) return '?'
    const parts = name.split(/\s+/).slice(0, 2)
    return parts.map(p => p[0]?.toUpperCase()).join('')
})

const lastLogin = computed(() => {
    if (!props.user.last_login) return '—'
    try {
        const d = new Date(props.user.last_login)
        return d.toLocaleString()
    } catch {
        return props.user.last_login
    }
})

const logout = () => {
    router.post('/logout')
}

const formatBytes = (bytes) => {
    if (!Number.isFinite(bytes)) return '—'
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    let v = bytes
    let i = 0
    while (v >= 1024 && i < units.length - 1) {
        v /= 1024
        i++
    }
    return `${Math.round(v * 100) / 100} ${units[i]}`
}
</script>


