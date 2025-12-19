<template>
    <div class="file-list">
        <!-- Empty State -->
        <div v-if="files.length === 0" class="flex flex-col items-center justify-center py-32 text-center animate-fade-in-up">
            <div class="relative group">
                <div class="absolute -inset-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full opacity-20 group-hover:opacity-40 blur-xl transition duration-500"></div>
                <div class="relative w-24 h-24 rounded-3xl bg-[color:var(--ui-surface-strong)] border border-[color:var(--ui-border)] flex items-center justify-center mb-8 shadow-2xl shadow-indigo-500/10 group-hover:scale-105 transition-transform duration-300">
                    <svg class="h-12 w-12 text-indigo-400 group-hover:text-indigo-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-[color:var(--ui-fg)] mb-3 tracking-tight">It’s quiet here</h3>
            <p class="text-[color:var(--ui-muted)] mb-8 max-w-md mx-auto leading-relaxed">Your workspace is ready. Upload files or create a folder to get started.</p>
        </div>

        <!-- Grid View -->
        <div v-else-if="props.viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
            <div
                v-for="file in files"
                :key="file.path"
                :data-file-name="file.name"
                @click="handleFileClick(file)"
                @contextmenu.prevent="showContextMenu(file, $event)"
                class="file-item group cursor-pointer p-5 rounded-3xl bg-[color:var(--ui-surface-strong)] border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-hover)] transition-all duration-300 relative overflow-hidden"
                :class="{ 'ring-2 ring-indigo-500 bg-indigo-500/5': props.selectedFiles.includes(file.path) }"
            >
                <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                <!-- Selection Indicator -->
                 <div v-if="props.selectedFiles.includes(file.path)" class="absolute top-3 right-3 w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center shadow-lg transform scale-100 transition-transform">
                     <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                 </div>
                
                <div class="text-center relative z-10 flex flex-col h-full">
                    <!-- File Icon -->
                    <div class="mb-5 transform group-hover:scale-110 transition-transform duration-500 ease-out-expo flex-1 flex items-center justify-center">
                        <svg v-if="file.is_directory" class="w-20 h-20 text-blue-500 drop-shadow-2xl" fill="currentColor" viewBox="0 0 24 24">
                           <defs>
                                <linearGradient id="folderGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#60A5FA;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#3B82F6;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z" fill="url(#folderGradient)"/>
                             <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z" fill="white" fill-opacity="0.1"/>
                        </svg>
                        <div v-else class="w-16 h-16 flex items-center justify-center rounded-2xl bg-black/5 dark:bg-white/5 text-[color:var(--ui-muted)] group-hover:text-indigo-500 group-hover:bg-indigo-500/10 transition-colors shadow-inner border border-[color:var(--ui-border)]">
                             <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- File Name -->
                    <div class="mt-auto">
                        <div class="text-sm font-medium text-[color:var(--ui-fg)] truncate px-1 transition-colors tracking-tight" :title="file.name">
                            {{ file.name }}
                        </div>
                        
                        <!-- File Size -->
                        <div v-if="!file.is_directory" class="text-xs text-[color:var(--ui-muted)] mt-1 font-mono opacity-70">
                            {{ file.size_formatted }}
                        </div>
                         <div v-else class="text-xs text-[color:var(--ui-muted)] mt-1 font-mono opacity-70">
                            Folder
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-else class="bg-[color:var(--ui-surface)] backdrop-blur-md rounded-xl border border-[color:var(--ui-border)] overflow-hidden shadow-xl">
            <table class="min-w-full divide-y divide-[color:var(--ui-border)]">
                <thead class="bg-black/5 dark:bg-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Name</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Size</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Modified</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--ui-border)]">
                    <tr
                        v-for="file in files"
                        :key="file.path"
                        :data-file-name="file.name"
                        @click="handleFileClick(file)"
                        @contextmenu.prevent="showContextMenu(file, $event)"
                        class="hover:bg-[color:var(--ui-hover)] cursor-pointer transition-colors"
                        :class="{ 'bg-blue-500/10': props.selectedFiles.includes(file.path) }"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <svg v-if="file.is_directory" class="w-5 h-5 mr-3 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z"/>
                                </svg>
                                <svg v-else class="w-5 h-5 mr-3 text-[color:var(--ui-muted-2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span class="text-sm font-medium text-[color:var(--ui-fg)]">{{ file.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[color:var(--ui-muted)]">
                            {{ file.is_directory ? '—' : file.size_formatted }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[color:var(--ui-muted)]">
                            {{ formatDate(file.modified) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[color:var(--ui-muted)]">
                            <button
                                @click.stop="showContextMenu(file, $event)"
                                class="text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] transition-colors"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Context Menu -->
        <div
            v-if="contextMenu.show"
            :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
            class="fixed z-50 bg-[color:var(--ui-surface-strong)] backdrop-blur-xl rounded-2xl shadow-2xl border border-[color:var(--ui-border)] py-2 min-w-[180px] transform transition-all duration-200 scale-100 origin-top-left ring-1 ring-black/5"
        >
            <button
                v-if="contextMenu.file.is_directory"
                @click="openFolder(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group"
            >
                <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20 transition-colors">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
                </div>
                Open
            </button>
            <button
                v-else
                @click="downloadFile(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group"
            >
                 <div class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500/20 transition-colors">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                Download
            </button>
            <button
                @click="renameFile(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group"
            >
                 <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 group-hover:bg-amber-500/20 transition-colors">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                Rename
            </button>
            <div class="h-px bg-[color:var(--ui-border)] my-1 mx-4"></div>
            <button
                @click="deleteFile(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 flex items-center gap-3 transition-colors group"
            >
                 <div class="p-1.5 rounded-lg bg-red-500/10 text-red-400 group-hover:bg-red-500/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                Delete
            </button>
        </div>

        <!-- Overlay to close context menu -->
        <div
            v-if="contextMenu.show"
            @click="contextMenu.show = false"
            class="fixed inset-0 z-40"
        ></div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';

const props = defineProps({
    files: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    viewMode: {
        type: String,
        default: 'grid'
    },
    selectedFiles: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits([
    'folder-open',
    'file-download',
    'file-rename',
    'file-delete',
    'file-select',
    'refresh'
]);

const contextMenu = reactive({
    show: false,
    x: 0,
    y: 0,
    file: null
});

const handleFileClick = (file) => {
    if (file.is_directory) {
        openFolder(file);
    } else {
        // Emit selection event to parent
        emit('file-select', file.path);
    }
};

const openFolder = (file) => {
    contextMenu.show = false;
    emit('folder-open', file);
};

const downloadFile = (file) => {
    contextMenu.show = false;
    emit('file-download', file);
};

const renameFile = (file) => {
    contextMenu.show = false;
    emit('file-rename', file);
};

const deleteFile = (file) => {
    contextMenu.show = false;
    emit('file-delete', file);
};

const showContextMenu = (file, event) => {
    contextMenu.file = file;
    contextMenu.x = event.clientX;
    contextMenu.y = event.clientY;
    contextMenu.show = true;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<style scoped>
.file-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.file-item:hover {
    transform: translateY(-2px);
}
</style>