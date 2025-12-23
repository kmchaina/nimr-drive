<template>
    <div v-if="show" class="fixed inset-0 bg-black/90 backdrop-blur-sm z-[100] flex flex-col transition-all duration-300">
        <!-- Header -->
        <div class="h-16 flex items-center justify-between px-6 bg-gradient-to-b from-black/50 to-transparent">
            <div class="flex items-center gap-4">
                <FileIcon :name="file?.name" :is-directory="false" size="sm" />
                <div>
                    <h3 class="text-white font-bold text-sm truncate max-w-md">{{ file?.name }}</h3>
                    <p class="text-white/50 text-[10px] uppercase font-bold tracking-widest">{{ file?.size_formatted }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    @click="download"
                    class="p-2.5 rounded-full hover:bg-white/10 text-white transition-colors border border-white/10"
                    title="Download"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </button>
                <button 
                    @click="$emit('close')"
                    class="p-2.5 rounded-full bg-white text-black hover:bg-gray-200 transition-all hover:scale-105 active:scale-95"
                    title="Close"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 flex items-center justify-center p-10 overflow-hidden">
            <!-- Loading -->
            <div v-if="loading" class="flex flex-col items-center">
                <div class="w-12 h-12 border-4 border-white/10 border-t-indigo-500 rounded-full animate-spin"></div>
                <p class="text-white/50 text-xs mt-4 font-bold tracking-widest uppercase">Loading Preview...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="text-center">
                <div class="w-20 h-20 bg-red-500/10 rounded-3xl flex items-center justify-center mx-auto mb-6 text-red-500 border border-red-500/20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h4 class="text-white text-lg font-bold mb-2">Can't preview this file</h4>
                <p class="text-white/50 text-sm max-w-xs">{{ error }}</p>
                <button @click="download" class="mt-6 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-500 transition-all">Download instead</button>
            </div>

            <!-- Preview Components -->
            <template v-else>
                <!-- Image Preview -->
                <img 
                    v-if="type === 'image'" 
                    :src="contentUrl" 
                    class="max-w-full max-h-full object-contain rounded-lg shadow-2xl animate-fade-in"
                    alt="Preview" 
                />

                <!-- PDF Preview -->
                <iframe 
                    v-else-if="type === 'pdf'" 
                    :src="contentUrl" 
                    class="w-full h-full max-w-5xl bg-white rounded-xl shadow-2xl animate-fade-in"
                ></iframe>

                <!-- Text Preview -->
                <div 
                    v-else-if="type === 'text'" 
                    class="w-full h-full max-w-4xl bg-black/40 border border-white/10 rounded-2xl overflow-auto p-8 custom-scrollbar animate-fade-in"
                >
                    <pre class="text-gray-300 font-mono text-sm leading-relaxed whitespace-pre-wrap">{{ textContent }}</pre>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import FileIcon from './FileIcon.vue';
const axios = window.axios;

const props = defineProps({
    show: Boolean,
    file: Object
});

const emit = defineEmits(['close', 'download']);

const loading = ref(false);
const error = ref(null);
const type = ref(null);
const contentUrl = ref(null);
const textContent = ref('');

watch(() => props.show, (newShow) => {
    if (newShow) {
        loadPreview();
    } else {
        cleanup();
    }
});

const cleanup = () => {
    if (contentUrl.value && type.value !== 'text') {
        URL.revokeObjectURL(contentUrl.value);
    }
    contentUrl.value = null;
    textContent.value = '';
    error.value = null;
};

const loadPreview = async () => {
    if (!props.file) return;
    
    loading.value = true;
    error.value = null;
    
    const ext = props.file.name.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(ext)) {
        type.value = 'image';
    } else if (ext === 'pdf') {
        type.value = 'pdf';
    } else if (['txt', 'js', 'html', 'css', 'php', 'py', 'json', 'md', 'log'].includes(ext)) {
        type.value = 'text';
    } else {
        error.value = "We don't support previews for this file type yet.";
        loading.value = false;
        return;
    }

    try {
        const response = await axios.get('/api/files/download', {
            params: { path: props.file.path },
            responseType: type.value === 'text' ? 'text' : 'blob'
        });

        if (type.value === 'text') {
            textContent.value = response.data;
        } else {
            contentUrl.value = URL.createObjectURL(response.data);
        }
    } catch (e) {
        console.error('Preview error:', e);
        error.value = "Could not load file content.";
    } finally {
        loading.value = false;
    }
};

const download = () => {
    emit('download', props.file);
};
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}
</style>
