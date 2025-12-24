<template>
    <div class="file-upload">
        <!-- Hidden Inputs for programmatic triggering -->
        <input ref="fileInput" type="file" multiple @change="handleFileSelect" class="hidden" />
        <input ref="folderInput" type="file" webkitdirectory directory multiple @change="handleFolderSelect" class="hidden" />

        <!-- Upload Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-[color:var(--ui-overlay)]/60 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center transition-all duration-300">
            <div class="relative mx-auto p-8 border border-[color:var(--ui-border)] w-full max-w-2xl shadow-2xl rounded-3xl bg-[color:var(--ui-surface-strong)] backdrop-blur-xl transform transition-all scale-100">
                <div class="mt-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold tracking-tight font-heading text-[color:var(--ui-fg)]">Upload files</h3>
                            <p class="text-sm text-[color:var(--ui-muted)] mt-1">Add documents, images, and more to your workspace.</p>
                        </div>
                        <button
                            @click="closeModal"
                            :disabled="isUploading"
                            class="p-2.5 rounded-xl text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] transition-colors border border-[color:var(--ui-border)] disabled:opacity-50"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Overall Progress Summary (when uploading) -->
                    <div v-if="uploadQueue.length > 0" class="mb-6 p-4 rounded-2xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-[color:var(--ui-fg)]">
                                {{ isUploading ? 'Uploading...' : (allUploadsComplete ? 'Upload Complete' : 'Ready to upload') }}
                            </span>
                            <span class="text-xs text-[color:var(--ui-muted)]">
                                {{ completedUploads }}/{{ uploadQueue.length }} files • {{ formatBytes(totalUploadedSize) }}/{{ formatBytes(totalQueueSize) }}
                            </span>
                        </div>
                        <div class="w-full bg-black/10 dark:bg-white/10 rounded-full h-2 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="{
                                    'bg-gradient-to-r from-blue-500 to-indigo-500': isUploading,
                                    'bg-green-500': allUploadsComplete && errorCount === 0,
                                    'bg-yellow-500': allUploadsComplete && errorCount > 0
                                }"
                                :style="{ width: overallProgress + '%' }"
                            ></div>
                        </div>
                        <div v-if="errorCount > 0" class="mt-2 text-xs text-red-400">
                            ⚠️ {{ errorCount }} file(s) failed to upload
                        </div>
                    </div>

                    <!-- Drop Zone (hide when uploading many files) -->
                    <div
                        v-if="!isUploading || uploadQueue.length < 5"
                        @drop="handleDrop"
                        @dragover.prevent
                        @dragenter.prevent
                        @dragleave="handleDragLeave"
                        @dragenter="handleDragEnter"
                        class="border-2 border-dashed rounded-2xl p-8 text-center transition-all duration-300 group relative overflow-hidden"
                        :class="{
                            'border-indigo-500 bg-indigo-500/10': isDragging,
                            'border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] hover:bg-[color:var(--ui-hover)]': !isDragging,
                            'opacity-50 pointer-events-none': isUploading
                        }"
                    >
                        <div class="mb-4">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-indigo-500/10 to-blue-500/10 flex items-center justify-center border border-[color:var(--ui-border)]">
                                <svg class="h-8 w-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="text-lg font-semibold text-[color:var(--ui-fg)] mb-2">Drop files or folders here</p>
                        <p class="text-sm text-[color:var(--ui-muted)] mb-4">Max 2GB per file</p>
                        
                        <div v-if="uploadQueue.length === 0" class="flex justify-center gap-3">
                            <button
                                @click="$refs.fileInput.click()"
                                :disabled="isUploading"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium shadow-lg transition-all disabled:opacity-50"
                            >
                                Browse Files
                            </button>
                            <button
                                @click="$refs.folderInput.click()"
                                :disabled="isUploading"
                                class="px-5 py-2.5 rounded-xl bg-[color:var(--ui-surface)] hover:bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] font-medium border border-[color:var(--ui-border)] transition-all disabled:opacity-50"
                            >
                                Browse Folder
                            </button>
                        </div>
                    </div>

                    <!-- Upload Queue -->
                    <div v-if="uploadQueue.length > 0" class="mt-6">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-[color:var(--ui-fg)]">Files ({{ uploadQueue.length }})</h4>
                            <div class="flex gap-2">
                                <button
                                    v-if="errorCount > 0"
                                    @click="retryFailed"
                                    class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                                >
                                    Retry failed
                                </button>
                                <button
                                    @click="clearCompleted"
                                    :disabled="completedUploads === 0"
                                    class="text-xs text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] transition-colors disabled:opacity-50"
                                >
                                    Clear completed
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                            <div
                                v-for="upload in uploadQueue"
                                :key="upload.id"
                                class="flex items-center gap-3 p-3 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]"
                            >
                                <!-- Status Icon -->
                                <div class="flex-shrink-0">
                                    <svg v-if="upload.status === 'completed'" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <svg v-else-if="upload.status === 'error'" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <svg v-else-if="upload.status === 'uploading'" class="w-5 h-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>

                                <!-- File Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-[color:var(--ui-fg)] truncate" :title="upload.relativePath || upload.file.name">
                                            {{ upload.file.name }}
                                        </p>
                                        <span class="text-xs text-[color:var(--ui-muted)] ml-2 flex-shrink-0">
                                            {{ formatBytes(upload.file.size) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Progress bar for uploading files -->
                                    <div v-if="upload.status === 'uploading'" class="mt-1.5">
                                        <div class="w-full bg-black/10 dark:bg-white/10 rounded-full h-1 overflow-hidden">
                                            <div
                                                class="h-full rounded-full bg-blue-500 transition-all duration-300"
                                                :style="{ width: upload.progress + '%' }"
                                            ></div>
                                        </div>
                                        <p class="text-xs text-[color:var(--ui-muted)] mt-1">
                                            {{ Math.round(upload.progress) }}% • Chunk {{ upload.uploadedChunks }}/{{ upload.chunks }}
                                        </p>
                                    </div>
                                    
                                    <!-- Error message -->
                                    <p v-if="upload.error" class="text-xs text-red-400 mt-1 truncate" :title="upload.error">
                                        {{ upload.error }}
                                    </p>
                                    
                                    <!-- Folder path indicator -->
                                    <p v-if="upload.relativePath && upload.relativePath !== upload.file.name" class="text-xs text-[color:var(--ui-muted)] mt-0.5 truncate">
                                        📁 {{ upload.relativePath.split('/').slice(0, -1).join('/') }}
                                    </p>
                                </div>

                                <!-- Cancel button -->
                                <button
                                    v-if="upload.status === 'pending' || upload.status === 'uploading'"
                                    @click="cancelUpload(upload)"
                                    class="flex-shrink-0 p-1.5 rounded-lg text-[color:var(--ui-muted)] hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div v-if="uploadQueue.length > 0" class="mt-6 flex justify-end gap-3">
                        <button
                            v-if="!isUploading && !allUploadsComplete"
                            @click="closeModal"
                            class="px-5 py-2.5 text-sm text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] rounded-xl transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            v-if="allUploadsComplete"
                            @click="closeModal"
                            class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg transition-all"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
import { ref, computed, watch } from 'vue';

// Use the globally configured axios with CSRF token from bootstrap.js
const axios = window.axios;
import ConfirmModal from './ConfirmModal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    currentPath: { type: String, default: '' }
});

const emit = defineEmits(['close', 'upload-complete', 'show-error', 'started']);

// Constants
const MAX_FILE_SIZE = 2 * 1024 * 1024 * 1024; // 2GB
const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB chunks for better network handling
const MAX_CONCURRENT_UPLOADS = 3;

// Reactive data
const showModal = ref(false);
const isDragging = ref(false);
const dragCounter = ref(0);
const uploadQueue = ref([]);
const activeUploadCount = ref(0);
const fileInput = ref(null);
const folderInput = ref(null);
const isProgrammatic = ref(false);

// Modal state
const confirmModalRef = ref(null);
const confirmModalType = ref('info');
const confirmModalTitle = ref('Confirm');
const confirmModalMessage = ref('');
const confirmModalConfirmText = ref('Upload');
const confirmModalResolve = ref(null);

// Computed properties
const completedUploads = computed(() => uploadQueue.value.filter(u => u.status === 'completed').length);
const errorCount = computed(() => uploadQueue.value.filter(u => u.status === 'error').length);
const isUploading = computed(() => uploadQueue.value.some(u => u.status === 'uploading' || u.status === 'pending'));
const allUploadsComplete = computed(() => 
    uploadQueue.value.length > 0 && uploadQueue.value.every(u => u.status === 'completed' || u.status === 'error')
);

const totalQueueSize = computed(() => uploadQueue.value.reduce((sum, u) => sum + u.file.size, 0));
const totalUploadedSize = computed(() => 
    uploadQueue.value.reduce((sum, u) => {
        if (u.status === 'completed') return sum + u.file.size;
        if (u.status === 'uploading') return sum + (u.file.size * u.progress / 100);
        return sum;
    }, 0)
);
const overallProgress = computed(() => {
    if (totalQueueSize.value === 0) return 0;
    return Math.round((totalUploadedSize.value / totalQueueSize.value) * 100);
});

// Watch for prop changes
watch(() => props.show, (newValue) => { showModal.value = newValue; });

// Modal functions
const showConfirm = (options) => {
    return new Promise((resolve) => {
        confirmModalType.value = options.type || 'info';
        confirmModalTitle.value = options.title || 'Confirm';
        confirmModalMessage.value = options.message || 'Are you sure?';
        confirmModalConfirmText.value = options.confirmText || 'Confirm';
        confirmModalResolve.value = resolve;
        confirmModalRef.value.open();
    });
};

const handleModalConfirm = () => { if (confirmModalResolve.value) { confirmModalResolve.value(true); confirmModalResolve.value = null; } };
const handleModalCancel = () => { if (confirmModalResolve.value) { confirmModalResolve.value(false); confirmModalResolve.value = null; } };

// Drag and drop handlers
const handleDragEnter = (e) => { e.preventDefault(); dragCounter.value++; isDragging.value = true; };
const handleDragLeave = (e) => { e.preventDefault(); dragCounter.value--; if (dragCounter.value === 0) isDragging.value = false; };

const handleDrop = async (e) => {
    e.preventDefault();
    isDragging.value = false;
    dragCounter.value = 0;
    
    const items = e.dataTransfer.items;
    const files = [];
    
    if (items && items.length > 0) {
        for (let i = 0; i < items.length; i++) {
            const item = items[i].webkitGetAsEntry();
            if (item) await traverseFileTree(item, '', files);
        }
        addFilesToQueue(files, true);
    } else {
        addFilesToQueue(Array.from(e.dataTransfer.files));
    }
};

const traverseFileTree = async (item, path, files) => {
    return new Promise((resolve) => {
        if (item.isFile) {
            item.file((file) => {
                file.webkitRelativePath = path + file.name;
                files.push(file);
                resolve();
            });
        } else if (item.isDirectory) {
            const dirReader = item.createReader();
            const readAllEntries = async (allEntries = []) => {
                dirReader.readEntries(async (entries) => {
                    if (entries.length === 0) {
                        for (const entry of allEntries) {
                            await traverseFileTree(entry, path + item.name + '/', files);
                        }
                        resolve();
                    } else {
                        readAllEntries([...allEntries, ...entries]);
                    }
                });
            };
            readAllEntries();
        }
    });
};

const handleFileSelect = (e) => { addFilesToQueue(Array.from(e.target.files)); e.target.value = ''; };
const handleFolderSelect = (e) => { addFilesToQueue(Array.from(e.target.files), true); e.target.value = ''; };

const addFilesToQueue = async (files, isFolder = false) => {
    // Only show confirmation for folder uploads if not triggered programmatically from the "New" menu
    if (isFolder && files.length > 0 && !isProgrammatic.value) {
        const folderName = files[0].webkitRelativePath?.split('/')[0] || 'this folder';
        const totalSize = files.reduce((sum, file) => sum + file.size, 0);
        
        const confirmed = await showConfirm({
            type: 'info',
            title: 'Upload Folder',
            message: `Upload "${folderName}" with ${files.length} file(s) (${formatBytes(totalSize)})?`,
            confirmText: 'Start Upload'
        });
        
        if (!confirmed) {
            isProgrammatic.value = false;
            return;
        }
    }
    
    isProgrammatic.value = false;
    
    if (files.length === 0) return;
    
    // Emit started event so Dashboard can show the modal
    emit('started');
    
    const skipped = [];
    
    files.forEach(file => {
        if (file.size > MAX_FILE_SIZE) {
            skipped.push(`${file.name} (too large, max ${formatBytes(MAX_FILE_SIZE)})`);
            return;
        }
        
        if (file.size === 0) {
            skipped.push(`${file.name} (empty file)`);
            return;
        }

        uploadQueue.value.push({
            id: generateUploadId(),
            file: file,
            status: 'pending',
            progress: 0,
            error: null,
            chunks: Math.ceil(file.size / CHUNK_SIZE),
            uploadedChunks: 0,
            relativePath: isFolder ? file.webkitRelativePath || file.name : file.name
        });
    });
    
    if (skipped.length > 0) {
        emit('show-error', `Skipped ${skipped.length} file(s): ${skipped.slice(0, 3).join(', ')}${skipped.length > 3 ? '...' : ''}`);
    }
    
    processQueue();
};

const processQueue = () => {
    const pending = uploadQueue.value.filter(u => u.status === 'pending');
    const available = MAX_CONCURRENT_UPLOADS - activeUploadCount.value;
    
    for (let i = 0; i < Math.min(available, pending.length); i++) {
        uploadFile(pending[i]);
    }
};

const uploadFile = async (upload) => {
    upload.status = 'uploading';
    upload.progress = 0;
    activeUploadCount.value++;
    
    try {
        const chunks = Math.ceil(upload.file.size / CHUNK_SIZE);
        upload.chunks = chunks;

        for (let chunk = 0; chunk < chunks; chunk++) {
            if (upload.status === 'cancelled') {
                activeUploadCount.value--;
                processQueue();
                return;
            }

            const start = chunk * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, upload.file.size);
            const chunkBlob = upload.file.slice(start, end);

            const formData = new FormData();
            formData.append('file', chunkBlob);
            formData.append('chunk', chunk);
            formData.append('chunks', chunks);
            formData.append('name', upload.file.name);
            
            let targetPath = props.currentPath;
            if (upload.relativePath && upload.relativePath !== upload.file.name) {
                const pathParts = upload.relativePath.split('/');
                pathParts.pop();
                const folderPath = pathParts.join('/');
                targetPath = props.currentPath ? `${props.currentPath}/${folderPath}` : folderPath;
            }
            
            formData.append('path', targetPath);
            formData.append('size', upload.file.size);
            formData.append('relative_path', upload.relativePath || upload.file.name);

            const response = await axios.post('/api/upload/chunk', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                timeout: 120000, // 2 minute timeout per chunk
            });

            if (response.data.success) {
                upload.progress = response.data.progress || ((chunk + 1) / chunks * 100);
                upload.uploadedChunks = chunk + 1;

                if (response.data.completed) {
                    upload.status = 'completed';
                    upload.progress = 100;
                    break;
                }
            } else {
                throw new Error(response.data.error || 'Upload failed');
            }
        }
    } catch (error) {
        upload.status = 'error';
        upload.error = error.response?.data?.error || error.message || 'Upload failed';
        
        if (error.code === 'ECONNABORTED') {
            upload.error = 'Upload timed out - try a smaller file or check your connection';
        } else if (error.response?.data?.quota_exceeded) {
            upload.error = 'Storage quota exceeded';
        } else if (error.response?.status === 413) {
            upload.error = 'File too large for server';
        } else if (error.response?.status === 419) {
            upload.error = 'Session expired - please refresh the page';
        }
    } finally {
        activeUploadCount.value--;
        processQueue();
        checkAllUploadsComplete();
    }
};

const retryFailed = () => {
    uploadQueue.value.filter(u => u.status === 'error').forEach(u => {
        u.status = 'pending';
        u.progress = 0;
        u.error = null;
        u.uploadedChunks = 0;
    });
    processQueue();
};

const cancelUpload = (upload) => {
    upload.status = 'cancelled';
    const index = uploadQueue.value.findIndex(u => u.id === upload.id);
    if (index > -1) uploadQueue.value.splice(index, 1);
};

const clearCompleted = () => {
    uploadQueue.value = uploadQueue.value.filter(u => u.status !== 'completed' && u.status !== 'error');
};

const closeModal = () => {
    showModal.value = false;
    uploadQueue.value = [];
    activeUploadCount.value = 0;
    emit('close');
};

const generateUploadId = () => Date.now().toString(36) + Math.random().toString(36).substr(2);

const triggerFolderSelect = () => {
    isProgrammatic.value = true;
    if (folderInput.value) {
        folderInput.value.click();
    }
};

const triggerFileSelect = () => {
    isProgrammatic.value = true;
    if (fileInput.value) {
        fileInput.value.click();
    }
};

defineExpose({
    triggerFolderSelect,
    triggerFileSelect
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const checkAllUploadsComplete = () => {
    if (allUploadsComplete.value && uploadQueue.value.length > 0) {
        const successCount = completedUploads.value;
        if (successCount > 0) {
            emit('upload-complete', { count: successCount });
        }
    }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>
