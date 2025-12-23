<template>
    <div class="relative">
        <!-- Bell Icon -->
        <button 
            @click="toggleDropdown"
            class="relative p-2.5 rounded-xl text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] transition-all duration-300"
            :class="{ 'text-indigo-500 bg-indigo-500/10': isOpen }"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            
            <!-- Unread Badge -->
            <span 
                v-if="unreadCount > 0" 
                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[10px] font-black rounded-full border-2 border-[color:var(--ui-bg)] flex items-center justify-center animate-in zoom-in duration-300"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95 -translate-y-2"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-2"
        >
            <div 
                v-if="isOpen" 
                class="absolute right-0 mt-3 w-80 bg-[color:var(--ui-surface-strong)] rounded-2xl shadow-2xl border border-[color:var(--ui-border)] overflow-hidden z-50 backdrop-blur-xl ring-1 ring-black/5"
            >
                <!-- Header -->
                <div class="px-5 py-4 border-b border-[color:var(--ui-border)] flex items-center justify-between bg-black/5 dark:bg-white/5">
                    <h3 class="text-sm font-bold text-[color:var(--ui-fg)] uppercase tracking-widest">Notifications</h3>
                    <button 
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-[10px] font-black uppercase text-indigo-500 hover:text-indigo-400 transition-colors"
                    >
                        Mark all read
                    </button>
                </div>

                <!-- List -->
                <div class="max-h-[400px] overflow-y-auto custom-scrollbar">
                    <div v-if="notifications.length === 0" class="p-8 text-center">
                        <div class="w-12 h-12 bg-gray-500/10 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H4a2 2 0 00-2 2v7m18 0v5a2 2 0 01-2 2H4a2 2 0 01-2-2v-5m18 0l-9 2.5L2 13"></path>
                            </svg>
                        </div>
                        <p class="text-xs text-[color:var(--ui-muted)] font-medium">No notifications yet</p>
                    </div>

                    <div 
                        v-for="notification in notifications" 
                        :key="notification.id"
                        class="px-5 py-4 border-b border-[color:var(--ui-border)] last:border-0 hover:bg-[color:var(--ui-hover)] transition-colors cursor-pointer relative group"
                        :class="{ 'bg-indigo-500/[0.03]': !notification.is_read }"
                        @click="handleNotificationClick(notification)"
                    >
                        <div class="flex gap-3">
                            <!-- Icon based on type -->
                            <div class="flex-shrink-0 mt-1">
                                <div v-if="notification.type === 'share'" class="p-1.5 rounded-lg bg-green-500/10 text-green-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                </div>
                                <div v-else class="p-1.5 rounded-lg bg-blue-500/10 text-blue-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-[color:var(--ui-fg)] mb-0.5 truncate">{{ notification.title }}</p>
                                <p class="text-[11px] text-[color:var(--ui-muted)] leading-relaxed">{{ notification.message }}</p>
                                <p class="text-[9px] text-[color:var(--ui-muted-2)] mt-1.5 font-bold uppercase tracking-tighter">{{ formatTime(notification.created_at) }}</p>
                            </div>

                            <!-- Unread indicator -->
                            <div v-if="!notification.is_read" class="w-1.5 h-1.5 bg-indigo-500 rounded-full mt-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="notifications.length > 0" class="px-5 py-3 bg-black/5 dark:bg-white/5 border-t border-[color:var(--ui-border)]">
                    <button 
                        @click="clearAll"
                        class="w-full text-center text-[10px] font-black uppercase text-[color:var(--ui-muted)] hover:text-red-500 transition-colors"
                    >
                        Clear all notifications
                    </button>
                </div>
            </div>
        </transition>

        <!-- Overlay to close -->
        <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40"></div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
const axios = window.axios;

const emit = defineEmits(['navigate']);

const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const pollInterval = ref(null);

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/api/notifications');
        if (response.data.success) {
            notifications.value = response.data.notifications;
            unreadCount.value = response.data.unread_count;
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
    }
};

const handleNotificationClick = async (notification) => {
    if (!notification.is_read) {
        markAsRead(notification);
    }
    
    if (notification.link) {
        emit('navigate', notification.link);
        isOpen.value = false;
    }
};

const markAsRead = async (notification) => {
    try {
        await axios.post(`/api/notifications/${notification.id}/read`);
        notification.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (error) {
        console.error('Error marking as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.is_read = true);
        unreadCount.value = 0;
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
};

const clearAll = async () => {
    try {
        await axios.delete('/api/notifications/clear');
        notifications.value = [];
        unreadCount.value = 0;
    } catch (error) {
        console.error('Error clearing notifications:', error);
    }
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
};

onMounted(() => {
    fetchNotifications();
    // Poll every 60 seconds
    pollInterval.value = setInterval(fetchNotifications, 60000);
});

onUnmounted(() => {
    if (pollInterval.value) clearInterval(pollInterval.value);
});
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
    border-radius: 2px;
}
</style>
