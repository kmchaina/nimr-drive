<template>
    <div :class="[sizeClass, 'flex items-center justify-center rounded-xl overflow-hidden relative shadow-sm transition-all duration-300 group-hover:shadow-md']" :style="{ backgroundColor: iconColor + '15' }">
        <svg v-if="isDirectory" :class="[iconSizeClass]" :style="{ color: '#3B82F6' }" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z" />
        </svg>
        <template v-else>
            <div class="absolute inset-0 opacity-10 group-hover:opacity-20 transition-opacity" :style="{ backgroundColor: iconColor }"></div>
            <svg :class="[iconSizeClass]" :style="{ color: iconColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="iconPath"></path>
            </svg>
            <div v-if="extension" class="absolute bottom-1 right-1 px-1 rounded-[4px] bg-white/80 dark:bg-black/40 text-[8px] font-bold uppercase tracking-tighter" :style="{ color: iconColor }">
                {{ extension }}
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: String,
    isDirectory: Boolean,
    size: {
        type: String,
        default: 'md' // sm, md, lg
    }
});

const extension = computed(() => {
    if (props.isDirectory) return '';
    return props.name.split('.').pop().toLowerCase();
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'w-8 h-8';
        case 'lg': return 'w-16 h-16';
        default: return 'w-12 h-12';
    }
});

const iconSizeClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'w-5 h-5';
        case 'lg': return 'w-10 h-10';
        default: return 'w-7 h-7';
    }
});

const iconInfo = computed(() => {
    const ext = extension.value;
    
    if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(ext)) {
        return { color: '#EC4899', path: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' };
    }
    if (['pdf'].includes(ext)) {
        return { color: '#EF4444', path: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z M9 9h1M9 13h6M9 17h6' };
    }
    if (['doc', 'docx', 'odt'].includes(ext)) {
        return { color: '#3B82F6', path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' };
    }
    if (['xls', 'xlsx', 'ods', 'csv'].includes(ext)) {
        return { color: '#10B981', path: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' };
    }
    if (['ppt', 'pptx', 'odp'].includes(ext)) {
        return { color: '#F59E0B', path: 'M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm0 0V5h10V3M7 17h10' };
    }
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
        return { color: '#8B5CF6', path: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' };
    }
    if (['mp3', 'wav', 'ogg'].includes(ext)) {
        return { color: '#6366F1', path: 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3' };
    }
    if (['mp4', 'avi', 'mkv', 'mov'].includes(ext)) {
        return { color: '#F43F5E', path: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' };
    }
    if (['js', 'html', 'css', 'php', 'py', 'json', 'xml'].includes(ext)) {
        return { color: '#10B981', path: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' };
    }
    
    // Default file icon
    return { color: '#6B7280', path: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' };
});

const iconColor = computed(() => iconInfo.value.color);
const iconPath = computed(() => iconInfo.value.path);
</script>
