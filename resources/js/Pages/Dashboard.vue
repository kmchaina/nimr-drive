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
                    <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-blue-600 rounded-xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center shadow-2xl shadow-indigo-500/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
                        </svg>
                    </div>
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
                    class="group w-full flex items-center justify-center px-4 py-3.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white rounded-2xl shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:shadow-indigo-500/40 hover:-translate-y-0.5 border border-[color:var(--ui-border)]"
                >
                    <svg class="w-5 h-5 mr-2 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="font-semibold tracking-wide">New Item</span>
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

                    <UserMenu :user="user" @logout="logout" />
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto px-10 pb-10 custom-scrollbar">
                <!-- My Files View -->
                <div v-if="activeView === 'my-drive'">
                    <!-- Premium overview header -->
                    <div class="mt-4 mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                        <div>
                            <div class="text-sm text-[color:var(--ui-muted)]">Dashboard</div>
                            <h2 class="mt-1 text-3xl font-bold text-[color:var(--ui-fg)] tracking-tight font-heading">
                                {{ greeting }}, {{ user.display_name || user.name }}
                            </h2>
                            <p class="mt-2 text-sm text-[color:var(--ui-muted)] max-w-2xl">
                                Manage your files and storage quota—all in one place.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto">
                            <div class="rounded-2xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] p-4 backdrop-blur-md">
                                <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Storage used</div>
                                <div class="mt-2 flex items-baseline justify-between gap-3">
                                    <div class="text-lg font-semibold text-[color:var(--ui-fg)]">{{ quota.used_formatted }}</div>
                                    <div class="text-xs text-[color:var(--ui-muted)]">of {{ quota.total_formatted }}</div>
                                </div>
                                <div class="mt-3 h-2 rounded-full bg-black/5 dark:bg-white/10 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"
                                        :style="{ width: Math.min(quota.usage_percentage, 100) + '%' }"
                                    />
                                </div>
                            </div>

                            <div class="rounded-2xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] p-4 backdrop-blur-md">
                                <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Items</div>
                                <div class="mt-2 text-lg font-semibold text-[color:var(--ui-fg)]">{{ folderStats.total }}</div>
                                <div class="mt-1 text-xs text-[color:var(--ui-muted)]">
                                    {{ folderStats.folders }} folder{{ folderStats.folders !== 1 ? 's' : '' }} •
                                    {{ folderStats.files }} file{{ folderStats.files !== 1 ? 's' : '' }}
                                </div>
                            </div>

                            <div class="rounded-2xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] p-4 backdrop-blur-md">
                                <div class="text-xs text-[color:var(--ui-muted)] uppercase tracking-wider font-semibold">Selected</div>
                                <div class="mt-2 text-lg font-semibold text-[color:var(--ui-fg)]">{{ selectedFiles.length }}</div>
                                <div class="mt-1 text-xs text-[color:var(--ui-muted)]">
                                    {{ selectedFiles.length ? 'Batch actions available' : 'Select items to manage' }}
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
                            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                                <button
                                    v-if="selectedFiles.length > 0"
                                    @click="handleBatchDelete(getSelectedFileObjects())"
                                    class="inline-flex items-center px-4 py-2 bg-red-500/10 border border-red-500/20 rounded-xl text-sm font-medium text-red-500 hover:bg-red-500/20 transition-all duration-300 shadow-lg shadow-red-500/10"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete ({{ selectedFiles.length }})
                                </button>
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
                        @folder-open="openFolder"
                        @file-download="downloadFile"
                        @file-rename="showRenameDialog"
                        @file-delete="showDeleteDialog"
                        @file-select="toggleFileSelection"
                        @refresh="loadFiles"
                    />
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
            </main>
        </div>

        <!-- Create Folder Modal -->
        <div v-if="showCreateFolder" class="fixed inset-0 bg-[color:var(--ui-overlay)]/60 backdrop-blur-sm flex items-center justify-center z-50">
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

        <!-- Rename Modal -->
        <div v-if="showRename" class="fixed inset-0 bg-[color:var(--ui-overlay)]/60 backdrop-blur-sm flex items-center justify-center z-50">
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

        <!-- Delete Confirmation Modal -->
        <div v-if="showDelete" class="fixed inset-0 bg-[color:var(--ui-overlay)]/60 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-[color:var(--ui-surface-strong)] rounded-2xl shadow-2xl w-96 p-6 border border-[color:var(--ui-border)]">
                <div class="mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-[color:var(--ui-fg)] font-heading">Delete forever?</h3>
                    <p class="text-sm text-[color:var(--ui-muted)] mt-2">
                        Are you sure you want to delete "<span class="text-[color:var(--ui-fg)] font-medium">{{ deleteFile?.name }}</span>"? This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button
                        @click="showDelete = false"
                        class="px-5 py-2.5 text-sm font-medium text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] rounded-xl transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDelete"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-500 rounded-xl shadow-lg shadow-red-600/20 transition-all"
                    >
                        Delete Forever
                    </button>
                </div>
            </div>
        </div>

        <!-- File Upload Component -->
        <FileUpload
            :show="showUpload"
            :current-path="currentPath"
            @close="showUpload = false"
            @upload-complete="handleUploadComplete"
            @show-error="showError"
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
import axios from 'axios';
import FileList from '../Components/FileList.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import FileUpload from '../Components/FileUpload.vue';
import SearchBar from '../Components/SearchBar.vue';
import Toast from '../Components/Toast.vue';
import ThemeToggle from '../Components/ThemeToggle.vue';
import UserMenu from '../Components/UserMenu.vue';
import ConfirmModal from '../Components/ConfirmModal.vue';

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
const showDelete = ref(false);
const showUpload = ref(false);

// Form data
const newFolderName = ref('');
const newFileName = ref('');
const renameFile = ref(null);
const deleteFile = ref(null);

// Refs
const toastRef = ref(null);
const confirmModalRef = ref(null);

// Modal state
const confirmModalType = ref('danger');
const confirmModalTitle = ref('Confirm');
const confirmModalMessage = ref('Are you sure?');
const confirmModalConfirmText = ref('Confirm');
const confirmModalResolve = ref(null);

// Load files on component mount
onMounted(() => {
    loadFiles();
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
});

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

const navigateToPath = (path) => {
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

const showDeleteDialog = (file) => {
    deleteFile.value = file;
    showDelete.value = true;
};

const confirmDelete = async () => {
    if (!deleteFile.value) return;
    
    try {
        const response = await axios.delete('/api/files/delete', {
            data: { path: deleteFile.value.path }
        });
        
        if (response.data.success) {
            showDelete.value = false;
            showSuccess(`"${deleteFile.value.name}" deleted`, 'Delete complete');
            loadFiles(currentPath.value);
        } else {
            showError(response.data.error, response.data.can_retry);
        }
    } catch (error) {
        console.error('Error deleting file:', error);
        const errorMsg = error.response?.data?.error || 'Failed to delete file';
        showError(errorMsg, error.response?.data?.can_retry);
    }
};

const showUploadDialog = () => {
    showUpload.value = true;
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
        type: 'danger',
        title: 'Delete Files',
        message: `Delete ${fileCount} item(s) forever? You can't undo this action.`,
        confirmText: 'Delete'
    });
    
    if (!confirmed) return;
    
    try {
        const paths = selectedFileObjects.map(file => file.path);
        const response = await axios.delete('/api/files/batch-delete', {
            data: { paths: paths }
        });
        
        if (response.data.success) {
            showSuccess(`${fileCount} item(s) deleted`, 'Delete complete');
            loadFiles(currentPath.value);
            selectedFiles.value = [];
        }
    } catch (error) {
        console.error('Error deleting files:', error);
        showError('Failed to delete files');
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
