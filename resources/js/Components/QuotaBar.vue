<template>
    <div class="bg-[#151520]/50 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-white tracking-tight">Storage Usage</h3>
            <span class="text-xs font-mono text-gray-400 bg-white/5 px-2 py-1 rounded-md border border-white/5">
                {{ quota.used_formatted }} / {{ quota.total_formatted }}
            </span>
        </div>
        
        <div class="relative w-full bg-[#0a0a12] rounded-full h-3 overflow-hidden shadow-inner border border-white/5">
            <div 
                class="absolute top-0 left-0 h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden"
                :class="{
                    'bg-gradient-to-r from-green-500 to-emerald-400': quota.usage_percentage < 70,
                    'bg-gradient-to-r from-yellow-500 to-amber-400': quota.usage_percentage >= 70 && quota.usage_percentage < 90,
                    'bg-gradient-to-r from-red-500 to-rose-400': quota.usage_percentage >= 90
                }"
                :style="{ width: Math.min(quota.usage_percentage, 100) + '%' }"
            >
                <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
            </div>
        </div>
        
        <div class="flex justify-between text-xs text-gray-500 mt-3 font-medium">
            <span>{{ quota.usage_percentage }}% used</span>
            <div class="flex items-center space-x-2">
                <span v-if="quota.is_approaching_limit" class="text-amber-400 flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    Approaching limit
                </span>
                <span v-if="quota.has_exceeded" class="text-red-400 flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    Limit exceeded
                </span>
                <span v-if="!quota.is_approaching_limit && !quota.has_exceeded && quota.usage_percentage > 0" class="text-green-400 flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Healthy
                </span>
            </div>
        </div>
        
        <!-- Quota warning banner -->
        <div v-if="quota.has_exceeded" class="mt-5 p-4 bg-red-500/10 border border-red-500/20 rounded-xl relative overflow-hidden group">
             <div class="absolute inset-0 bg-red-500/5 group-hover:bg-red-500/10 transition-colors"></div>
            <div class="flex items-start relative z-10">
                <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-sm font-bold text-red-400">Storage quota exceeded</p>
                    <p class="text-xs text-red-300/80 mt-1 leading-relaxed">You cannot upload new files. Please free up space.</p>
                </div>
            </div>
        </div>
        
        <div v-else-if="quota.is_approaching_limit" class="mt-5 p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl relative overflow-hidden group">
             <div class="absolute inset-0 bg-amber-500/5 group-hover:bg-amber-500/10 transition-colors"></div>
            <div class="flex items-start relative z-10">
                <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-sm font-bold text-amber-400">Approaching limit</p>
                    <p class="text-xs text-amber-300/80 mt-1 leading-relaxed">You have {{ quota.available_formatted }} remaining.</p>
                </div>
            </div>
        </div>

        <!-- Additional quota details -->
        <div v-if="showDetails" class="mt-5 pt-4 border-t border-white/5">
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div class="bg-white/5 rounded-lg p-2 border border-white/5">
                    <span class="text-gray-500 block mb-1">Available</span>
                    <span class="font-medium text-white">{{ quota.available_formatted }}</span>
                </div>
                <div class="bg-white/5 rounded-lg p-2 border border-white/5">
                    <span class="text-gray-500 block mb-1">Total Files</span>
                    <span class="font-medium text-white">{{ fileCount || 'N/A' }}</span>
                </div>
                <div class="bg-white/5 rounded-lg p-2 border border-white/5">
                    <span class="text-gray-500 block mb-1">Type</span>
                    <span class="font-medium text-white">{{ quota.is_unlimited ? 'Unlimited' : 'Limited' }}</span>
                </div>
            </div>
        </div>
        
        <!-- Action buttons -->
        <div class="flex items-center justify-between mt-4">
            <button
                @click="showDetails = !showDetails"
                class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center transition-colors font-medium px-2 py-1 rounded-lg hover:bg-indigo-500/10"
            >
                <span>{{ showDetails ? 'Hide' : 'Show' }} details</span>
                <svg 
                    class="w-3 h-3 ml-1 transition-transform duration-300"
                    :class="{ 'rotate-180': showDetails }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <button
                @click="$emit('recalculate')"
                :disabled="recalculating"
                class="text-xs text-gray-400 hover:text-white flex items-center disabled:opacity-50 transition-colors font-medium px-2 py-1 rounded-lg hover:bg-white/5"
                title="Recalculate storage usage"
            >
                <svg 
                    class="w-3 h-3 mr-1.5"
                    :class="{ 'animate-spin text-indigo-500': recalculating }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span>{{ recalculating ? 'Updating...' : 'Refresh' }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    quota: {
        type: Object,
        required: true,
        default: () => ({
            used_bytes: 0,
            total_bytes: 0,
            available_bytes: 0,
            usage_percentage: 0,
            is_approaching_limit: false,
            has_exceeded: false,
            is_unlimited: false,
            used_formatted: '0 B',
            total_formatted: '0 B',
            available_formatted: '0 B'
        })
    },
    fileCount: {
        type: Number,
        default: null
    },
    recalculating: {
        type: Boolean,
        default: false
    }
});

defineEmits(['recalculate']);

const showDetails = ref(false);
</script>

<style scoped>
.rotate-180 {
    transform: rotate(180deg);
}
</style>