import { ref, computed } from 'vue'

const notifications = ref({}) // Structure: { [userId]: count }

export function useNotifications() {
    // Get total unread count
    const totalUnread = computed(() => {
        return Object.values(notifications.value).reduce((sum, count) => sum + count, 0)
    })

    // Get unread count for a specific user
    const getUnreadCount = (userId) => {
        return notifications.value[userId] || 0
    }

    // Increment unread count for a user
    const incrementUnread = (userId) => {
        if (!notifications.value[userId]) {
            notifications.value[userId] = 0
        }
        notifications.value[userId]++
    }

    // Reset unread count for a user (when opening chat)
    const resetUnread = (userId) => {
        notifications.value[userId] = 0
    }

    // Set specific count
    const setUnreadCount = (userId, count) => {
        notifications.value[userId] = count
    }

    // In your notification store or component
const playNotificationSound = () => {
    const audio = new Audio('/sounds/notification.mp3'); // Add this file to public/sounds/
    audio.play().catch(e => console.log('Audio play failed:', e));
}

// Or use a simple beep
const playBeepSound = () => {
    const context = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = context.createOscillator();
    const gainNode = context.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(context.destination);
    
    oscillator.frequency.value = 800;
    oscillator.type = 'sine';
    gainNode.gain.setValueAtTime(0.3, context.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.5);
    
    oscillator.start(context.currentTime);
    oscillator.stop(context.currentTime + 0.5);
}

    return {
        notifications,
        totalUnread,
        getUnreadCount,
        incrementUnread,
        resetUnread,
        setUnreadCount,
        playBeepSound,
        playNotificationSound,
        
    }
}