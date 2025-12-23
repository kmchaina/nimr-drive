<template>
    <div v-if="show" class="fixed inset-0 bg-[color:var(--ui-overlay)]/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-[color:var(--ui-surface-strong)] rounded-3xl shadow-2xl w-full max-w-lg p-8 border border-[color:var(--ui-border)] transform transition-all scale-100">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-[color:var(--ui-fg)] tracking-tight font-heading">Share item</h3>
                    <p class="text-sm text-[color:var(--ui-muted)] mt-1">Manage who can access "<span class="text-[color:var(--ui-fg)] font-medium">{{ item?.name }}</span>"</p>
                </div>
                <button @click="$emit('close')" class="p-2 rounded-xl hover:bg-[color:var(--ui-hover)] transition-colors text-[color:var(--ui-muted)]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- User Search -->
            <div class="mb-8 relative">
                <label class="block text-xs font-bold text-[color:var(--ui-muted)] uppercase tracking-wider mb-2">Add people</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-[color:var(--ui-muted-2)] group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <input
                        v-model="userSearchQuery"
                        @input="debounceSearch"
                        type="text"
                        placeholder="Name, email or username..."
                        class="w-full pl-11 pr-4 py-3.5 bg-white/70 dark:bg-black/20 border border-[color:var(--ui-border)] rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all text-[color:var(--ui-fg)] placeholder-[color:var(--ui-muted-2)]"
                    />
                </div>

                <!-- Search Results -->
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform opacity-0 scale-95 -translate-y-2"
                    enter-to-class="transform opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="transform opacity-100 scale-100 translate-y-0"
                    leave-to-class="transform opacity-0 scale-95 -translate-y-2"
                >
                    <div v-if="searchResults.length > 0" class="absolute left-0 right-0 mt-2 bg-[color:var(--ui-surface-strong)] rounded-2xl shadow-2xl border border-[color:var(--ui-border)] py-2 z-50 backdrop-blur-xl ring-1 ring-black/5 overflow-hidden">
                        <button
                            v-for="user in searchResults"
                            :key="user.id"
                            @click="selectUser(user)"
                            class="w-full text-left px-4 py-3 hover:bg-[color:var(--ui-hover)] flex items-center transition-all group"
                        >
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mr-3 font-bold group-hover:scale-110 transition-all">
                                {{ getInitials(user) }}
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-bold text-[color:var(--ui-fg)]">{{ user.display_name || user.name }}</div>
                                <div class="text-xs text-[color:var(--ui-muted)]">{{ user.ad_username || user.email }}</div>
                            </div>
                        </button>
                    </div>
                </transition>
            </div>

            <!-- Selected User and Permission -->
            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="selectedUser" class="mb-8 p-5 bg-indigo-500/5 rounded-2xl border border-indigo-500/10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500 text-white flex items-center justify-center font-bold shadow-lg shadow-indigo-500/20">
                                {{ getInitials(selectedUser) }}
                            </div>
                            <div class="ml-4">
                                <div class="text-base font-bold text-[color:var(--ui-fg)]">{{ selectedUser.display_name || selectedUser.name }}</div>
                                <div class="text-xs text-[color:var(--ui-muted)]">{{ selectedUser.email }}</div>
                            </div>
                        </div>
                        <button @click="selectedUser = null" class="p-2 text-[color:var(--ui-muted)] hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <select
                            v-model="accessLevel"
                            class="flex-1 px-4 py-2.5 bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] rounded-xl text-sm font-medium focus:ring-2 focus:ring-indigo-500/30 outline-none"
                        >
                            <option value="view">Viewer</option>
                            <option value="edit">Editor</option>
                        </select>
                        <button
                            @click="shareItem"
                            :disabled="isSharing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/20 transition-all disabled:opacity-50"
                        >
                            {{ isSharing ? 'Sharing...' : 'Share' }}
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Existing Shares -->
            <div>
                <label class="block text-xs font-bold text-[color:var(--ui-muted)] uppercase tracking-wider mb-4">Who has access</label>
                <div class="space-y-4 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                    <!-- Owner (always first) -->
                    <div class="flex items-center justify-between group">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] flex items-center justify-center text-[color:var(--ui-muted)] font-bold">
                                {{ getInitials(currentUser) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-bold text-[color:var(--ui-fg)]">{{ currentUser.display_name || currentUser.name }} (You)</div>
                                <div class="text-xs text-[color:var(--ui-muted)]">Owner</div>
                            </div>
                        </div>
                    </div>

                    <!-- Shared Users -->
                    <div v-for="share in existingShares" :key="share.id" class="flex items-center justify-between group">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold">
                                {{ getInitials(share.shared_with) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-bold text-[color:var(--ui-fg)]">{{ share.shared_with?.display_name || share.shared_with?.name }}</div>
                                <div class="text-xs text-[color:var(--ui-muted)]">{{ share.shared_with?.email }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <select
                                @change="updateShare(share, $event.target.value)"
                                class="bg-transparent text-xs font-semibold text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] border-none focus:ring-0 cursor-pointer"
                            >
                                <option value="view" :selected="share.access_level === 'view'">Viewer</option>
                                <option value="edit" :selected="share.access_level === 'edit'">Editor</option>
                                <option value="remove" class="text-red-500">Remove</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="existingShares.length === 0" class="py-6 text-center rounded-2xl border-2 border-dashed border-[color:var(--ui-border)]">
                        <p class="text-sm text-[color:var(--ui-muted)]">Not shared with anyone yet</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-[color:var(--ui-border)] flex justify-between items-center">
                <button
                    @click="copyLink"
                    class="flex items-center text-sm font-bold text-indigo-500 hover:text-indigo-400 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    Copy share path
                </button>
                <button
                    @click="$emit('close')"
                    class="px-6 py-2.5 bg-[color:var(--ui-hover)] text-[color:var(--ui-fg)] text-sm font-bold rounded-xl transition-all"
                >
                    Done
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
const axios = window.axios;

const props = defineProps({
    show: Boolean,
    item: Object,
    currentUser: Object
});

const emit = defineEmits(['close', 'share-updated']);

const userSearchQuery = ref('');
const searchResults = ref([]);
const selectedUser = ref(null);
const accessLevel = ref('view');
const isSharing = ref(false);
const existingShares = ref([]);
const searchTimeout = ref(null);

watch(() => props.item, (newItem) => {
    if (newItem) {
        loadExistingShares();
    }
}, { immediate: true });

const loadExistingShares = async () => {
    if (!props.item) return;
    try {
        // We'll need a way to get shares for a specific path
        // For now, let's filter the owned shares
        const response = await axios.get('/api/shares/owned');
        if (response.data.success) {
            existingShares.value = response.data.shares.filter(s => s.path === props.item.path);
        }
    } catch (error) {
        console.error('Error loading shares:', error);
    }
};

const debounceSearch = () => {
    if (searchTimeout.value) clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(searchUsers, 300);
};

const searchUsers = async () => {
    if (userSearchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    try {
        const response = await axios.get('/api/users/search', {
            params: { q: userSearchQuery.value }
        });
        if (response.data.success) {
            searchResults.value = response.data.users;
        }
    } catch (error) {
        console.error('Error searching users:', error);
    }
};

const selectUser = (user) => {
    selectedUser.value = user;
    userSearchQuery.value = '';
    searchResults.value = [];
};

const getInitials = (user) => {
    if (!user) return '?';
    const name = user.display_name || user.name || '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const shareItem = async () => {
    if (!selectedUser.value || !props.item) return;
    
    isSharing.value = true;
    try {
        const response = await axios.post('/api/shares', {
            path: props.item.path,
            shared_with_id: selectedUser.value.id,
            access_level: accessLevel.value
        });

        if (response.data.success) {
            selectedUser.value = null;
            loadExistingShares();
            emit('share-updated');
        }
    } catch (error) {
        console.error('Error sharing item:', error);
    } finally {
        isSharing.value = false;
    }
};

const updateShare = async (share, newLevel) => {
    if (newLevel === 'remove') {
        removeShare(share);
        return;
    }

    try {
        const response = await axios.put(`/api/shares/${share.id}`, {
            access_level: newLevel
        });
        if (response.data.success) {
            loadExistingShares();
            emit('share-updated');
        }
    } catch (error) {
        console.error('Error updating share:', error);
    }
};

const removeShare = async (share) => {
    try {
        const response = await axios.delete(`/api/shares/${share.id}`);
        if (response.data.success) {
            loadExistingShares();
            emit('share-updated');
        }
    } catch (error) {
        console.error('Error removing share:', error);
    }
};

const copyLink = () => {
    if (!props.item) return;
    const fullPath = `users/${props.currentUser.ad_username || props.currentUser.id}/files/${props.item.path}`;
    navigator.clipboard.writeText(fullPath);
    // Could show a toast here
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>
