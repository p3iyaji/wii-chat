import { reactive, computed } from 'vue';

// Create reactive notification store
const notifications = reactive({
    counts: {},
    totalUnread: 0
});

// Load from localStorage
function loadFromStorage() {
    try {
        const saved = localStorage.getItem('chat_notifications');
        if (saved) {
            const parsed = JSON.parse(saved);
            notifications.counts = parsed;
            updateTotalUnread();
        }
    } catch (e) {
        console.error('Error loading notifications:', e);
    }
}

// Save to localStorage
function saveToStorage() {
    try {
        localStorage.setItem('chat_notifications', JSON.stringify(notifications.counts));
    } catch (e) {
        console.error('Error saving notifications:', e);
    }
}

// Update total unread count
function updateTotalUnread() {
    notifications.totalUnread = Object.values(notifications.counts).reduce((sum, count) => sum + count, 0);
}

// Initialize
loadFromStorage();

export function useNotifications() {
    // Add notification for a user
    const addNotification = (userId) => {
        if (!notifications.counts[userId]) {
            notifications.counts[userId] = 0;
        }
        notifications.counts[userId]++;
        updateTotalUnread();
        saveToStorage();
    };

    // Mark notifications as read for a user
    const markAsRead = (userId) => {
        if (notifications.counts[userId]) {
            notifications.counts[userId] = 0;
            updateTotalUnread();
            saveToStorage();
        }
    };

    // Clear all notifications for a user
    const clearNotifications = (userId) => {
        if (notifications.counts[userId]) {
            delete notifications.counts[userId];
            updateTotalUnread();
            saveToStorage();
        }
    };

    // Get notification count for a user
    const getNotificationCount = (userId) => {
        return notifications.counts[userId] || 0;
    };

    // Clear all notifications
    const clearAllNotifications = () => {
        notifications.counts = {};
        notifications.totalUnread = 0;
        saveToStorage();
    };

    // Setup global Echo listener
    const setupGlobalListener = () => {
        if (window.Echo) {
            const userId = window.Laravel?.user?.id;
            if (userId) {
                const channelName = `chat.${userId}`;
                const channel = window.Echo.private(channelName);

                channel.listen('.MessageSent', (e) => {
                    if (e.message && e.message.sender_id !== userId) {
                        addNotification(e.message.sender_id);
                    }
                });

                // Return cleanup function
                return () => {
                    window.Echo.leave(channelName);
                };
            }
        }
        return () => {};
    };

    return {
        notifications: computed(() => notifications.counts),
        totalUnread: computed(() => notifications.totalUnread),
        addNotification,
        markAsRead,
        clearNotifications,
        getNotificationCount,
        clearAllNotifications,
        setupGlobalListener
    };
} 