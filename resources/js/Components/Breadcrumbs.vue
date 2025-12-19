<template>
    <nav class="flex items-center space-x-1 text-sm mb-6 bg-[color:var(--ui-surface)] inline-flex px-2 py-1.5 rounded-2xl backdrop-blur-md border border-[color:var(--ui-border)]">
        <template v-for="(crumb, index) in breadcrumbs" :key="index">
            <button
                @click="navigateTo(crumb.path)"
                class="flex items-center px-3 py-1.5 rounded-xl transition-all duration-300"
                :class="{
                    'text-[color:var(--ui-fg)] font-semibold bg-[color:var(--ui-hover)] shadow-sm ring-1 ring-[color:var(--ui-border)] cursor-default': index === breadcrumbs.length - 1,
                    'text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] active:scale-95': index < breadcrumbs.length - 1
                }"
                :disabled="index === breadcrumbs.length - 1"
            >
                <!-- Home icon for root -->
                <svg
                    v-if="index === 0"
                    class="w-4 h-4 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    :class="index === breadcrumbs.length - 1 ? 'text-blue-500' : 'text-[color:var(--ui-muted-2)] group-hover:text-[color:var(--ui-fg)]'"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"
                    />
                </svg>
                
                <span class="tracking-wide">{{ crumb.name }}</span>
            </button>
            
            <!-- Separator -->
            <div v-if="index < breadcrumbs.length - 1" class="px-0.5">
                <svg class="w-3 h-3 text-[color:var(--ui-muted-2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </template>
    </nav>
</template>

<script setup>
defineProps({
    breadcrumbs: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['navigate']);

const navigateTo = (path) => {
    emit('navigate', path);
};
</script>