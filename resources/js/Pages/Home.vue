<template>
    <div class="min-h-screen bg-white dark:bg-[#020205] text-slate-900 dark:text-gray-100 overflow-hidden relative">
        <!-- Animated gradient background -->
        <div class="fixed inset-0 pointer-events-none">
            <div class="absolute top-0 left-1/4 w-[800px] h-[800px] bg-indigo-500/15 rounded-full blur-[150px] animate-pulse"></div>
            <div class="absolute bottom-0 right-1/4 w-[600px] h-[600px] bg-blue-500/15 rounded-full blur-[130px] animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[1000px] h-[1000px] bg-violet-500/10 rounded-full blur-[180px]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(var(--grid-color)_1px,transparent_1px),linear-gradient(90deg,var(--grid-color)_1px,transparent_1px)] bg-[size:64px_64px]"></div>
        </div>

        <div class="relative z-10 min-h-screen flex flex-col lg:flex-row">
            <!-- Left Panel -->
            <div class="lg:w-[55%] flex flex-col justify-between p-8 lg:p-16">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img src="/images/logos/NIMR.png" alt="NIMR Logo" class="w-14 h-14 object-contain" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100">NIMR Drive</h1>
                        <p class="text-sm text-slate-600 dark:text-gray-400">Secure Institute Storage</p>
                    </div>
                </div>

                <div class="flex-1 flex flex-col justify-center py-12 lg:py-0 lg:pr-16">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 dark:bg-white/5 border border-slate-200 dark:border-white/10 shadow-sm w-fit mb-8">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-sm text-slate-600 dark:text-gray-400">Secure • Fast • Reliable</span>
                    </div>

                    <h2 class="text-4xl lg:text-6xl font-bold leading-tight">
                        <span class="text-slate-900 dark:text-gray-100">Your secure</span>
                        <br />
                        <span class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 bg-clip-text text-transparent">cloud workspace</span>
                    </h2>

                    <p class="mt-6 text-lg text-slate-600 dark:text-gray-400 leading-relaxed max-w-xl">
                        Store, organize, and access your institute files from anywhere. Enterprise-grade security with seamless Active Directory integration.
                    </p>

                    <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="group p-5 rounded-2xl bg-white/80 dark:bg-white/5 border border-slate-200 dark:border-white/10 shadow-lg hover:shadow-xl transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 dark:text-gray-100 mb-1">5 GB Storage</h3>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Per user allocation</p>
                        </div>

                        <div class="group p-5 rounded-2xl bg-white/80 dark:bg-white/5 border border-slate-200 dark:border-white/10 shadow-lg hover:shadow-xl transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 dark:text-gray-100 mb-1">AD Secured</h3>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Enterprise auth</p>
                        </div>

                        <div class="group p-5 rounded-2xl bg-white/80 dark:bg-white/5 border border-slate-200 dark:border-white/10 shadow-lg hover:shadow-xl transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 dark:text-gray-100 mb-1">2 GB Uploads</h3>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Large file support</p>
                        </div>
                    </div>
                </div>

                <div class="hidden lg:flex items-center justify-center text-sm text-slate-600 dark:text-gray-400">
                    <span>© {{ new Date().getFullYear() }} NIMR Drive</span>
                </div>
            </div>

            <!-- Right Panel - Login -->
            <div class="lg:w-[45%] flex items-center justify-center p-8 lg:p-16 lg:border-l border-slate-200 dark:border-white/10">
                <div class="w-full max-w-md">
                    <div class="flex justify-end mb-8">
                        <ThemeToggle />
                    </div>

                    <div class="relative">
                        <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 via-blue-500 to-cyan-500 rounded-3xl blur-xl opacity-20"></div>
                        <div class="relative rounded-3xl bg-white/95 dark:bg-[#0a0a12]/95 border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden backdrop-blur-sm">
                            <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 via-blue-500 to-cyan-500"></div>
                            
                            <div class="p-8 lg:p-10 pt-10 lg:pt-12">
                                <div class="text-center mb-10">
                                    <h2 class="text-3xl font-bold text-slate-900 dark:text-gray-100">Welcome back</h2>
                                    <p class="mt-3 text-slate-600 dark:text-gray-400">Sign in with your Active Directory account</p>
                                </div>

                                <form class="space-y-6" @submit.prevent="submit">
                                    <div class="space-y-2">
                                        <label for="username" class="block text-sm font-medium text-slate-900 dark:text-gray-100">Username</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                                </svg>
                                            </div>
                                            <input id="username" v-model="form.username" type="text" required autocomplete="username" placeholder="john.doe"
                                                class="w-full pl-12 pr-4 py-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-gray-100 placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="password" class="block text-sm font-medium text-slate-900 dark:text-gray-100">Password</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                                </svg>
                                            </div>
                                            <input id="password" v-model="form.password" type="password" required autocomplete="current-password" placeholder="••••••••"
                                                class="w-full pl-12 pr-4 py-4 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-gray-100 placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all" />
                                        </div>
                                    </div>

                                    <div v-if="errors.username || errors.password" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20">
                                        <p class="text-sm text-rose-500 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                            </svg>
                                            {{ errors.username || errors.password }}
                                        </p>
                                    </div>

                                    <button type="submit" :disabled="form.processing" class="relative w-full group">
                                        <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 via-blue-500 to-cyan-500 rounded-2xl blur opacity-40 group-hover:opacity-70 transition duration-300"></div>
                                        <div class="relative flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white font-semibold shadow-xl shadow-indigo-500/30 transition-all duration-300 group-hover:shadow-indigo-500/50 disabled:opacity-60 disabled:cursor-not-allowed">
                                            <span v-if="form.processing">Signing in...</span>
                                            <span v-else>Sign in</span>
                                            <svg v-if="!form.processing" class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                            </svg>
                                            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                    </button>
                                </form>

                                <div class="mt-8 pt-8 border-t border-slate-200 dark:border-white/10">
                                    <p class="text-center text-sm text-slate-600 dark:text-gray-400">Enter your domain username without @nimrhqs.local</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-8 text-center text-sm text-slate-600 dark:text-gray-400">
                        Need help? Contact <span class="text-indigo-500 font-medium cursor-pointer hover:text-indigo-400">IT Support</span>
                    </p>

                    <div class="mt-8 lg:hidden p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                        <p class="text-xs text-slate-600 dark:text-gray-400 text-center">⚠ For official institute work only</p>
                    </div>

                    <div class="mt-8 lg:hidden text-center text-xs text-slate-600 dark:text-gray-400">© {{ new Date().getFullYear() }} NIMR</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import ThemeToggle from '../Components/ThemeToggle.vue';

defineProps({
    appName: { type: String, default: 'NIMR Drive' },
    errors: { type: Object, default: () => ({}) },
});

const form = useForm({ username: '', password: '' });
const submit = () => { form.post('/login'); };
</script>

<style scoped>
:root {
    --grid-color: rgba(15, 23, 42, 0.04);
}
:root.dark {
    --grid-color: rgba(255, 255, 255, 0.02);
}
</style>
