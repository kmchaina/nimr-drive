<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'NIMR Storage') }} - Secure Storage</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        /* Custom Animations & Utilities not yet in Tailwind v4 or specific overrides */
        :root {
            --font-sans: 'Plus Jakarta Sans', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        body {
            font-family: var(--font-sans);
            background-color: #030014;
            /* Deep Space Black */
            color: #ffffff;
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--font-heading);
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .glass-nav {
            background: rgba(3, 0, 20, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .text-gradient {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .text-gradient-purple {
            background: linear-gradient(135deg, #c471ed 0%, #f64f59 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.6;
            animation: float 10s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .bg-grid-pattern {
            background-size: 50px 50px;
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
        }
    </style>
</head>

<body class="antialiased selection:bg-blue-500 selection:text-white">

    <!-- Background Effects -->
    <div class="fixed inset-0 bg-grid-pattern pointer-events-none z-0"></div>
    <div class="blob bg-purple-600 w-96 h-96 rounded-full top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
    <div
        class="blob bg-blue-600 w-[500px] h-[500px] rounded-full bottom-0 right-0 translate-x-1/3 translate-y-1/3 animation-delay-2000">
    </div>

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass-nav transition-all duration-300">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex-shrink-0 flex items-center gap-3 cursor-pointer">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z" />
                        </svg>
                    </div>
                    <span class="font-bold text-xl tracking-tight">{{ config('app.name', 'NIMR Storage') }}</span>
                </div>

                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}"
                                    class="px-6 py-2.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/5 backdrop-blur-sm transition-all duration-300 font-medium text-sm">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}"
                                    class="text-gray-300 hover:text-white transition-colors px-3 py-2 rounded-md text-sm font-medium">Log
                                    in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                        class="group relative px-6 py-2.5 rounded-full bg-blue-600 hover:bg-blue-500 transition-all duration-300 text-sm font-semibold shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50">
                                        <span class="relative z-10 text-white">Get Started</span>
                                        <div
                                            class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        </div>
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative z-10 pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center relative">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-panel mb-8 animate-fade-in-up">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                <span class="text-sm font-medium text-gray-300">v2.0 is now live</span>
            </div>

            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-8 leading-tight">
                Storage reimagined for the <br>
                <span class="text-gradient">Modern Era</span>
            </h1>

            <p class="mt-6 text-lg md:text-xl text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Experience lightning-fast, secure, and beautiful cloud storage.
                Manage your digital life with a workspace designed for focus and clarity.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                        class="w-full sm:w-auto px-8 py-4 rounded-full bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold text-lg shadow-xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all duration-300">
                        Start for free
                    </a>
                @endif
                <a href="#features"
                    class="w-full sm:w-auto px-8 py-4 rounded-full glass-panel hover:bg-white/5 text-white font-semibold text-lg transition-all duration-300 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Watch Demo
                </a>
            </div>

            <!-- Hero Image / Mockup -->
            <div class="mt-20 relative mx-auto max-w-5xl">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl blur opacity-20">
                </div>
                <div class="relative glass-panel rounded-2xl p-2 md:p-4 border border-white/10 shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1629654297299-c8506221ca97?q=80&w=2748&auto=format&fit=crop"
                        alt="App Dashboard"
                        class="rounded-xl w-full h-auto opacity-90 hover:opacity-100 transition-opacity duration-500">

                    <!-- Floating Cards Overlay -->
                    <div
                        class="absolute -right-12 top-20 glass-panel p-4 rounded-xl hidden lg:block animate-float-slow">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold">Upload Complete</div>
                                <div class="text-xs text-gray-400">brand_assets_v2.zip</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-24 relative z-10 bg-black/20">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-6">Designed for <span
                        class="text-gradient-purple">Performance</span></h2>
                <p class="text-gray-400 max-w-2xl mx-auto text-lg">Every interaction is crafted to feel instantaneous
                    and fluid. It's not just storage, it's a workflow accelerator.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass-panel p-8 rounded-2xl hover:bg-white/5 transition-all duration-300 group">
                    <div
                        class="w-14 h-14 rounded-2xl bg-blue-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Lightning Fast</h3>
                    <p class="text-gray-400 leading-relaxed">Global CDN and optimized compression ensure your files are
                        ready when you are, wherever you are.</p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-panel p-8 rounded-2xl hover:bg-white/5 transition-all duration-300 group">
                    <div
                        class="w-14 h-14 rounded-2xl bg-purple-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Bank-Grade Security</h3>
                    <p class="text-gray-400 leading-relaxed">End-to-end encryption and compliance standards that keep
                        your intellectual property safe.</p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-panel p-8 rounded-2xl hover:bg-white/5 transition-all duration-300 group">
                    <div
                        class="w-14 h-14 rounded-2xl bg-pink-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Beautiful Organization</h3>
                    <p class="text-gray-400 leading-relaxed">Auto-tagging, smart folders, and a visual search engine
                        that understands your content.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="py-20 relative z-10">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <div class="glass-panel rounded-3xl p-12 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl">
                </div>

                <h2 class="text-4xl font-bold mb-6 relative z-10">Ready to upgrade your storage?</h2>
                <p class="text-gray-400 mb-8 relative z-10">Secure storage built for the National Institute for Medical
                    Research (NIMR).</p>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                        class="relative z-10 inline-block px-10 py-4 rounded-full bg-white text-black font-bold text-lg hover:scale-105 transition-transform duration-300">
                        Create Free Account
                    </a>
                @endif
            </div>
        </div>
    </div>

    <footer class="border-t border-white/5 bg-black/40 backdrop-blur-md pt-16 pb-8 relative z-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center gap-2 mb-4 md:mb-0">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z" />
                        </svg>
                    </div>
                    <span class="font-bold text-lg">{{ config('app.name', 'NIMR Storage') }}</span>
                </div>
                <div class="text-gray-500 text-sm">
                    &copy; {{ date('Y') }} National Institute for Medical Research (NIMR). All rights reserved.
                </div>
            </div>
        </div>
    </footer>

</body>

</html>
