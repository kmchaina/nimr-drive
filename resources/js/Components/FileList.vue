<template>
    <div class="file-list" @dragover.prevent @drop="handleRootDrop">
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
                draggable="true"
                @dragstart="handleDragStart($event, file)"
                @dragover.prevent="handleDragOver($event, file)"
                @dragleave="handleDragLeave($event, file)"
                @drop.stop="handleDrop($event, file)"
                @click="handleFileClick(file)"
                @contextmenu.prevent="showContextMenu(file, $event)"
                class="file-item group cursor-pointer p-5 rounded-3xl bg-[color:var(--ui-surface-strong)] border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-hover)] transition-all duration-300 relative overflow-hidden"
                :class="{ 
                    'ring-2 ring-indigo-500 bg-indigo-500/5': props.selectedFiles.includes(file.path),
                    'ring-2 ring-indigo-500 bg-indigo-500/10 scale-105': draggingOverPath === file.path && file.is_directory 
                }"
            >
                <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                <!-- Selection Indicator -->
                 <div v-if="props.selectedFiles.includes(file.path)" class="absolute top-3 right-3 w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center shadow-lg transform scale-100 transition-transform">
                     <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                 </div>

                 <!-- Star Indicator -->
                 <button 
                    @click.stop="emit('file-star-toggle', file)"
                    class="absolute top-3 left-3 p-1.5 rounded-xl transition-all duration-300 z-20 group/star"
                    :class="file.is_starred ? 'bg-amber-500/10 text-amber-500 opacity-100' : 'bg-black/5 text-gray-400 opacity-0 group-hover:opacity-100 hover:text-amber-500'"
                 >
                    <svg class="w-4 h-4" :fill="file.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                 </button>
                
                <div class="text-center relative z-10 flex flex-col h-full">
                    <!-- File Icon -->
                    <div class="mb-5 transform group-hover:scale-110 transition-transform duration-500 ease-out-expo flex-1 flex items-center justify-center">
                        <FileIcon :name="file.name" :is-directory="file.is_directory" size="lg" />
                    </div>
                    
                    <!-- File Name -->
                    <div class="mt-auto">
                        <div class="text-sm font-medium text-[color:var(--ui-fg)] truncate px-1 transition-colors tracking-tight" :title="file.name">
                            {{ file.name }}
                        </div>
                        
                        <!-- File Size -->
                        <div v-if="file.is_trash" class="text-[10px] font-black uppercase tracking-tighter bg-amber-500/10 text-amber-500 px-2 py-0.5 rounded-md border border-amber-500/10 mt-1 inline-block">
                            {{ file.days_remaining }}d left
                        </div>
                        <div v-else-if="!file.is_directory" class="text-xs text-[color:var(--ui-muted)] mt-1 font-mono opacity-70">
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
                        draggable="true"
                        @dragstart="handleDragStart($event, file)"
                        @dragover.prevent="handleDragOver($event, file)"
                        @dragleave="handleDragLeave($event, file)"
                        @drop.stop="handleDrop($event, file)"
                        @click="handleFileClick(file)"
                        @contextmenu.prevent="showContextMenu(file, $event)"
                        class="hover:bg-[color:var(--ui-hover)] cursor-pointer transition-colors group"
                        :class="{ 
                            'bg-indigo-500/10': props.selectedFiles.includes(file.path),
                            'bg-indigo-500/20': draggingOverPath === file.path && file.is_directory
                        }"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <button 
                                    @click.stop="emit('file-star-toggle', file)"
                                    class="mr-3 p-1.5 rounded-lg transition-all duration-300"
                                    :class="file.is_starred ? 'text-amber-500' : 'text-gray-400 opacity-0 group-hover:opacity-100 hover:text-amber-500'"
                                >
                                    <svg class="w-4 h-4" :fill="file.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </button>
                                <FileIcon :name="file.name" :is-directory="file.is_directory" size="sm" class="mr-3" />
                                <span class="text-sm font-medium text-[color:var(--ui-fg)]">{{ file.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[color:var(--ui-muted)]">
                            {{ file.is_directory ? '—' : file.size_formatted }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[color:var(--ui-muted)]">
                            <span v-if="file.is_trash" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                {{ file.days_remaining }} days left
                            </span>
                            <span v-else>{{ formatDate(file.modified) }}</span>
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
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group cursor-pointer"
            >
                <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 group-hover:bg-blue-500/20 transition-colors">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
                </div>
                Open
            </button>
            <button
                v-else
                @click="downloadFile(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group cursor-pointer"
            >
                 <div class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500/20 transition-colors">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                Download
            </button>
            <button
                @click="emit('file-star-toggle', contextMenu.file); contextMenu.show = false"
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group cursor-pointer"
            >
                 <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 group-hover:bg-amber-500/20 transition-colors">
                     <svg class="w-4 h-4" :fill="contextMenu.file.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
                {{ contextMenu.file.is_starred ? 'Unstar' : 'Star' }}
            </button>
            <button
                @click="renameFile(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group cursor-pointer"
            >
                 <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 group-hover:bg-amber-500/20 transition-colors">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                Rename
            </button>
            <button
                v-if="!contextMenu.file.is_trash"
                @click="shareFile(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] flex items-center gap-3 transition-colors group cursor-pointer"
            >
                 <div class="p-1.5 rounded-lg bg-green-500/10 text-green-400 group-hover:bg-green-500/20 transition-colors">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </div>
                Share
            </button>

            <!-- Trash Specific Actions -->
            <button
                v-if="contextMenu.file.is_trash"
                @click="emit('file-restore', contextMenu.file); contextMenu.show = false"
                class="w-full text-left px-4 py-3 text-sm text-green-400 hover:bg-green-500/10 flex items-center gap-3 transition-colors group cursor-pointer"
            >
                 <div class="p-1.5 rounded-lg bg-green-500/10 text-green-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </div>
                Restore
            </button>

            <div class="h-px bg-[color:var(--ui-border)] my-1 mx-4"></div>
            <button
                v-if="!contextMenu.file.is_trash"
                @click="deleteFile(contextMenu.file)"
                class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 flex items-center gap-3 transition-colors group cursor-pointer"
            >
                 <div class="p-1.5 rounded-lg bg-red-500/10 text-red-400 group-hover:bg-red-500/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                Move to Trash
            </button>
            <button
                v-else
                @click="emit('file-permanent-delete', contextMenu.file); contextMenu.show = false"
                class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 flex items-center gap-3 transition-colors group cursor-pointer"
            >
                 <div class="p-1.5 rounded-lg bg-red-500/10 text-red-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                Delete Permanently
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
import FileIcon from './FileIcon.vue';

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
    'file-share',
    'file-star-toggle',
    'file-move',
    'file-restore',
    'file-permanent-delete',
    'file-select',
    'file-click',
    'refresh'
]);

const contextMenu = reactive({
    show: false,
    x: 0,
    y: 0,
    file: null
});

const draggingOverPath = ref(null);

const handleDragStart = (event, file) => {
    event.dataTransfer.setData('application/json', JSON.stringify(file));
    event.dataTransfer.effectAllowed = 'move';
};

const handleDragOver = (event, file) => {
    if (file.is_directory) {
        draggingOverPath.value = file.path;
    } else {
        draggingOverPath.value = null;
    }
};

const handleDragLeave = (event, file) => {
    if (draggingOverPath.value === file.path) {
        draggingOverPath.value = null;
    }
};

const handleDrop = (event, targetFile) => {
    draggingOverPath.value = null;
    const sourceData = event.dataTransfer.getData('application/json');
    if (!sourceData) return;

    const sourceFile = JSON.parse(sourceData);
    if (sourceFile.path === targetFile.path) return;

    if (targetFile.is_directory) {
        emit('file-move', {
            source: sourceFile,
            targetPath: targetFile.path
        });
    }
};

const handleRootDrop = (event) => {
    // This allows moving things back to the current root if we're in a subfolder
    // But for simplicity, we usually move INTO folders shown in the list.
};

const handleFileClick = (file) => {
    emit('file-click', file);
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

const shareFile = (file) => {
    contextMenu.show = false;
    emit('file-share', file);
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