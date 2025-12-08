<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, nextTick, onUnmounted, watch } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

const newMessage = ref("");
const messages = ref([]);
const messagesContainer = ref(null);
const debugLog = ref([]);
const page = usePage();
const isTyping = ref(false);
const otherUserTyping = ref(false);
const replyTo = ref(null);
const typingTimeout = ref(null);
const fileInput = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const notificationSound = ref(null);
const otherUserOnline = ref(false);

// Use notifications composable
const {
    addNotification,
    markAsRead,
    clearNotifications,
    getNotificationCount
} = useNotifications();

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    initialMessages: {
        type: Object,
        required: true,
    }
})

const setupUserOnlineListener = () => {
    if (window.Echo) {
        try {
            const channel = window.Echo.join('online-users');

            // When user joins
            channel.here((users) => {
                addDebug(`Users currently online: ${users.length}`);
                const isUserHere = users.some(u => u.id === props.user.id);
                otherUserOnline.value = isUserHere;
            })
                .joining((user) => {
                    addDebug(`User joining: ${user.name}`);
                    if (user.id === props.user.id) {
                        otherUserOnline.value = true;
                    }
                })
                .leaving((user) => {
                    addDebug(`User leaving: ${user.name}`);
                    if (user.id === props.user.id) {
                        otherUserOnline.value = false;
                    }
                })
                .listen('.UserOnlineStatusUpdated', (e) => {
                    addDebug(`Online status update received: ${JSON.stringify(e)}`);
                    if (e.user && e.user.id === props.user.id) {
                        otherUserOnline.value = e.is_online;
                    }
                });

            return () => {
                window.Echo.leave('online-users');
            };
        } catch (error) {
            console.error('Online listener error:', error);
        }
    }
    return () => { };
}

// Watch for user prop changes to update online status
watch(() => props.user, (newUser) => {
    otherUserOnline.value = newUser.is_online;
}, { immediate: true });

const addDebug = (message) => {
    const timestamp = new Date().toLocaleTimeString();
    debugLog.value.push(`${timestamp}: ${message}`);
    console.log(message);
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: `Chat with ${props.user.name}`,
        href: '#',
    }
];

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
}

const getRandomColor = (id) => {
    const colors = [
        'bg-blue-500', 'bg-green-500', 'bg-purple-500',
        'bg-pink-500', 'bg-indigo-500', 'bg-teal-500',
        'bg-orange-500', 'bg-cyan-500', 'bg-amber-500'
    ];
    return colors[id % colors.length];
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTo({
                top: messagesContainer.value.scrollHeight,
                behavior: 'smooth'
            });
        }
    });
}

const getMessageSender = (message) => {
    return message.sender_id === page.props.auth.user.id ? 'You' : props.user.name;
}

const setTyping = (isTypingValue) => {
    isTyping.value = isTypingValue;

    axios.post(`/typing/${props.user.id}`, {
        is_typing: isTypingValue
    }).then(() => {
        addDebug(`Typing status sent: ${isTypingValue}`);
    }).catch(error => {
        console.error('Error sending typing status:', error);
    });
}

const playNotificationSound = () => {
    if (notificationSound.value) {
        notificationSound.value.currentTime = 0;
        notificationSound.value.play().catch(e => console.log('Audio play failed:', e));
    }
}

const handleTyping = () => {
    if (!isTyping.value) {
        setTyping(true);
    }

    clearTimeout(typingTimeout.value);
    typingTimeout.value = setTimeout(() => {
        setTyping(false);
    }, 1000);
}

const sendMessage = () => {
    if (newMessage.value.trim() !== "") {
        const payload = {
            message: newMessage.value
        };

        if (replyTo.value) {
            payload.reply_to = replyTo.value.id;
        }

        axios.post(`/messages/${props.user.id}`, payload)
            .then((response) => {
                addDebug(`Message sent successfully: ${response.data.id}`);
                newMessage.value = "";
                clearReply();
                setTyping(false);
                clearTimeout(typingTimeout.value);
                scrollToBottom();
            }).catch(error => {
                addDebug(`Error sending message: ${error.response?.data?.message || error.message}`);
                console.error('Error sending message: ', error);
            });
    }
}

const replyToMessage = (message) => {
    replyTo.value = message;
    addDebug(`Replying to message: ${message.body.substring(0, 30)}...`);
    nextTick(() => {
        document.querySelector('input[type="text"]')?.focus();
    });
}

const clearReply = () => {
    replyTo.value = null;
}

const clearMessages = () => {
    if (confirm('Are you sure you want to clear all messages in this chat? This will only clear messages for you. The other user will still see them.')) {
        axios.delete(`/messages/${props.user.id}/clear`)
            .then((response) => {
                messages.value = [];
                addDebug('Messages cleared locally');
                clearNotifications(props.user.id);
                alert(response.data.message || 'Messages cleared successfully (for you only)');
            }).catch(error => {
                addDebug(`Error clearing messages: ${error.response?.data?.message || error.message}`);
                console.error('Error clearing messages: ', error);
                alert('Error clearing messages. Please try again.');
            });
    }
}

// File upload functions
const openFilePicker = () => {
    fileInput.value?.click();
}

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        uploadFile(file);
    }
    event.target.value = '';
}

const uploadFile = (file) => {
    if (!file) return;

    const maxSize = 20 * 1024 * 1024;
    if (file.size > maxSize) {
        alert(`File is too large. Maximum size is 20MB.`);
        return;
    }

    isUploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    formData.append('file', file);

    if (replyTo.value) {
        formData.append('reply_to', replyTo.value.id);
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    axios.post(`/messages/${props.user.id}/upload`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'Accept': 'application/json'
        },
        onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                uploadProgress.value = percentCompleted;
            }
        }
    })
        .then((response) => {
            uploadProgress.value = 100;

            if (response.data.success && response.data.message) {
                addDebug(`File uploaded successfully: ${response.data.message.file_name}`);
                addDebug(`Message type: ${response.data.message.type}`);

                const messageExists = messages.value.some(msg => msg.id === response.data.message.id);
                if (!messageExists) {
                    messages.value.push(response.data.message);
                    scrollToBottom();
                }
            } else {
                addDebug('Upload response format unexpected:', response.data);
            }

            clearReply();

            setTimeout(() => {
                isUploading.value = false;
                uploadProgress.value = 0;
            }, 500);
        })
        .catch(error => {
            isUploading.value = false;
            uploadProgress.value = 0;

            let errorMessage = 'Failed to upload file';
            if (error.response?.data?.error) {
                errorMessage = error.response.data.error;
            } else if (error.response?.data?.message) {
                errorMessage = error.response.data.message;
            } else if (error.message) {
                errorMessage = error.message;
            }

            addDebug(`Error uploading file: ${errorMessage}`);
            alert(`Upload failed: ${errorMessage}`);
            console.error('Upload error details:', error);
        });
}

const handleDrop = (event) => {
    event.preventDefault();
    event.stopPropagation();

    const files = event.dataTransfer.files;
    if (files.length > 0) {
        uploadFile(files[0]);
    }
}

const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
    event.dataTransfer.dropEffect = 'copy';
}

const getFileIcon = (mimeType) => {
    if (!mimeType) return '📎';

    if (mimeType.includes('image')) {
        return '📷';
    } else if (mimeType.includes('pdf')) {
        return '📄';
    } else if (mimeType.includes('word') || mimeType.includes('document')) {
        return '📝';
    } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
        return '📊';
    } else if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) {
        return '📈';
    } else if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('archive')) {
        return '📦';
    } else {
        return '📎';
    }
}

const downloadFile = (message) => {
    if (message.file_url) {
        window.open(message.file_url, '_blank');
    }
}

onMounted(() => {
    messages.value = props.initialMessages;
    addDebug(`Component mounted with ${props.initialMessages.length} initial messages`);
    addDebug(`Chatting with user ID: ${props.user.id}`);
    addDebug(`Your user ID: ${page.props.auth.user.id}`);

    // Initialize with current status
    otherUserOnline.value = props.user.is_online ||
        (props.user.last_seen_at && new Date(props.user.last_seen_at) > new Date(Date.now() - 3 * 60 * 1000));

    // Setup online listener
    const cleanupOnlineListener = setupUserOnlineListener();

    // Create audio element for notification sound
    notificationSound.value = new Audio('/notification.mp3');
    notificationSound.value.volume = 0.3;

    // Mark all notifications as read when opening chat
    markAsRead(props.user.id);

    // Initialize Echo
    initializeEcho();
    scrollToBottom();

    // Cleanup on unmount
    onUnmounted(() => {
        if (cleanupOnlineListener) cleanupOnlineListener();
        if (window.Echo) {
            window.Echo.leave(`chat.${page.props.auth.user.id}`);
            addDebug('Echo listeners cleaned up');
        }
        clearTimeout(typingTimeout.value);
    });
});

const getLastSeenTime = (lastSeenAt) => {
    if (!lastSeenAt) return 'a while ago';

    const lastSeen = new Date(lastSeenAt);
    const now = new Date();
    const diffMs = now - lastSeen;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins} minutes ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours} hours ago`;

    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays} days ago`;
};

const initializeEcho = () => {
    if (!window.Echo) {
        addDebug('❌ Echo is not available on window object');
        return;
    }

    addDebug('🔄 Setting up Echo connection...');

    const pusher = window.Echo.connector.pusher;

    pusher.connection.bind('connected', () => {
        addDebug('✅ Connected to Reverb server');
        setupChannelListeners();
    });

    pusher.connection.bind('connecting', () => {
        addDebug('🔄 Connecting to Reverb server...');
    });

    pusher.connection.bind('disconnected', () => {
        addDebug('🔌 Disconnected from Reverb server');
    });

    pusher.connection.bind('error', (error) => {
        addDebug(`❌ Connection error: ${JSON.stringify(error)}`);
    });

    setupChannelListeners();
}

const setupChannelListeners = () => {
    const yourChannelName = `chat.${page.props.auth.user.id}`;
    addDebug(`Setting up listener for YOUR channel: ${yourChannelName}`);

    try {
        const yourChannel = window.Echo.private(yourChannelName);

        yourChannel.subscribed(() => {
            addDebug(`✅ Successfully subscribed to YOUR channel: ${yourChannelName}`);
        }).error((error) => {
            addDebug(`❌ Your channel subscription failed: ${JSON.stringify(error)}`);
        });

        // Listen for all MessageSent events
        yourChannel.listen('.MessageSent', (e) => {
            addDebug(`🎯 MessageSent event received: ${JSON.stringify(e)}`);
            handleIncomingEvent(e);
        });

        // Listen to all events for debugging
        yourChannel.listenToAll((event, data) => {
            if (event !== 'pusher:subscription_succeeded' && event !== 'pusher:ping') {
                addDebug(`📢 Your channel - ${event}: ${JSON.stringify(data)}`);
            }
        });

    } catch (error) {
        addDebug(`❌ Error setting up channels: ${error.message}`);
    }
}

const handleIncomingEvent = (e) => {
    // Handle typing updates
    if (e.isTypingUpdate) {
        addDebug(`Typing update: User ${e.userId} is typing: ${e.isTyping}`);
        if (e.userId === page.props.auth.user.id) {
            // This is your typing status from server
        } else if (e.userId === props.user.id) {
            otherUserTyping.value = e.isTyping;
            // Clear typing indicator after 2 seconds
            setTimeout(() => {
                otherUserTyping.value = false;
            }, 2000);
        }
        return;
    }

    // Handle clear messages
    if (e.isClearMessages) {
        addDebug(`Messages cleared for user ${e.clearedForUserId}`);
        if (e.clearedForUserId === page.props.auth.user.id) {
            messages.value = [];
            addDebug('Clearing messages from UI for current user');
            clearNotifications(props.user.id);
        }
        return;
    }

    // Handle new message
    if (e.message) {
        addDebug(`Incoming message - Type: ${e.message.type}, Body: "${e.message.body}"`);
        addDebug(`Message data: ${JSON.stringify(e.message)}`);

        const isRelevantMessage =
            (e.message.sender_id === props.user.id && e.message.receiver_id === page.props.auth.user.id) ||
            (e.message.sender_id === page.props.auth.user.id && e.message.receiver_id === props.user.id);

        if (isRelevantMessage) {
            addDebug(`✅ Adding relevant message to UI: "${e.message.body}" (ID: ${e.message.id})`);

            const messageExists = messages.value.some(msg => msg.id === e.message.id);
            if (!messageExists) {
                messages.value.push(e.message);
                scrollToBottom();

                // If message is from other user, add notification and play sound
                if (e.message.sender_id === props.user.id) {
                    playNotificationSound();
                    addNotification(props.user.id);
                }
            } else {
                addDebug('⚠️ Message already exists, skipping...');
            }
        } else {
            addDebug(`🚫 Ignoring irrelevant message: ${e.message.sender_id} -> ${e.message.receiver_id}`);

            // Check if this is a notification from another chat
            if (e.message.sender_id !== page.props.auth.user.id &&
                e.message.receiver_id === page.props.auth.user.id) {
                playNotificationSound();
                addNotification(e.message.sender_id);
            }
        }
    } else {
        addDebug(`❌ No message data in event: ${JSON.stringify(e)}`);
    }
}

const loadMessages = () => {
    axios.get(`/messages/${props.user.id}`).then((response) => {
        messages.value = response.data;
        // Mark notifications as read when loading messages
        markAsRead(props.user.id);
        scrollToBottom();
    }).catch(error => {
        console.error('Error loading messages: ', error);
    });
}

const clearDebug = () => {
    debugLog.value = [];
}

const testBroadcast = () => {
    addDebug('🧪 Testing ShouldBroadcastNow...');
    axios.post(`/messages/${props.user.id}`, {
        message: 'Test message - ShouldBroadcastNow'
    }).then((response) => {
        addDebug('Test message sent - should appear instantly via broadcast');
    });
}

const testTyping = () => {
    addDebug('🧪 Testing typing indicator...');
    setTyping(true);
    setTimeout(() => setTyping(false), 2000);
}
</script>

<template>

    <Head :title="`Chat with ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-4 md:p-6">
            <!-- Online/Offline Status -->
            <div class="mb-4">
                <div v-if="otherUserOnline" class="flex items-center space-x-2 text-xs md:text-sm text-green-600">
                    <div class="flex items-center">
                        <div class="relative mr-1">
                            <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full"></div>
                            <div class="absolute w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full animate-ping">
                            </div>
                        </div>
                        <span class="truncate">Online</span>
                    </div>
                </div>
                <div v-else class="text-xs md:text-sm text-gray-500">
                    Last seen {{ getLastSeenTime(user.last_seen_at) }}
                </div>
            </div>

            <!-- Upload Progress -->
            <div v-if="isUploading" class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 md:p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="animate-spin rounded-full h-5 w-5 md:h-6 md:w-6 border-b-2 border-blue-500"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-blue-700">Uploading file...</p>
                        <div class="w-full bg-blue-200 rounded-full h-1.5 md:h-2 mt-1">
                            <div class="bg-blue-600 h-1.5 md:h-2 rounded-full transition-all duration-300"
                                :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <p class="text-xs text-blue-600 mt-1">{{ uploadProgress }}%</p>
                    </div>
                </div>
            </div>

            <!-- Chat Header -->
            <div class="bg-white rounded-lg shadow p-3 md:p-4 mb-4 md:mb-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-3 md:space-y-0">
                    <div class="flex items-center space-x-3 md:space-x-4">
                        <div
                            :class="['flex-shrink-0 h-10 w-10 md:h-12 md:w-12 rounded-full flex items-center justify-center text-white font-medium text-sm md:text-base', getRandomColor(user.id)]">
                            {{ getInitials(user.name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-lg md:text-xl font-semibold text-gray-900 truncate">{{ user.name }}</h2>
                            <p class="text-xs md:text-sm text-gray-600 truncate">{{ user.email }}</p>
                            <!-- Typing Indicator -->
                            <div v-if="otherUserTyping"
                                class="flex items-center space-x-2 text-xs md:text-sm text-green-600 mt-1">
                                <div class="flex space-x-1">
                                    <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full animate-bounce">
                                    </div>
                                    <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full animate-bounce"
                                        style="animation-delay: 0.1s"></div>
                                    <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full animate-bounce"
                                        style="animation-delay: 0.2s"></div>
                                </div>
                                <span class="truncate">typing...</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2 md:space-x-2">
                        <button @click="loadMessages"
                            class="flex-1 md:flex-none px-3 py-1.5 md:px-4 md:py-2 text-xs md:text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            Refresh
                        </button>
                        <button @click="clearMessages"
                            class="flex-1 md:flex-none px-3 py-1.5 md:px-4 md:py-2 text-xs md:text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                            Clear Chat
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages Container with Drop Zone -->
            <div ref="messagesContainer" @dragover="handleDragOver" @drop="handleDrop"
                class="bg-white rounded-lg shadow h-[60vh] md:h-96 overflow-y-auto p-3 md:p-4 mb-3 md:mb-4 relative">

                <!-- Drop Zone Overlay -->
                <div v-if="false"
                    class="absolute inset-0 bg-blue-50 border-2 md:border-4 border-dashed border-blue-300 rounded-lg flex items-center justify-center z-10">
                    <div class="text-center p-4 md:p-8">
                        <svg class="w-8 h-8 md:w-12 md:h-12 text-blue-400 mx-auto mb-2 md:mb-4" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-sm md:text-base font-medium text-blue-600">Drop files here to upload</p>
                        <p class="text-xs md:text-sm text-blue-500 mt-1">Supports images and documents up to 20MB</p>
                    </div>
                </div>

                <div class="space-y-3 md:space-y-4">
                    <div v-for="message in messages" :key="message.id"
                        :class="['flex', message.sender_id === page.props.auth.user.id ? 'justify-end' : 'justify-start']">
                        <div class="max-w-[80%] md:max-w-xs lg:max-w-md">
                            <!-- Reply Indicator -->
                            <div v-if="message.reply_to" class="mb-1">
                                <div class="text-xs text-gray-500 mb-1">Replying to</div>
                                <div
                                    class="bg-gray-100 border-l-2 border-gray-300 pl-2 py-1 text-xs text-gray-600 rounded">
                                    <p class="truncate">{{ message.replied_to?.body || 'Original message' }}</p>
                                </div>
                            </div>

                            <!-- File Message -->
                            <div v-if="message.type && message.type !== 'text'" :class="[
                                'px-3 py-2 md:px-4 md:py-3 rounded-lg cursor-pointer transition-all hover:shadow-md',
                                message.sender_id === page.props.auth.user.id
                                    ? 'bg-blue-100 border border-blue-200'
                                    : 'bg-gray-100 border border-gray-200'
                            ]" @click="downloadFile(message)">

                                <!-- Image Preview -->
                                <div v-if="message.is_image" class="mb-2">
                                    <img :src="message.file_url" :alt="message.file_name"
                                        class="rounded-lg max-h-32 md:max-h-48 w-auto mx-auto object-cover cursor-pointer hover:opacity-90 transition-opacity"
                                        @click.stop="window.open(message.file_url, '_blank')">
                                </div>

                                <!-- Document Preview -->
                                <div v-else class="flex items-center space-x-2 md:space-x-3">
                                    <div class="flex-shrink-0 text-xl md:text-2xl">
                                        {{ getFileIcon(message.mime_type) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs md:text-sm font-medium truncate"
                                            :class="message.sender_id === page.props.auth.user.id ? 'text-blue-800' : 'text-gray-800'">
                                            {{ message.file_name }}
                                        </p>
                                        <p class="text-xs"
                                            :class="message.sender_id === page.props.auth.user.id ? 'text-blue-600' : 'text-gray-600'">
                                            {{ message.file_size }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg class="w-4 h-4 md:w-5 md:h-5"
                                            :class="message.sender_id === page.props.auth.user.id ? 'text-blue-500' : 'text-gray-500'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </div>
                                </div>

                                <p class="text-xs mt-1 md:mt-2"
                                    :class="message.sender_id === page.props.auth.user.id ? 'text-blue-600' : 'text-gray-600'">
                                    {{ message.body }} • {{ new Date(message.created_at).toLocaleTimeString([], {
                                        hour:
                                            '2-digit', minute: '2-digit'
                                    }) }}
                                </p>
                            </div>

                            <!-- Text Message -->
                            <div v-else :class="[
                                'px-3 py-2 md:px-4 md:py-2 rounded-lg cursor-pointer hover:opacity-90 transition-opacity',
                                message.sender_id === page.props.auth.user.id
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 text-gray-900'
                            ]" @click="replyToMessage(message)">
                                <p class="text-sm break-words">{{ message.body }}</p>
                                <p :class="[
                                    'text-xs mt-1 flex items-center justify-between',
                                    message.sender_id === page.props.auth.user.id
                                        ? 'text-blue-100'
                                        : 'text-gray-500'
                                ]">
                                    <span>{{ new Date(message.created_at).toLocaleTimeString([], {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) }}</span>
                                    <span class="ml-2 hidden md:inline">Click to reply</span>
                                    <span class="ml-2 md:hidden">↩️</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="messages.length === 0" class="text-center py-6 md:py-8">
                        <div class="mb-3 md:mb-4">
                            <svg class="w-12 h-12 md:w-16 md:h-16 text-gray-300 mx-auto" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-sm md:text-base text-gray-500">No messages yet. Start the conversation!</p>
                        <p class="text-xs md:text-sm text-gray-400 mt-1 md:mt-2">You can also drag & drop files here</p>
                    </div>
                </div>
            </div>

            <!-- REPLY PREVIEW -->
            <div v-if="replyTo"
                class="mb-3 md:mb-4 p-2 md:p-3 bg-blue-50 border-l-2 md:border-l-4 border-blue-500 rounded-lg shadow-sm">
                <div class="flex justify-between items-start">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-1 md:space-x-2 mb-1">
                            <svg class="w-3 h-3 md:w-4 md:h-4 text-blue-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            <p class="text-xs md:text-sm font-medium text-blue-700 truncate">Replying to {{
                                getMessageSender(replyTo) }}</p>
                        </div>
                        <div class="pl-4 md:pl-6">
                            <p
                                class="text-xs md:text-sm text-blue-600 bg-blue-100 px-2 py-1 md:px-3 md:py-2 rounded truncate">
                                {{ replyTo.body }}</p>
                        </div>
                    </div>
                    <button @click="clearReply"
                        class="ml-1 md:ml-2 p-0.5 md:p-1 text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded-full transition-colors flex-shrink-0"
                        title="Cancel reply">
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Message Input with File Upload -->
            <div class="bg-white rounded-lg shadow p-3 md:p-4">
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- Hidden file input -->
                    <input type="file" ref="fileInput" @change="handleFileSelect"
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" class="hidden">

                    <!-- File Upload Button -->
                    <button @click="openFilePicker"
                        class="p-1.5 md:p-2 text-gray-500 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors flex-shrink-0"
                        title="Upload file">
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>

                    <!-- Text Input -->
                    <input type="text" v-model="newMessage" @input="handleTyping" @keyup.enter="sendMessage"
                        placeholder="Type a message..."
                        class="flex-1 px-3 py-1.5 md:px-4 md:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm md:text-base" />

                    <!-- Send Button -->
                    <button @click="sendMessage" :disabled="!newMessage.trim() && !isUploading"
                        class="px-4 py-1.5 md:px-6 md:py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm md:text-base flex-shrink-0">
                        Send
                    </button>
                </div>

                <!-- File Upload Info -->
                <div class="mt-2 text-xs text-gray-500 hidden md:block">
                    <p>📷 Images: JPEG, PNG, GIF, WebP, SVG, BMP (Max 10MB)</p>
                    <p>📎 Documents: PDF, Word, Excel, PowerPoint, TXT, CSV, ZIP, RAR (Max 20MB)</p>
                </div>
                <div class="mt-1 text-[10px] md:text-xs text-gray-500 md:hidden">
                    <p>📷 Images & 📎 Documents (Max 20MB)</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>