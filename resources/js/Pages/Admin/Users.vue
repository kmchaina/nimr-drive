<template>
    <div class="min-h-screen bg-[color:var(--ui-bg)] text-[color:var(--ui-fg)]">
        <!-- Header -->
        <header class="border-b border-[color:var(--ui-border)] bg-[color:var(--ui-surface)]">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/dashboard" class="text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] transition-colors">
                        ← Back to Dashboard
                    </a>
                    <h1 class="text-xl font-bold">Admin Panel</h1>
                </div>
                <ThemeToggle />
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-6 py-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                    <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ stats.total_users }}</div>
                    <div class="text-sm text-[color:var(--ui-muted)]">Total Users</div>
                </div>
                <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                    <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ stats.total_storage_used }}</div>
                    <div class="text-sm text-[color:var(--ui-muted)]">Storage Used</div>
                </div>
                <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                    <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ stats.total_storage_allocated }}</div>
                    <div class="text-sm text-[color:var(--ui-muted)]">Total Allocated</div>
                </div>
                <div class="p-4 rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)]">
                    <div class="text-2xl font-bold text-[color:var(--ui-fg)]">{{ stats.admin_count }}</div>
                    <div class="text-sm text-[color:var(--ui-muted)]">Admins</div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div v-if="selectedUsers.length > 0" class="mb-4 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-between">
                <span class="text-sm">{{ selectedUsers.length }} user(s) selected</span>
                <div class="flex items-center gap-3">
                    <input
                        v-model.number="bulkQuotaGb"
                        type="number"
                        min="0.1"
                        step="0.5"
                        class="w-24 px-3 py-1.5 rounded-lg bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] text-sm"
                        placeholder="GB"
                    />
                    <button
                        @click="applyBulkQuota"
                        class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors"
                    >
                        Set Quota
                    </button>
                    <button
                        @click="selectedUsers = []"
                        class="px-4 py-1.5 rounded-lg bg-[color:var(--ui-surface)] hover:bg-[color:var(--ui-hover)] text-sm transition-colors"
                    >
                        Clear Selection
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-xl bg-[color:var(--ui-surface)] border border-[color:var(--ui-border)] overflow-hidden">
                <table class="w-full">
                    <thead class="bg-[color:var(--ui-hover)]">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input
                                    type="checkbox"
                                    :checked="selectedUsers.length === localUsers.length"
                                    @change="toggleSelectAll"
                                    class="rounded"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Storage</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Quota</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Last Login</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Role</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--ui-border)]">
                        <tr v-for="user in localUsers" :key="user.id" class="hover:bg-[color:var(--ui-hover)] transition-colors">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedUsers.includes(user.id)"
                                    @change="toggleUserSelection(user.id)"
                                    class="rounded"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-[color:var(--ui-fg)]">{{ user.display_name || user.name }}</div>
                                <div class="text-xs text-[color:var(--ui-muted)]">{{ user.ad_username || user.email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 rounded-full bg-black/10 dark:bg-white/10 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="{
                                                'bg-green-500': user.usage_percentage < 70,
                                                'bg-yellow-500': user.usage_percentage >= 70 && user.usage_percentage < 90,
                                                'bg-red-500': user.usage_percentage >= 90
                                            }"
                                            :style="{ width: Math.min(user.usage_percentage, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-[color:var(--ui-muted)]">{{ user.used_formatted }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model.number="user.editQuotaGb"
                                        type="number"
                                        min="0.1"
                                        step="0.5"
                                        class="w-20 px-2 py-1 rounded-lg bg-[color:var(--ui-bg)] border border-[color:var(--ui-border)] text-sm"
                                        @focus="user.editQuotaGb = bytesToGb(user.quota_bytes)"
                                    />
                                    <span class="text-sm text-[color:var(--ui-muted)]">GB</span>
                                    <button
                                        v-if="user.editQuotaGb !== bytesToGb(user.quota_bytes)"
                                        @click="updateQuota(user)"
                                        :disabled="updatingUser === user.id"
                                        class="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium transition-colors disabled:opacity-50"
                                    >
                                        {{ updatingUser === user.id ? '...' : 'Save' }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-[color:var(--ui-muted)]">
                                {{ user.last_login || 'Never' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="user.is_admin ? 'bg-indigo-500/20 text-indigo-400' : 'bg-gray-500/20 text-gray-400'"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ user.is_admin ? 'Admin' : 'User' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="recalculateUsage(user)"
                                        :disabled="recalculatingUser === user.id"
                                        class="p-1.5 rounded-lg hover:bg-[color:var(--ui-hover)] text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] transition-colors"
                                        title="Recalculate storage"
                                    >
                                        <svg class="w-4 h-4" :class="{ 'animate-spin': recalculatingUser === user.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                    <button
                                        @click="toggleAdmin(user)"
                                        :disabled="togglingAdmin === user.id"
                                        class="p-1.5 rounded-lg hover:bg-[color:var(--ui-hover)] text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] transition-colors"
                                        :title="user.is_admin ? 'Remove admin' : 'Make admin'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Toast -->
            <div v-if="toast.show" class="fixed bottom-6 right-6 px-4 py-3 rounded-xl shadow-lg border"
                :class="{
                    'bg-green-500/10 border-green-500/20 text-green-400': toast.type === 'success',
                    'bg-red-500/10 border-red-500/20 text-red-400': toast.type === 'error'
                }"
            >
                {{ toast.message }}
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

// Use the globally configured axios with CSRF token from bootstrap.js
const axios = window.axios;
import ThemeToggle from '../../Components/ThemeToggle.vue';

const props = defineProps({
    users: Array,
    stats: Object,
});

const localUsers = ref(props.users.map(u => ({ ...u, editQuotaGb: bytesToGb(u.quota_bytes) })));
const selectedUsers = ref([]);
const bulkQuotaGb = ref(5);
const updatingUser = ref(null);
const recalculatingUser = ref(null);
const togglingAdmin = ref(null);
const toast = ref({ show: false, message: '', type: 'success' });

function bytesToGb(bytes) {
    return Math.round((bytes / (1024 * 1024 * 1024)) * 10) / 10;
}

function showToast(message, type = 'success') {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 3000);
}

function toggleSelectAll(e) {
    if (e.target.checked) {
        selectedUsers.value = localUsers.value.map(u => u.id);
    } else {
        selectedUsers.value = [];
    }
}

function toggleUserSelection(userId) {
    const index = selectedUsers.value.indexOf(userId);
    if (index > -1) {
        selectedUsers.value.splice(index, 1);
    } else {
        selectedUsers.value.push(userId);
    }
}

async function updateQuota(user) {
    updatingUser.value = user.id;
    try {
        const response = await axios.put(`/admin/users/${user.id}/quota`, {
            quota_gb: user.editQuotaGb
        });
        if (response.data.success) {
            user.quota_bytes = response.data.user.quota_bytes;
            user.quota_formatted = response.data.user.quota_formatted;
            user.usage_percentage = response.data.user.usage_percentage;
            showToast(response.data.message);
        }
    } catch (error) {
        showToast(error.response?.data?.error || 'Failed to update quota', 'error');
    } finally {
        updatingUser.value = null;
    }
}

async function recalculateUsage(user) {
    recalculatingUser.value = user.id;
    try {
        const response = await axios.post(`/admin/users/${user.id}/recalculate`);
        if (response.data.success) {
            user.used_bytes = response.data.used_bytes;
            user.used_formatted = response.data.used_formatted;
            user.usage_percentage = response.data.usage_percentage;
            showToast(response.data.message);
        }
    } catch (error) {
        showToast(error.response?.data?.error || 'Failed to recalculate', 'error');
    } finally {
        recalculatingUser.value = null;
    }
}

async function toggleAdmin(user) {
    togglingAdmin.value = user.id;
    try {
        const response = await axios.post(`/admin/users/${user.id}/toggle-admin`);
        if (response.data.success) {
            user.is_admin = response.data.is_admin;
            showToast(response.data.message);
        }
    } catch (error) {
        showToast(error.response?.data?.error || 'Failed to update admin status', 'error');
    } finally {
        togglingAdmin.value = null;
    }
}

async function applyBulkQuota() {
    if (selectedUsers.value.length === 0 || !bulkQuotaGb.value) return;
    
    try {
        const response = await axios.post('/admin/users/bulk-quota', {
            user_ids: selectedUsers.value,
            quota_gb: bulkQuotaGb.value
        });
        if (response.data.success) {
            const quotaBytes = bulkQuotaGb.value * 1024 * 1024 * 1024;
            localUsers.value.forEach(user => {
                if (selectedUsers.value.includes(user.id)) {
                    user.quota_bytes = quotaBytes;
                    user.editQuotaGb = bulkQuotaGb.value;
                    user.usage_percentage = user.quota_bytes > 0 
                        ? Math.round((user.used_bytes / user.quota_bytes) * 1000) / 10 
                        : 0;
                }
            });
            selectedUsers.value = [];
            showToast(response.data.message);
        }
    } catch (error) {
        showToast(error.response?.data?.error || 'Failed to update quotas', 'error');
    }
}
</script>
