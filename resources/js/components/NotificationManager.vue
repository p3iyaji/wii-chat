<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

const { setupGlobalListener, cleanupGlobalListener } = useNotifications();

onMounted(() => {
    console.log('🔔 NotificationManager mounted');

    // Setup global notification listener
    const cleanup = setupGlobalListener();

    // Request notification permission
    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission().then(permission => {
            console.log('Notification permission:', permission);
        });
    }

    // Store cleanup function
    onUnmounted(() => {
        console.log('🔔 NotificationManager unmounted');
        if (cleanup) cleanup();
        cleanupGlobalListener();
    });
});
</script>

<template>
    <!-- This component doesn't render anything, it just manages global notifications -->
</template>