<template>
    <div class="flex h-screen bg-[color:var(--ui-bg)] text-[color:var(--ui-fg)] overflow-hidden font-sans selection:bg-indigo-500/30">
        <!-- Background Ambient Effects -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
            <div class="absolute top-0 left-1/4 w-[1000px] h-[500px] bg-indigo-600/12 rounded-[100%] blur-[120px] mix-blend-multiply opacity-60 dark:mix-blend-screen dark:opacity-50 dark:animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-[800px] h-[600px] bg-blue-600/10 rounded-[100%] blur-[120px] mix-blend-multiply opacity-50 dark:mix-blend-screen dark:opacity-30"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-500/10 via-[color:var(--ui-bg)] to-[color:var(--ui-bg)]"></div>
        </div>

        <!-- Left Sidebar -->
        <aside class="w-72 bg-[color:var(--ui-surface-strong)] backdrop-blur-2xl border-r border-[color:var(--ui-border)] flex flex-col relative z-20 transition-all duration-300">
            <!-- Logo/Brand -->
            <div class="h-24 flex items-center px-8 border-b border-[color:var(--ui-border)]">
                <div class="relative group">
                    <img src="/images/logos/NIMR.png" alt="NIMR Logo" class="w-12 h-12 object-contain" />
                </div>
                <div class="ml-4">
                    <h1 class="text-xl font-bold tracking-tight text-[color:var(--ui-fg)] font-heading">NIMR Drive</h1>
                    <div class="text-xs text-[color:var(--ui-muted)] -mt-0.5">Secure Institute Storage</div>
                </div>
            </div>

            <!-- New Button with Dropdown -->
            <div class="p-6 relative">
                <button
                    @click="showNewMenu = !showNewMenu"
                    class="group w-full flex items-center justify-center px-4 py-4 bg-white dark:bg-indigo-600/10 hover:bg-gray-50 dark:hover:bg-indigo-600/20 text-[color:var(--ui-fg)] rounded-2xl shadow-xl shadow-indigo-500/10 transition-all duration-300 hover:shadow-indigo-500/20 hover:-translate-y-0.5 border border-[color:var(--ui-border)] ring-1 ring-black/5"
                >
                    <div class="p-1 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 mr-3 text-white shadow-lg group-hover:rotate-90 transition-transform duration-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <span class="font-bold tracking-tight text-sm uppercase">New</span>
                </button>
                
                <!-- Dropdown Menu -->
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform opacity-0 scale-95 translate-y-2"
                    enter-to-class="transform opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="transform opacity-100 scale-100 translate-y-0"
                    leave-to-class="transform opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="showNewMenu"
                        class="absolute left-6 right-6 mt-4 bg-[color:var(--ui-surface-strong)] rounded-2xl shadow-2xl border border-[color:var(--ui-border)] py-2 z-50 backdrop-blur-xl ring-1 ring-black/5"
                    >
                        <button
                            @click="showCreateFolderDialog(); showNewMenu = false"
                            class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center transition-all group"
                        >
                            <div class="p-2 rounded-lg bg-blue-500/10 text-blue-400 mr-3 group-hover:bg-blue-500/20 group-hover:scale-110 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                            </div>
                            New Folder
                        </button>
                        <button
                            @click="showUploadDialog(); showNewMenu = false"
                            class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center transition-all group"
                        >
                             <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 mr-3 group-hover:bg-indigo-500/20 group-hover:scale-110 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            Upload Files
                        </button>
                        <button
                            @click="showFolderUploadDialog(); showNewMenu = false"
                            class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center transition-all group"
                        >
                             <div class="p-2 rounded-lg bg-blue-500/10 text-blue-400 mr-3 group-hover:bg-blue-500/20 group-hover:scale-110 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            Upload Folder
                        </button>
                    </div>
                </transition>
                
                <!-- Overlay -->
                <div v-if="showNewMenu" @click="showNewMenu = false" class="fixed inset-0 z-40 bg-[color:var(--ui-overlay)]/30 backdrop-blur-[2px] transition-all"></div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-2 overflow-y-auto space-y-2">
                <button
                    @click="activeView = 'my-drive'; loadFiles()"
                    :class="[
                        'w-full flex items-center px-4 py-3.5 rounded-2xl text-sm font-medium transition-all duration-300 group relative overflow-hidden',
                        activeView === 'my-drive' 
                            ? 'bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] shadow-lg ring-1 ring-[color:var(--ui-border)]' 
                            : 'text-[color:var(--ui-muted)] hover:bg-[color:var(--ui-hover)] hover:text-[color:var(--ui-fg)] hover:shadow-lg'
                    ]"
                >
                    <div v-if="activeView === 'my-drive'" class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 opacity-100 transition-opacity"></div>
                    <svg :class="[
                        'w-5 h-5 mr-3 transition-colors', 
                        activeView === 'my-drive' ? 'text-blue-500' : 'text-[color:var(--ui-muted-2)] group-hover:text-[color:var(--ui-fg)]'
                    ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span class="relative z-10">My files</span>
                    <div v-if="activeView === 'my-drive'" class="absolute right-3 w-1.5 h-1.5 rounded-full bg-blue-500 box-shadow-glow"></div>
                </button>

                <button
                    @click="activeView = 'shared'; loadSharedFiles()"
                    :class="[
                        'w-full flex items-center px-4 py-3.5 rounded-2xl text-sm font-medium transition-all duration-300 group relative overflow-hidden',
                        activeView === 'shared' 
                            ? 'bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] shadow-lg ring-1 ring-[color:var(--ui-border)]' 
                            : 'text-[color:var(--ui-muted)] hover:bg-[color:var(--ui-hover)] hover:text-[color:var(--ui-fg)] hover:shadow-lg'
                    ]"
                >
                    <div v-if="activeView === 'shared'" class="absolute inset-0 bg-gradient-to-r from-green-500/10 to-emerald-500/10 opacity-100 transition-opacity"></div>
                    <svg :class="[
                        'w-5 h-5 mr-3 transition-colors', 
                        activeView === 'shared' ? 'text-green-500' : 'text-[color:var(--ui-muted-2)] group-hover:text-green-500'
                    ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="relative z-10">Shared with me</span>
                    <div v-if="activeView === 'shared'" class="absolute right-3 w-1.5 h-1.5 rounded-full bg-green-500 box-shadow-glow"></div>
                </button>

                <button
                    @click="activeView = 'recent'; loadRecentFiles()"
                    :class="[
                        'w-full flex items-center px-4 py-3.5 rounded-2xl text-sm font-medium transition-all duration-300 group relative overflow-hidden',
                        activeView === 'recent' 
                            ? 'bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] shadow-lg ring-1 ring-[color:var(--ui-border)]' 
                            : 'text-[color:var(--ui-muted)] hover:bg-[color:var(--ui-hover)] hover:text-[color:var(--ui-fg)] hover:shadow-lg'
                    ]"
                >
                    <div v-if="activeView === 'recent'" class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 to-blue-500/10 opacity-100 transition-opacity"></div>
                    <svg :class="[
                        'w-5 h-5 mr-3 transition-colors', 
                        activeView === 'recent' ? 'text-indigo-500' : 'text-[color:var(--ui-muted-2)] group-hover:text-indigo-500'
                    ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="relative z-10">Recent</span>
                    <div v-if="activeView === 'recent'" class="absolute right-3 w-1.5 h-1.5 rounded-full bg-indigo-500 box-shadow-glow"></div>
                </button>

                <button
                    @click="activeView = 'starred'; loadStarredFiles()"
                    :class="[
                        'w-full flex items-center px-4 py-3.5 rounded-2xl text-sm font-medium transition-all duration-300 group relative overflow-hidden',
                        activeView === 'starred' 
                            ? 'bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] shadow-lg ring-1 ring-[color:var(--ui-border)]' 
                            : 'text-[color:var(--ui-muted)] hover:bg-[color:var(--ui-hover)] hover:text-[color:var(--ui-fg)] hover:shadow-lg'
                    ]"
                >
                    <div v-if="activeView === 'starred'" class="absolute inset-0 bg-gradient-to-r from-amber-500/10 to-yellow-500/10 opacity-100 transition-opacity"></div>
                    <svg :class="[
                        'w-5 h-5 mr-3 transition-colors', 
                        activeView === 'starred' ? 'text-amber-500' : 'text-[color:var(--ui-muted-2)] group-hover:text-amber-500'
                    ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <span class="relative z-10">Starred</span>
                    <div v-if="activeView === 'starred'" class="absolute right-3 w-1.5 h-1.5 rounded-full bg-amber-500 box-shadow-glow"></div>
                </button>

                <button
                    @click="activeView = 'trash'; loadTrashFiles()"
                    :class="[
                        'w-full flex items-center px-4 py-3.5 rounded-2xl text-sm font-medium transition-all duration-300 group relative overflow-hidden',
                        activeView === 'trash' 
                            ? 'bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] shadow-lg ring-1 ring-[color:var(--ui-border)]' 
                            : 'text-[color:var(--ui-muted)] hover:bg-[color:var(--ui-hover)] hover:text-[color:var(--ui-fg)] hover:shadow-lg'
                    ]"
                >
                    <div v-if="activeView === 'trash'" class="absolute inset-0 bg-gradient-to-r from-red-500/10 to-rose-500/10 opacity-100 transition-opacity"></div>
                    <svg :class="[
                        'w-5 h-5 mr-3 transition-colors', 
                        activeView === 'trash' ? 'text-red-500' : 'text-[color:var(--ui-muted-2)] group-hover:text-red-500'
                    ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span class="relative z-10">Trash</span>
                    <div v-if="activeView === 'trash'" class="absolute right-3 w-1.5 h-1.5 rounded-full bg-red-500 box-shadow-glow"></div>
                </button>

                <div class="h-px bg-[color:var(--ui-border)] my-4 mx-4"></div>

                <!-- Admin Panel (only for admins) -->
                <button
                    v-if="user.is_admin"
                    @click="activeView = 'admin'"
                    :class="[
                        'w-full flex items-center px-4 py-3.5 rounded-2xl text-sm font-medium transition-all duration-300 group relative overflow-hidden',
                        activeView === 'admin' 
                            ? 'bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] shadow-lg ring-1 ring-[color:var(--ui-border)]' 
                            : 'text-[color:var(--ui-muted)] hover:bg-[color:var(--ui-hover)] hover:text-[color:var(--ui-fg)] hover:shadow-lg'
                    ]"
                >
                    <div v-if="activeView === 'admin'" class="absolute inset-0 bg-gradient-to-r from-amber-500/10 to-orange-500/10 opacity-100 transition-opacity"></div>
                    <svg :class="[
                        'w-5 h-5 mr-3 transition-colors', 
                        activeView === 'admin' ? 'text-amber-500' : 'text-[color:var(--ui-muted-2)] group-hover:text-amber-500'
                    ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="relative z-10">Admin Panel</span>
                    <div v-if="activeView === 'admin'" class="absolute right-3 w-1.5 h-1.5 rounded-full bg-amber-500 box-shadow-glow"></div>
                </button>
            </nav>

            <!-- Bottom Section -->
            <div class="p-6 space-y-6">
                <!-- Storage Quota -->
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl blur opacity-10 group-hover:opacity-20 transition duration-500"></div>
                    <div class="relative px-5 py-5 rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)]">
                        <div class="flex justify-between items-center mb-3">
                            <div class="text-xs font-bold text-[color:var(--ui-muted)] uppercase tracking-wider">Storage</div>
                            <button
                                @click="recalculateQuota"
                                :disabled="recalculatingQuota"
                                class="text-xs text-indigo-600 dark:text-indigo-300 transition-colors bg-indigo-500/10 px-2 py-1 rounded-md"
                            >
                                {{ recalculatingQuota ? '...' : 'Refresh' }}
                            </button>
                        </div>

                        <div class="w-full bg-black/5 dark:bg-white/10 rounded-full h-2 mb-3 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-1000 ease-out bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 relative"
                                :style="{ width: Math.min(quota.usage_percentage, 100) + '%' }"
                            >
                                <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                            </div>
                        </div>
                        <div class="flex justify-between items-end">
                            <div class="text-sm font-semibold text-[color:var(--ui-fg)]">{{ quota.used_formatted }}</div>
                            <div class="text-xs text-[color:var(--ui-muted)]">of {{ quota.total_formatted }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden relative z-10 bg-[color:var(--ui-bg)]">
            <!-- Top Bar -->
            <header class="h-24 flex items-center px-10 justify-between">
                <!-- Search Bar -->
                <div class="flex-1 max-w-3xl transform transition-transform duration-300 focus-within:scale-[1.01]">
                    <SearchBar 
                        :current-path="currentPath"
                        @search-performed="handleSearchPerformed"
                        @search-cleared="handleSearchCleared"
                        @navigate-to-result="handleNavigateToResult"
                        class="shadow-lg shadow-indigo-500/5 hover:shadow-indigo-500/10 transition-shadow duration-300"
                    />
                </div>

                <!-- Right controls -->
                <div class="ml-6 flex items-center space-x-3">
                    <NotificationBell @navigate="navigateToPath" />
                    <ThemeToggle />
                    <button
                        @click="toggleView"
                        class="p-3 rounded-2xl transition-all duration-300 border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] hover:bg-[color:var(--ui-hover)] active:scale-95"
                        :title="viewMode === 'grid' ? 'List view' : 'Grid view'"
                    >
                        <svg v-if="viewMode === 'grid'" class="w-5 h-5 text-[color:var(--ui-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        <svg v-else class="w-5 h-5 text-[color:var(--ui-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </button>

                    <UserMenu :user="user" @logout="logout" @profile="activeView = 'profile'" />
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto px-10 pb-10 custom-scrollbar">
                <!-- My Files View -->
                <div v-if="activeView === 'my-drive'">
                    <!-- Premium overview header -->
                    <div class="mt-4 mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <h2 class="text-3xl font-extrabold text-[color:var(--ui-fg)] tracking-tight font-heading flex items-center">
                                {{ greeting }}, {{ user.display_name?.split(' ')[0] || user.name.split(' ')[0] }}
                                <span class="ml-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-2 animate-pulse"></span>
                                    Member
                                </span>
                            </h2>
                            <p class="mt-1 text-sm text-[color:var(--ui-muted)]">
                                All your institute files are safe and accessible here.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden xl:flex items-center px-4 py-3 rounded-2xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] backdrop-blur-md">
                                <div class="mr-4">
                                    <div class="text-[10px] text-[color:var(--ui-muted)] uppercase font-bold tracking-widest">Available</div>
                                    <div class="text-sm font-bold text-[color:var(--ui-fg)]">{{ quota.available_formatted }}</div>
                                </div>
                                <div class="w-px h-8 bg-[color:var(--ui-border)] mx-2"></div>
                                <div class="ml-4">
                                    <div class="text-[10px] text-[color:var(--ui-muted)] uppercase font-bold tracking-widest">Usage</div>
                                    <div class="text-sm font-bold text-[color:var(--ui-fg)]">{{ quota.usage_percentage }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="flex items-center justify-between mb-8 mt-2">
                         <Breadcrumbs 
                            :breadcrumbs="breadcrumbs" 
                            @navigate="navigateToPath"
                            class="flex-1"
                        />
                        
                        <div class="flex items-center space-x-3 ml-4">
                            <!-- Refresh Files Button -->
                            <button
                                @click="refreshFiles"
                                :disabled="loading"
                                class="inline-flex items-center px-3 py-2 bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] rounded-xl text-sm font-medium text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] transition-all duration-300 disabled:opacity-50"
                                title="Refresh files"
                            >
                                <svg :class="['w-4 h-4', loading ? 'animate-spin' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>

                            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                                <div v-if="selectedFiles.length > 0" class="flex items-center space-x-3">
                                    <button
                                        @click="handleBatchDelete(getSelectedFileObjects())"
                                        class="inline-flex items-center px-4 py-2 bg-red-500/10 border border-red-500/20 rounded-xl text-sm font-medium text-red-500 hover:bg-red-500/20 transition-all duration-300 shadow-lg shadow-red-500/10"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Delete ({{ selectedFiles.length }})
                                    </button>

                                    <button
                                        v-if="selectedFiles.length > 1"
                                        @click="downloadSelectedAsZip"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-sm font-medium text-indigo-500 hover:bg-indigo-500/20 transition-all duration-300 shadow-lg shadow-indigo-500/10"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                        Download as ZIP
                                    </button>
                                </div>
                            </transition>

                            <div v-if="loading" class="flex items-center text-sm text-[color:var(--ui-muted)] bg-[color:var(--ui-surface)] px-4 py-2 rounded-xl backdrop-blur-md border border-[color:var(--ui-border)]">
                                <svg class="animate-spin h-4 w-4 mr-3 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Syncing...
                            </div>
                        </div>
                    </div>

                    <!-- File List -->
                    <FileList
                        :files="files"
                        :loading="loading"
                        :view-mode="viewMode"
                        :selected-files="selectedFiles"
                        @file-rename="showRenameDialog"
                        @file-delete="showDeleteDialog"
                        @file-share="showShareDialog"
                        @file-star-toggle="toggleStar"
                        @file-move="handleFileMove"
                        @file-select="toggleFileSelection"
                        @file-click="handleFileClick"
                        @refresh="loadFiles"
                    />
                </div>

                <!-- Recent View -->
                <div v-if="activeView === 'recent'">
                    <div class="mt-4 mb-8">
                        <div class="text-sm text-[color:var(--ui-muted)]">Activity</div>
                        <h2 class="text-3xl font-extrabold text-[color:var(--ui-fg)] tracking-tight font-heading">Recent</h2>
                        <p class="mt-1 text-sm text-[color:var(--ui-muted)]">Files you've accessed or modified lately.</p>
                    </div>
                    <FileList
                        :files="recentFiles"
                        :loading="loading"
                        :view-mode="viewMode"
                        :selected-files="selectedFiles"
                        @file-rename="showRenameDialog"
                        @file-delete="showDeleteDialog"
                        @file-share="showShareDialog"
                        @file-star-toggle="toggleStar"
                        @file-move="handleFileMove"
                        @file-select="toggleFileSelection"
                        @file-click="handleFileClick"
                        @refresh="loadRecentFiles"
                    />
                </div>

                <!-- Starred View -->
                <div v-if="activeView === 'starred'">
                    <div class="mt-4 mb-8">
                        <div class="text-sm text-[color:var(--ui-muted)]">Favorites</div>
                        <h2 class="text-3xl font-extrabold text-[color:var(--ui-fg)] tracking-tight font-heading">Starred</h2>
                        <p class="mt-1 text-sm text-[color:var(--ui-muted)]">Your most important files and folders.</p>
                    </div>
                    <FileList
                        :files="starredFiles"
                        :loading="loading"
                        :view-mode="viewMode"
                        :selected-files="selectedFiles"
                        @file-rename="showRenameDialog"
                        @file-delete="showDeleteDialog"
                        @file-share="showShareDialog"
                        @file-star-toggle="toggleStar"
                        @file-move="handleFileMove"
                        @file-select="toggleFileSelection"
                        @file-click="handleFileClick"
                        @refresh="loadStarredFiles"
                    />
                </div>

                <!-- Trash View -->
                <div v-if="activeView === 'trash'">
                    <div class="mt-4 mb-8 flex items-center justify-between">
                        <div>
                            <div class="text-sm text-[color:var(--ui-muted)]">Cleanup</div>
                            <h2 class="text-3xl font-extrabold text-[color:var(--ui-fg)] tracking-tight font-heading">Trash</h2>
                            <p class="mt-1 text-sm text-[color:var(--ui-muted)]">Items here will be permanently deleted soon.</p>
                        </div>
                        <button
                            v-if="trashFiles.length > 0"
                            @click="emptyTrash"
                            class="px-6 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-sm font-bold rounded-xl transition-all border border-red-500/20"
                        >
                            Empty Trash
                        </button>
                    </div>
                    <FileList
                        :files="trashFiles"
                        :loading="loading"
                        :view-mode="viewMode"
                        :selected-files="selectedFiles"
                        @file-restore="restoreFile"
                        @file-permanent-delete="confirmPermanentDelete"
                        @file-click="handleFileClick"
                        @refresh="loadTrashFiles"
                    />
                </div>

                <!-- Shared with Me View -->
                <div v-if="activeView === 'shared'">
                    <div class="mt-4 mb-8">
                        <div class="text-sm text-[color:var(--ui-muted)]">Shared</div>
                        <h2 class="mt-1 text-3xl font-bold text-[color:var(--ui-fg)] tracking-tight font-heading">
                            Shared with me
                        </h2>
                        <p class="mt-2 text-sm text-[color:var(--ui-muted)]">
                            Files and folders shared with you by other institute members.
                        </p>
                    </div>

                    <!-- Shared items list -->
                    <div v-if="sharedItems.length > 0" class="bg-[color:var(--ui-surface)] backdrop-blur-md rounded-3xl border border-[color:var(--ui-border)] overflow-hidden shadow-xl">
                        <table class="min-w-full divide-y divide-[color:var(--ui-border)]">
                            <thead class="bg-black/5 dark:bg-white/5">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Owner</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Access</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[color:var(--ui-border)]">
                                <tr v-for="share in sharedItems" :key="share.id" class="hover:bg-[color:var(--ui-hover)] transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center cursor-pointer" @click="openSharedItem(share)">
                                            <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 mr-3">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <span class="text-sm font-bold text-[color:var(--ui-fg)]">{{ getShareName(share) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-xs font-bold mr-2">
                                                {{ getInitials(share.owner) }}
                                            </div>
                                            <span class="text-sm text-[color:var(--ui-muted)]">{{ share.owner?.display_name || share.owner?.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="share.access_level === 'edit' ? 'bg-amber-500/10 text-amber-500' : 'bg-blue-500/10 text-blue-500'">
                                            {{ share.access_level }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[color:var(--ui-muted)]">
                                        <div class="flex items-center gap-3">
                                            <button @click="downloadSharedItem(share)" class="hover:text-indigo-500 transition-colors" title="Download">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            </button>
                                            <button @click="removeSharedWithMe(share)" class="hover:text-red-500 transition-colors" title="Remove">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div v-else class="flex flex-col items-center justify-center py-32 text-center animate-fade-in-up">
                        <div class="w-24 h-24 rounded-3xl bg-[color:var(--ui-surface-strong)] border border-[color:var(--ui-border)] flex items-center justify-center mb-8 shadow-2xl shadow-indigo-500/10">
                            <svg class="h-12 w-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-[color:var(--ui-fg)] mb-3 tracking-tight">Nothing shared yet</h3>
                        <p class="text-[color:var(--ui-muted)] mb-8 max-w-md mx-auto leading-relaxed">Items shared with you by others will appear here.</p>
                    </div>
                </div>

                <!-- Admin Panel View -->
                <div v-if="activeView === 'admin' && user.is_admin && adminData">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-[color:var(--ui-fg)] mb-2 font-heading">Admin Panel</h2>
                        <p class="text-sm text-[color:var(--ui-muted)]">Manage user quotas and storage allocations</p>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                            <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ adminStats.total_users }}</div>
                            <div class="text-sm text-[color:var(--ui-muted)]">Total Users</div>
                        </div>
                        <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                            <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ adminStats.total_storage_used }}</div>
                            <div class="text-sm text-[color:var(--ui-muted)]">Storage Used</div>
                        </div>
                        <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                            <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ adminStats.total_storage_allocated }}</div>
                            <div class="text-sm text-[color:var(--ui-muted)]">Total Allocated</div>
                        </div>
                        <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                            <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ adminStats.admin_count }}</div>
                            <div class="text-sm text-[color:var(--ui-muted)]">Admins</div>
                        </div>
                    </div>

                    <!-- Bulk Actions -->
                    <div v-if="selectedAdminUsers.length > 0" class="mb-4 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-between">
                        <span class="text-sm">{{ selectedAdminUsers.length }} user(s) selected</span>
                        <div class="flex items-center gap-3">
                            <input
                                v-model.number="bulkQuotaGb"
                                type="number"
                                min="0.1"
                                step="0.5"
                                class="w-24 px-3 py-1.5 rounded-lg bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] text-sm"
                                placeholder="GB"
                            />
                            <button
                                @click="applyBulkQuota"
                                class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors"
                            >
                                Set Quota
                            </button>
                            <button
                                @click="selectedAdminUsers = []"
                                class="px-4 py-1.5 rounded-lg bg-[color:var(--ui-surface)] hover:bg-[color:var(--ui-hover)] text-sm transition-colors"
                            >
                                Clear Selection
                            </button>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-[color:var(--ui-hover)]">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            :checked="selectedAdminUsers.length === adminUsers.length"
                                            @change="toggleSelectAllUsers"
                                            class="rounded"
                                        />
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Storage</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Quota</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Last Login</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Role</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[color:var(--ui-border)]">
                                <tr v-for="adminUser in adminUsers" :key="adminUser.id" class="hover:bg-[color:var(--ui-hover)] transition-colors">
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :checked="selectedAdminUsers.includes(adminUser.id)"
                                            @change="toggleUserSelection(adminUser.id)"
                                            class="rounded"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-[color:var(--ui-fg)]">{{ adminUser.display_name || adminUser.name }}</div>
                                        <div class="text-xs text-[color:var(--ui-muted)]">{{ adminUser.ad_username || adminUser.email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 h-2 rounded-full bg-black/10 dark:bg-white/10 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="{
                                                        'bg-green-500': adminUser.usage_percentage < 70,
                                                        'bg-yellow-500': adminUser.usage_percentage >= 70 && adminUser.usage_percentage < 90,
                                                        'bg-red-500': adminUser.usage_percentage >= 90
                                                    }"
                                                    :style="{ width: Math.min(adminUser.usage_percentage, 100) + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-sm text-[color:var(--ui-muted)]">{{ adminUser.used_formatted }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <input
                                                v-model.number="adminUser.editQuotaGb"
                                                type="number"
                                                min="0.1"
                                                step="0.5"
                                                class="w-20 px-2 py-1 rounded-lg bg-[color:var(--ui-bg)] border border-[color:var(--ui-border)] text-sm"
                                                @focus="adminUser.editQuotaGb = bytesToGb(adminUser.quota_bytes)"
                                            />
                                            <span class="text-sm text-[color:var(--ui-muted)]">GB</span>
                                            <button
                                                v-if="adminUser.editQuotaGb !== bytesToGb(adminUser.quota_bytes)"
                                                @click="updateUserQuota(adminUser)"
                                                :disabled="updatingUserId === adminUser.id"
                                                class="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium transition-colors disabled:opacity-50"
                                            >
                                                {{ updatingUserId === adminUser.id ? '...' : 'Save' }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[color:var(--ui-muted)]">
                                        {{ adminUser.last_login || 'Never' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="adminUser.is_admin ? 'bg-indigo-500/20 text-indigo-400' : 'bg-gray-500/20 text-gray-400'"
                                            class="px-2 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ adminUser.is_admin ? 'Admin' : 'User' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                @click="recalculateUserUsage(adminUser)"
                                                :disabled="recalculatingUserId === adminUser.id"
                                                class="p-1.5 rounded-lg hover:bg-[color:var(--ui-hover)] text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] transition-colors"
                                                title="Recalculate storage"
                                            >
                                                <svg class="w-4 h-4" :class="{ 'animate-spin': recalculatingUserId === adminUser.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="toggleUserAdmin(adminUser)"
                                                :disabled="togglingAdminId === adminUser.id"
                                                class="p-1.5 rounded-lg hover:bg-[color:var(--ui-hover)] text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] transition-colors"
                                                :title="adminUser.is_admin ? 'Remove admin' : 'Make admin'"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Profile View -->
                <div v-if="activeView === 'profile'">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-[color:var(--ui-fg)] mb-2 font-heading">My Profile</h2>
                        <p class="text-sm text-[color:var(--ui-muted)]">View your account information and storage usage</p>
                    </div>

                    <div class="grid lg:grid-cols-3 gap-6">
                        <!-- Identity card -->
                        <div class="lg:col-span-2 rounded-3xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface-strong)] backdrop-blur-2xl shadow-xl p-6">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-500/20">
                                    {{ userInitials }}
                                </div>
                                <div>
                                    <div class="text-2xl font-bold tracking-tight font-heading">
                                        {{ user.display_name || user.name }}
                                    </div>
                                    <div class="mt-1 text-sm text-[color:var(--ui-muted)]">
                                        {{ user.ad_username }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                                    <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Full Name</div>
                                    <div class="mt-1 text-sm font-medium">{{ user.display_name || user.name }}</div>
                                </div>
                                <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                                    <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Username</div>
                                    <div class="mt-1 text-sm font-medium">{{ user.ad_username || user.username }}</div>
                                </div>
                                <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                                    <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Email</div>
                                    <div class="mt-1 text-sm font-medium break-all">{{ user.email || 'Not available' }}</div>
                                </div>
                                <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                                    <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Last Login</div>
                                    <div class="mt-1 text-sm font-medium">{{ formatLastLogin(user.last_login) }}</div>
                                </div>
                                <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                                    <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Account Type</div>
                                    <div class="mt-1 text-sm font-medium">
                                        <span :class="user.is_admin ? 'text-indigo-400' : 'text-[color:var(--ui-fg)]'">
                                            {{ user.is_admin ? 'Administrator' : 'Standard User' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-4">
                                    <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Authentication</div>
                                    <div class="mt-1 text-sm font-medium">Active Directory</div>
                                </div>
                            </div>
                        </div>

                        <!-- Quota card -->
                        <div class="rounded-3xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface-strong)] backdrop-blur-2xl shadow-xl p-6">
                            <div class="text-sm font-semibold mb-4">Storage Quota</div>
                            <div class="h-3 rounded-full bg-black/5 dark:bg-white/5 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"
                                    :style="{ width: Math.min(quota.usage_percentage, 100) + '%' }"
                                />
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <div class="font-semibold">{{ quota.used_formatted }}</div>
                                <div class="text-[color:var(--ui-muted)]">of {{ quota.total_formatted }}</div>
                            </div>
                            <div class="mt-2 text-xs text-[color:var(--ui-muted)]">
                                {{ Math.round(quota.usage_percentage) }}% used
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-[color:var(--ui-border)]">
                                <button
                                    @click="recalculateQuota"
                                    :disabled="recalculatingQuota"
                                    class="w-full px-4 py-2.5 rounded-xl text-sm font-medium bg-[color:var(--ui-surface)] hover:bg-[color:var(--ui-hover)] border border-[color:var(--ui-border)] transition-colors disabled:opacity-50"
                                >
                                    {{ recalculatingQuota ? 'Recalculating...' : 'Refresh Storage Info' }}
                                </button>
                            </div>
                            
                            <div class="mt-4 text-xs text-[color:var(--ui-muted)]">
                                Storage quota is managed by your administrator. Contact support if you need more space.
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Create Folder Modal -->
        <teleport to="body">
            <div v-if="showCreateFolder" class="fixed inset-0 bg-[color:var(--ui-overlay)]/60 backdrop-blur-sm flex items-center justify-center z-[100]">
                <div class="bg-[color:var(--ui-surface-strong)] rounded-2xl shadow-2xl w-96 p-6 border border-[color:var(--ui-border)] transform transition-all scale-100">
                    <h3 class="text-lg font-bold text-[color:var(--ui-fg)] mb-1 font-heading">New folder</h3>
                    <p class="text-sm text-[color:var(--ui-muted)] mb-4">Enter a name for your new folder</p>
                    
                    <input
                        v-model="newFolderName"
                        @keyup.enter="createFolder"
                        type="text"
                        placeholder="Untitled folder"
                        class="w-full px-4 py-3 bg-white/70 dark:bg-black/20 border border-[color:var(--ui-border)] rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-transparent text-[color:var(--ui-fg)] placeholder-[color:var(--ui-muted-2)] mb-6"
                        autofocus
                    />
                    
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showCreateFolder = false"
                            class="px-5 py-2.5 text-sm font-medium text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] rounded-xl transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="createFolder"
                            :disabled="!newFolderName.trim()"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg shadow-blue-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Create Folder
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- Rename Modal -->
        <teleport to="body">
            <div v-if="showRename" class="fixed inset-0 bg-[color:var(--ui-overlay)]/60 backdrop-blur-sm flex items-center justify-center z-[100]">
                <div class="bg-[color:var(--ui-surface-strong)] rounded-2xl shadow-2xl w-96 p-6 border border-[color:var(--ui-border)]">
                    <h3 class="text-lg font-bold text-[color:var(--ui-fg)] mb-1 font-heading">Rename</h3>
                    <p class="text-sm text-[color:var(--ui-muted)] mb-4">Enter a new name for the item</p>
                    <input
                        v-model="newFileName"
                        @keyup.enter="confirmRename"
                        type="text"
                        class="w-full px-4 py-3 bg-white/70 dark:bg-black/20 border border-[color:var(--ui-border)] rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-transparent text-[color:var(--ui-fg)] placeholder-[color:var(--ui-muted-2)] mb-6"
                        autofocus
                    />
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showRename = false"
                            class="px-5 py-2.5 text-sm font-medium text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] rounded-xl transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmRename"
                            :disabled="!newFileName.trim()"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg shadow-blue-600/20 transition-all disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- File Upload Component -->
        <FileUpload
            ref="uploadRef"
            :show="showUpload"
            :current-path="currentPath"
            @close="showUpload = false"
            @started="showUpload = true"
            @upload-complete="handleUploadComplete"
            @show-error="showError"
        />

        <!-- Share Modal -->
        <ShareModal
            :show="showShare"
            :item="shareItem"
            :current-user="user"
            @close="showShare = false"
            @share-updated="handleShareUpdated"
        />

        <!-- File Preview -->
        <FilePreview
            :show="showPreview"
            :file="previewFile"
            @close="showPreview = false"
            @download="downloadFile"
        />

        <!-- Toast Notifications -->
        <Toast ref="toastRef" @retry="handleRetry" />

        <!-- Confirm Modal -->
        <ConfirmModal
            ref="confirmModalRef"
            :type="confirmModalType"
            :title="confirmModalTitle"
            :message="confirmModalMessage"
            :confirmText="confirmModalConfirmText"
            @confirm="handleModalConfirm"
            @cancel="handleModalCancel"
        />
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

// Use the globally configured axios with CSRF token from bootstrap.js
const axios = window.axios;
import FileList from '../Components/FileList.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import FileUpload from '../Components/FileUpload.vue';
import SearchBar from '../Components/SearchBar.vue';
import Toast from '../Components/Toast.vue';
import ThemeToggle from '../Components/ThemeToggle.vue';
import UserMenu from '../Components/UserMenu.vue';
import ConfirmModal from '../Components/ConfirmModal.vue';
import ShareModal from '../Components/ShareModal.vue';
import FilePreview from '../Components/FilePreview.vue';
import NotificationBell from '../Components/NotificationBell.vue';

const props = defineProps({
    user: Object,
    quota: Object,
    user_path: String,
    adminData: Object,
});

// Reactive data
const files = ref([]);
const breadcrumbs = ref([]);
const currentPath = ref('');
const showNewMenu = ref(false);
const loading = ref(false);
const activeView = ref('my-drive');
const viewMode = ref('grid');
const selectedFiles = ref([]);
const sharedItems = ref([]);
const recentFiles = ref([]);
const starredFiles = ref([]);
const trashFiles = ref([]);

// Create reactive quota data for real-time updates
const quota = ref({ ...props.quota });
const recalculatingQuota = ref(false);

// Admin panel data
const adminUsers = ref(props.adminData?.users?.map(u => ({ ...u, editQuotaGb: bytesToGb(u.quota_bytes) })) || []);
const adminStats = ref(props.adminData?.stats || {});
const selectedAdminUsers = ref([]);
const bulkQuotaGb = ref(5);
const updatingUserId = ref(null);
const recalculatingUserId = ref(null);
const togglingAdminId = ref(null);

function bytesToGb(bytes) {
    return Math.round((bytes / (1024 * 1024 * 1024)) * 10) / 10;
}

// Modal states
const showCreateFolder = ref(false);
const showRename = ref(false);
const showUpload = ref(false);
const showShare = ref(false);
const showPreview = ref(false);

// Form data
const newFolderName = ref('');
const newFileName = ref('');
const renameFile = ref(null);
const deleteFile = ref(null);
const shareItem = ref(null);
const previewFile = ref(null);

// Refs
const toastRef = ref(null);
const confirmModalRef = ref(null);
const uploadRef = ref(null);

// Modal state
const confirmModalType = ref('danger');
const confirmModalTitle = ref('Confirm');
const confirmModalMessage = ref('Are you sure?');
const confirmModalConfirmText = ref('Confirm');
const confirmModalResolve = ref(null);

// Load files on component mount
onMounted(() => {
    // Check URL for view parameter
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    if (viewParam === 'profile') {
        activeView.value = 'profile';
    } else if (viewParam === 'admin' && props.user.is_admin) {
        activeView.value = 'admin';
    }
    
    loadFiles();
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
});

const userInitials = computed(() => {
    const name = (props.user.display_name || props.user.name || '').trim();
    if (!name) return '?';
    const parts = name.split(/\s+/).slice(0, 2);
    return parts.map(p => p[0]?.toUpperCase()).join('');
});

const formatLastLogin = (lastLogin) => {
    if (!lastLogin) return 'Never';
    try {
        const d = new Date(lastLogin);
        return d.toLocaleString();
    } catch {
        return lastLogin;
    }
};

const folderStats = computed(() => {
    const dirs = files.value.filter(f => f.is_directory).length;
    const fls = files.value.filter(f => !f.is_directory).length;
    return { total: files.value.length, folders: dirs, files: fls };
});

const logout = () => {
    router.post('/logout');
};

const toggleView = () => {
    viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
};

// Modal functions
const showConfirm = (options) => {
    return new Promise((resolve) => {
        confirmModalType.value = options.type || 'danger';
        confirmModalTitle.value = options.title || 'Confirm';
        confirmModalMessage.value = options.message || 'Are you sure?';
        confirmModalConfirmText.value = options.confirmText || 'Confirm';
        confirmModalResolve.value = resolve;
        confirmModalRef.value.open();
    });
};

const handleModalConfirm = () => {
    if (confirmModalResolve.value) {
        confirmModalResolve.value(true);
        confirmModalResolve.value = null;
    }
};

const handleModalCancel = () => {
    if (confirmModalResolve.value) {
        confirmModalResolve.value(false);
        confirmModalResolve.value = null;
    }
};

const toggleFileSelection = (filePath) => {
    const index = selectedFiles.value.indexOf(filePath);
    if (index > -1) {
        selectedFiles.value.splice(index, 1);
    } else {
        selectedFiles.value.push(filePath);
    }
};

const getSelectedFileObjects = () => {
    return files.value.filter(file => selectedFiles.value.includes(file.path));
};

const loadFiles = async (path = '') => {
    loading.value = true;
    selectedFiles.value = [];
    try {
        const response = await axios.get('/api/files', {
            params: { path: path || '' }
        });
        
        if (response.data.success) {
            files.value = response.data.files;
            breadcrumbs.value = response.data.breadcrumbs;
            currentPath.value = response.data.current_path;
            
            // Update quota information in real-time
            if (response.data.quota) {
                quota.value = response.data.quota;
            }
        } else {
            showError(response.data.error, response.data.can_retry);
        }
    } catch (error) {
        console.error('Error loading files:', error);
        showError('Failed to load files. Please try again.', true);
    } finally {
        loading.value = false;
    }
};

const refreshFiles = () => {
    loadFiles(currentPath.value);
};

const navigateToPath = (path) => {
    if (path === 'SHARED_ROOT') {
        activeView.value = 'shared';
        loadSharedFiles();
        return;
    }
    loadFiles(path);
};

const openFolder = (folder) => {
    const newPath = currentPath.value ? `${currentPath.value}/${folder.name}` : folder.name;
    loadFiles(newPath);
};

const downloadFile = async (file) => {
    try {
        const response = await axios.get('/api/files/download', {
            params: { path: file.path },
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', file.name);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error downloading file:', error);
        showError('Failed to download file: ' + error.message);
    }
};

const downloadSelectedAsZip = async () => {
    if (selectedFiles.value.length < 2) return;
    
    loading.value = true;
    try {
        const response = await axios.post('/api/files/download-zip', {
            paths: selectedFiles.value
        }, {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `nimr_drive_export_${new Date().getTime()}.zip`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        
        showSuccess(`Packaged ${selectedFiles.value.length} items successfully`);
    } catch (error) {
        console.error('Error downloading ZIP:', error);
        showError('Failed to create ZIP archive');
    } finally {
        loading.value = false;
    }
};

const showCreateFolderDialog = () => {
    newFolderName.value = '';
    showCreateFolder.value = true;
};

const createFolder = async () => {
    if (!newFolderName.value.trim()) return;
    
    try {
        const response = await axios.post('/api/files/folder', {
            path: currentPath.value,
            name: newFolderName.value.trim()
        });
        
        if (response.data.success) {
            showCreateFolder.value = false;
            showSuccess(`Folder "${newFolderName.value}" created`, 'Folder created');
            loadFiles(currentPath.value);
        } else {
            showError(response.data.error, response.data.can_retry);
        }
    } catch (error) {
        console.error('Error creating folder:', error);
        const errorMsg = error.response?.data?.error || 'Failed to create folder';
        showError(errorMsg, error.response?.data?.can_retry);
    }
};

const showRenameDialog = (file) => {
    renameFile.value = file;
    newFileName.value = file.name;
    showRename.value = true;
};

const confirmRename = async () => {
    if (!newFileName.value.trim() || !renameFile.value) return;
    
    try {
        const response = await axios.put('/api/files/rename', {
            path: renameFile.value.path,
            new_name: newFileName.value.trim()
        });
        
        if (response.data.success) {
            showRename.value = false;
            showSuccess(`Renamed to "${newFileName.value}"`, 'Rename complete');
            loadFiles(currentPath.value);
        } else {
            showError(response.data.error, response.data.can_retry);
        }
    } catch (error) {
        console.error('Error renaming file:', error);
        const errorMsg = error.response?.data?.error || 'Failed to rename file';
        showError(errorMsg, error.response?.data?.can_retry);
    }
};

const showDeleteDialog = async (file) => {
    const confirmed = await showConfirm({
        type: 'warning',
        title: 'Move to Trash?',
        message: `"${file.name}" will be moved to the trash. You can restore it later from the Trash sidebar.`,
        confirmText: 'Move to Trash'
    });
    
    if (confirmed) {
        deleteFile.value = file;
        confirmDelete();
    }
};

const confirmDelete = async () => {
    if (!deleteFile.value) return;
    
    try {
        const response = await axios.delete('/api/files/delete', {
            data: { path: deleteFile.value.path }
        });
        
        if (response.data.success) {
            showSuccess(`"${deleteFile.value.name}" moved to trash`, 'Task complete');
            loadFiles(currentPath.value);
            // Also refresh other views if they are active
            if (activeView.value === 'recent') loadRecentFiles();
            if (activeView.value === 'starred') loadStarredFiles();
        } else {
            showError(response.data.error, response.data.can_retry);
        }
    } catch (error) {
        console.error('Error deleting file:', error);
        const errorMsg = error.response?.data?.error || 'Failed to move to trash';
        showError(errorMsg, error.response?.data?.can_retry);
    } finally {
        deleteFile.value = null;
    }
};

const handleFileClick = (file) => {
    if (file.is_directory) {
        if (file.is_trash) return;
        loadFiles(file.path);
    } else {
        previewFile.value = file;
        showPreview.value = true;
    }
};

const handleFileMove = async ({ source, targetPath }) => {
    try {
        const response = await axios.post('/api/files/move', {
            source_path: source.path,
            target_directory: targetPath
        });
        
        if (response.data.success) {
            showSuccess(`Moved "${source.name}" successfully`);
            loadFiles(currentPath.value);
            // Also refresh other views if they are active
            if (activeView.value === 'recent') loadRecentFiles();
            if (activeView.value === 'starred') loadStarredFiles();
        } else {
            showError(response.data.error);
        }
    } catch (error) {
        console.error('Error moving file:', error);
        showError(error.response?.data?.error || 'Failed to move item');
    }
};

const showUploadDialog = () => {
    if (uploadRef.value) {
        uploadRef.value.triggerFileSelect();
    }
};

const showFolderUploadDialog = () => {
    if (uploadRef.value) {
        uploadRef.value.triggerFolderSelect();
    }
};

const showShareDialog = (file) => {
    shareItem.value = file;
    showShare.value = true;
};

const handleShareUpdated = () => {
    // Optionally refresh something
};

const toggleStar = async (file) => {
    try {
        const response = await axios.post('/api/stars/toggle', {
            path: file.path,
            is_directory: file.is_directory
        });
        
        if (response.data.success) {
            file.is_starred = response.data.is_starred;
            // Update in other lists if present
            [files, recentFiles, starredFiles].forEach(list => {
                const item = list.value.find(f => f.path === file.path);
                if (item) item.is_starred = file.is_starred;
            });
            
            if (activeView.value === 'starred' && !file.is_starred) {
                starredFiles.value = starredFiles.value.filter(f => f.path !== file.path);
            } else if (activeView.value === 'starred' && file.is_starred) {
                loadStarredFiles();
            }
        }
    } catch (error) {
        console.error('Error toggling star:', error);
        showError('Failed to update star status');
    }
};

const loadRecentFiles = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/recents');
        if (response.data.success) {
            recentFiles.value = response.data.items;
        }
    } catch (error) {
        console.error('Error loading recent files:', error);
        showError('Failed to load recent files');
    } finally {
        loading.value = false;
    }
};

const loadStarredFiles = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/starred');
        if (response.data.success) {
            starredFiles.value = response.data.items;
        }
    } catch (error) {
        console.error('Error loading starred files:', error);
        showError('Failed to load starred files');
    } finally {
        loading.value = false;
    }
};

const loadTrashFiles = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/files/trash');
        if (response.data.success) {
            trashFiles.value = response.data.files;
        }
    } catch (error) {
        console.error('Error loading trash:', error);
        showError('Failed to load trash');
    } finally {
        loading.value = false;
    }
};

const restoreFile = async (file) => {
    try {
        const response = await axios.post('/api/files/restore', { path: file.path });
        if (response.data.success) {
            showSuccess(`Restored "${file.name}"`);
            loadTrashFiles();
        }
    } catch (error) {
        showError('Failed to restore item');
    }
};

const confirmPermanentDelete = async (file) => {
    const confirmed = await showConfirm({
        title: 'Delete Permanently?',
        message: `"${file.name}" will be gone forever. This cannot be undone.`,
        confirmText: 'Delete Forever'
    });

    if (!confirmed) return;

    try {
        const response = await axios.delete('/api/files/permanent', { data: { path: file.path } });
        if (response.data.success) {
            showSuccess('Item deleted permanently');
            loadTrashFiles();
        }
    } catch (error) {
        showError('Failed to delete item');
    }
};

const emptyTrash = async () => {
    const confirmed = await showConfirm({
        title: 'Empty Trash?',
        message: 'All items in the trash will be permanently deleted. This cannot be undone.',
        confirmText: 'Empty Everything'
    });

    if (!confirmed) return;

    try {
        for (const file of trashFiles.value) {
            await axios.delete('/api/files/permanent', { data: { path: file.path } });
        }
        showSuccess('Trash emptied');
        loadTrashFiles();
    } catch (error) {
        showError('Failed to empty trash completely');
    }
};

const loadSharedFiles = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/shares');
        if (response.data.success) {
            sharedItems.value = response.data.shares;
        }
    } catch (error) {
        console.error('Error loading shared files:', error);
        showError('Failed to load shared files');
    } finally {
        loading.value = false;
    }
};

const getShareName = (share) => {
    return share.path.split('/').pop() || 'Shared item';
};

const openSharedItem = (share) => {
    // Construct the absolute path for shared items
    // This allows the middleware to recognize it as a shared path
    const absolutePath = `users/${share.owner.ad_username || share.owner.id}/files/${share.path}`;
    loadFiles(absolutePath);
    activeView.value = 'my-drive'; // Switch to file view
};

const downloadSharedItem = async (share) => {
    const absolutePath = `users/${share.owner.ad_username || share.owner.id}/files/${share.path}`;
    try {
        const response = await axios.get('/api/files/download', {
            params: { path: absolutePath },
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', getShareName(share));
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error downloading shared item:', error);
        showError('Failed to download item');
    }
};

const removeSharedWithMe = async (share) => {
    const confirmed = await showConfirm({
        title: 'Remove shared item',
        message: `Remove "${getShareName(share)}" from your shared items? You will lose access.`,
        confirmText: 'Remove'
    });

    if (!confirmed) return;

    try {
        const response = await axios.delete(`/api/shares/${share.id}`);
        if (response.data.success) {
            showSuccess('Item removed');
            loadSharedFiles();
        }
    } catch (error) {
        console.error('Error removing share:', error);
        showError('Failed to remove item');
    }
};

const getInitials = (user) => {
    if (!user) return '?';
    const name = user.display_name || user.name || '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const recalculateQuota = async () => {
    recalculatingQuota.value = true;
    try {
        const response = await axios.post('/api/quota/recalculate');
        if (response.data.success) {
            quota.value = response.data.quota;
        } else {
            showWarning('Failed to recalculate quota');
        }
    } catch (error) {
        console.error('Error recalculating quota:', error);
        showError('Failed to recalculate quota');
    } finally {
        recalculatingQuota.value = false;
    }
};

const handleUploadComplete = (data) => {
    console.log('Upload complete, reloading files...', data);
    loadFiles(currentPath.value);
    
    if (data?.count) {
        // Multiple files uploaded
        showSuccess(`${data.count} file(s) uploaded successfully`, 'Upload complete');
    } else {
        // Single file uploaded
        const name = data?.name || data?.file_name || 'File';
        showSuccess(`Uploaded "${name}"`, 'Upload complete');
    }
};

const handleBatchDelete = async (selectedFileObjects) => {
    if (selectedFileObjects.length === 0) return;
    
    const fileCount = selectedFileObjects.length;
    
    const confirmed = await showConfirm({
        type: 'warning',
        title: 'Move to Trash',
        message: `Move ${fileCount} item(s) to the trash? You can restore them later from the Trash sidebar.`,
        confirmText: 'Move to Trash'
    });
    
    if (!confirmed) return;
    
    try {
        const paths = selectedFileObjects.map(file => file.path);
        const response = await axios.delete('/api/files/batch-delete', {
            data: { paths: paths }
        });
        
        if (response.data.success) {
            showSuccess(`${fileCount} item(s) moved to trash`, 'Task complete');
            loadFiles(currentPath.value);
            selectedFiles.value = [];
        }
    } catch (error) {
        console.error('Error moving files to trash:', error);
        showError('Failed to move files to trash');
    }
};

const handleSearchPerformed = (searchData) => {
    console.log('Search performed:', searchData);
};

const handleSearchCleared = () => {
    loadFiles(currentPath.value);
};

const handleNavigateToResult = (data) => {
    console.log('Navigate to result:', data);
};

// Toast notification helpers
const showSuccess = (message, title = 'Success') => {
    if (toastRef.value) {
        toastRef.value.addToast({
            type: 'success',
            title: title,
            message: message,
            duration: 3000
        });
    }
};

const showError = (message, canRetry = false, title = 'Error') => {
    if (toastRef.value) {
        toastRef.value.addToast({
            type: 'error',
            title: title,
            message: message,
            canRetry: canRetry,
            duration: canRetry ? 0 : 5000
        });
    }
};

const showWarning = (message, title = 'Warning') => {
    if (toastRef.value) {
        toastRef.value.addToast({
            type: 'warning',
            title: title,
            message: message,
            duration: 4000
        });
    }
};

const showInfo = (message, title = 'Info') => {
    if (toastRef.value) {
        toastRef.value.addToast({
            type: 'info',
            title: title,
            message: message,
            duration: 3000
        });
    }
};

const handleRetry = (toastId) => {
    // Retry the last failed operation
    loadFiles(currentPath.value);
    if (toastRef.value) {
        toastRef.value.removeToast(toastId);
    }
};

// Admin panel methods
const toggleSelectAllUsers = (e) => {
    if (e.target.checked) {
        selectedAdminUsers.value = adminUsers.value.map(u => u.id);
    } else {
        selectedAdminUsers.value = [];
    }
};

const toggleUserSelection = (userId) => {
    const index = selectedAdminUsers.value.indexOf(userId);
    if (index > -1) {
        selectedAdminUsers.value.splice(index, 1);
    } else {
        selectedAdminUsers.value.push(userId);
    }
};

const updateUserQuota = async (adminUser) => {
    updatingUserId.value = adminUser.id;
    try {
        const response = await axios.put(`/admin/users/${adminUser.id}/quota`, {
            quota_gb: adminUser.editQuotaGb
        });
        if (response.data.success) {
            adminUser.quota_bytes = response.data.user.quota_bytes;
            adminUser.quota_formatted = response.data.user.quota_formatted;
            adminUser.usage_percentage = response.data.user.usage_percentage;
            showSuccess(response.data.message);
        }
    } catch (error) {
        showError(error.response?.data?.error || 'Failed to update quota');
    } finally {
        updatingUserId.value = null;
    }
};

const recalculateUserUsage = async (adminUser) => {
    recalculatingUserId.value = adminUser.id;
    try {
        const response = await axios.post(`/admin/users/${adminUser.id}/recalculate`);
        if (response.data.success) {
            adminUser.used_bytes = response.data.used_bytes;
            adminUser.used_formatted = response.data.used_formatted;
            adminUser.usage_percentage = response.data.usage_percentage;
            showSuccess(response.data.message);
        }
    } catch (error) {
        showError(error.response?.data?.error || 'Failed to recalculate');
    } finally {
        recalculatingUserId.value = null;
    }
};

const toggleUserAdmin = async (adminUser) => {
    togglingAdminId.value = adminUser.id;
    try {
        const response = await axios.post(`/admin/users/${adminUser.id}/toggle-admin`);
        if (response.data.success) {
            adminUser.is_admin = response.data.is_admin;
            showSuccess(response.data.message);
        }
    } catch (error) {
        showError(error.response?.data?.error || 'Failed to update admin status');
    } finally {
        togglingAdminId.value = null;
    }
};

const applyBulkQuota = async () => {
    if (selectedAdminUsers.value.length === 0 || !bulkQuotaGb.value) return;
    
    try {
        const response = await axios.post('/admin/users/bulk-quota', {
            user_ids: selectedAdminUsers.value,
            quota_gb: bulkQuotaGb.value
        });
        if (response.data.success) {
            const quotaBytes = bulkQuotaGb.value * 1024 * 1024 * 1024;
            adminUsers.value.forEach(user => {
                if (selectedAdminUsers.value.includes(user.id)) {
                    user.quota_bytes = quotaBytes;
                    user.editQuotaGb = bulkQuotaGb.value;
                    user.usage_percentage = user.quota_bytes > 0 
                        ? Math.round((user.used_bytes / user.quota_bytes) * 1000) / 10 
                        : 0;
                }
            });
            selectedAdminUsers.value = [];
            showSuccess(response.data.message);
        }
    } catch (error) {
        showError(error.response?.data?.error || 'Failed to update quotas');
    }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
