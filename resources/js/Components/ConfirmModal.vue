<template>
    <teleport to="body">
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click="handleBackdropClick"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div
                            v-if="isOpen"
                            class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] shadow-2xl transition-all"
                            @click.stop
                        >
                            <!-- Icon -->
                            <div class="flex items-center justify-center pt-6 pb-4">
                                <div
                                    :class="[
                                        'flex items-center justify-center w-12 h-12 rounded-full',
                                        type === 'danger' ? 'bg-red-500/10' :
                                        type === 'warning' ? 'bg-amber-500/10' :
                                        type === 'success' ? 'bg-emerald-500/10' :
                                        'bg-blue-500/10'
                                    ]"
                                >
                                    <!-- Danger Icon -->
                                    <svg v-if="type === 'danger'" class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <!-- Warning Icon -->
                                    <svg v-else-if="type === 'warning'" class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <!-- Success Icon -->
                                    <svg v-else-if="type === 'success'" class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <!-- Info Icon -->
                                    <svg v-else class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="px-6 pb-6 text-center">
                                <h3 class="text-lg font-semibold text-[color:var(--ui-fg)] mb-2">
                                    {{ title }}
                                </h3>
                                <p class="text-sm text-[color:var(--ui-muted)] leading-relaxed">
                                    {{ message }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 px-6 pb-6">
                                <button
                                    v-if="showCancel"
                                    @click="handleCancel"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-[color:var(--ui-fg)] bg-[color:var(--ui-surface-2)] hover:bg-[color:var(--ui-surface-strong)] border border-[color:var(--ui-border)] rounded-lg transition-colors"
                                >
                                    {{ cancelText }}
                                </button>
                                <button
                                    @click="handleConfirm"
                                    :class="[
                                        'flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all shadow-sm',
                                        type === 'danger' ? 'bg-red-500 hover:bg-red-600 text-white' :
                                        type === 'warning' ? 'bg-amber-500 hover:bg-amber-600 text-white' :
                                        type === 'success' ? 'bg-emerald-500 hover:bg-emerald-600 text-white' :
                                        'bg-blue-500 hover:bg-blue-600 text-white'
                                    ]"
                                >
                                    {{ confirmText }}
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'info', // 'info', 'warning', 'danger', 'success'
    },
    title: {
        type: String,
        default: 'Confirm',
    },
    message: {
        type: String,
        default: 'Are you sure?',
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    showCancel: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['confirm', 'cancel']);

const isOpen = ref(false);

const open = () => {
    isOpen.value = true;
};

const close = () => {
    isOpen.value = false;
};

const handleConfirm = () => {
    emit('confirm');
    close();
};

const handleCancel = () => {
    emit('cancel');
    close();
};

const handleBackdropClick = () => {
    if (props.showCancel) {
        handleCancel();
    }
};

defineExpose({
    open,
    close,
});
</script>

