<template>
    <teleport to="body">
        <div class="fixed top-4 right-4 z-[100] space-y-2 font-sans max-w-[90vw] sm:max-w-xs">
        <transition-group name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[
                    'w-full backdrop-blur-xl border shadow-lg rounded-xl pointer-events-auto overflow-hidden',
                    'transform transition-all duration-300 ease-out',
                    toast.type === 'error' ? 'bg-red-500/10 border-red-500/30' : 
                    toast.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30' : 
                    toast.type === 'warning' ? 'bg-amber-500/10 border-amber-500/30' : 
                    'bg-[color:var(--ui-surface-strong)] border-[color:var(--ui-border)]'
                ]"
            >
                <div class="p-3 relative overflow-hidden">
                    <div
                        class="absolute left-0 top-0 bottom-0 w-0.5"
                        :class="toast.type === 'error' ? 'bg-red-500' : toast.type === 'success' ? 'bg-emerald-500' : toast.type === 'warning' ? 'bg-amber-500' : 'bg-indigo-500'"
                    ></div>
                    <div class="flex items-start gap-2.5 pl-2">
                        <div class="flex-shrink-0 mt-0.5">
                            <!-- Success Icon -->
                            <svg v-if="toast.type === 'success'" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <!-- Error Icon -->
                            <svg v-else-if="toast.type === 'error'" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <!-- Warning Icon -->
                            <svg v-else-if="toast.type === 'warning'" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <!-- Info Icon -->
                            <svg v-else class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-[color:var(--ui-fg)] leading-tight">
                                {{ toast.title }}
                            </p>
                            <p v-if="toast.message" class="mt-0.5 text-xs text-[color:var(--ui-muted)] leading-snug line-clamp-2">
                                {{ toast.message }}
                            </p>
                        </div>
                        <button
                            @click="removeToast(toast.id)"
                            class="flex-shrink-0 rounded-md text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] focus:outline-none transition-colors p-0.5 hover:bg-[color:var(--ui-hover)]"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </transition-group>
        </div>
    </teleport>
</template>

<script setup>
import { ref } from 'vue';

const toasts = ref([]);

const emit = defineEmits(['retry']);

const addToast = (toast) => {
    const id = Date.now() + Math.random();
    const newToast = {
        id,
        type: toast.type || 'info',
        title: toast.title || 'Notification',
        message: toast.message || '',
        canRetry: toast.canRetry || false,
        duration: toast.duration || 5000
    };
    
    toasts.value.push(newToast);
    
    if (newToast.duration > 0) {
        setTimeout(() => {
            removeToast(id);
        }, newToast.duration);
    }
    
    return id;
};

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index > -1) {
        toasts.value.splice(index, 1);
    }
};

const clearAll = () => {
    toasts.value = [];
};

// Expose methods to parent
defineExpose({
    addToast,
    removeToast,
    clearAll
});
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
