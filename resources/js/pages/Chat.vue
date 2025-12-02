<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, nextTick, onUnmounted } from 'vue';

const newMessage = ref("");
const messages = ref([]);
const messagesContainer = ref(null);
const debugLog = ref([]);
const page = usePage();

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

const sendMessage = () => {
    if (newMessage.value.trim() !== "") {
        addDebug(`Sending message: "${newMessage.value}" to user ${props.user.id}`);

        axios.post(`/messages/${props.user.id}`, {
            message: newMessage.value
        }).then((response) => {
            addDebug(`Message sent successfully: ${response.data.id}`);
            // Don't add to messages array here - wait for the broadcast
            // This prevents duplicates when ShouldBroadcastNow is used
            newMessage.value = "";
            scrollToBottom();
        }).catch(error => {
            addDebug(`Error sending message: ${error.response?.data?.message || error.message}`);
            console.error('Error sending message: ', error);
        });
    }
}

onMounted(() => {
    messages.value = props.initialMessages;
    addDebug(`Component mounted with ${props.initialMessages.length} initial messages`);
    addDebug(`Chatting with user ID: ${props.user.id}`);
    addDebug(`Your user ID: ${page.props.auth.user.id}`);

    // Initialize Echo with ShouldBroadcastNow approach
    initializeEcho();
    scrollToBottom();
});

const initializeEcho = () => {
    if (!window.Echo) {
        addDebug('❌ Echo is not available on window object');
        return;
    }

    addDebug('🔄 Setting up Echo connection (ShouldBroadcastNow)...');

    // Monitor connection status
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

    // Set up listeners immediately
    setupChannelListeners();
}

const setupChannelListeners = () => {
    // Listen to YOUR OWN channel
    const yourChannelName = `chat.${page.props.auth.user.id}`;
    addDebug(`Setting up listener for YOUR channel: ${yourChannelName}`);

    try {
        const yourChannel = window.Echo.private(yourChannelName);

        // Monitor subscription
        yourChannel.subscribed(() => {
            addDebug(`✅ Successfully subscribed to YOUR channel: ${yourChannelName}`);
        }).error((error) => {
            addDebug(`❌ Your channel subscription failed: ${JSON.stringify(error)}`);
        });

        // Listen for MessageSent events on your channel
        yourChannel.listen('.MessageSent', (e) => {
            addDebug(`🎯 MessageSent event received on your channel: ${JSON.stringify(e)}`);
            handleIncomingMessage(e);
        });

        // Also listen to the other user's channel (optional - for testing)
        const otherUserChannelName = `chat.${props.user.id}`;
        addDebug(`Also setting up listener for other user's channel: ${otherUserChannelName}`);

        const otherUserChannel = window.Echo.private(otherUserChannelName);

        otherUserChannel.subscribed(() => {
            addDebug(`✅ Also subscribed to other user's channel: ${otherUserChannelName}`);
        }).listen('.MessageSent', (e) => {
            addDebug(`🎯 MessageSent event received on other user's channel: ${JSON.stringify(e)}`);
            handleIncomingMessage(e);
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

const handleIncomingMessage = (e) => {
    if (e.message) {
        // Check if this message belongs to the current chat conversation
        const isRelevantMessage =
            (e.message.sender_id === props.user.id && e.message.receiver_id === page.props.auth.user.id) ||
            (e.message.sender_id === page.props.auth.user.id && e.message.receiver_id === props.user.id);

        if (isRelevantMessage) {
            addDebug(`✅ Adding relevant message to UI: "${e.message.body}" (ID: ${e.message.id})`);

            // Check if message already exists to avoid duplicates
            const messageExists = messages.value.some(msg => msg.id === e.message.id);
            if (!messageExists) {
                messages.value.push(e.message);
                scrollToBottom();

                // Play a sound or show notification for new incoming messages
                if (e.message.sender_id !== page.props.auth.user.id) {
                    addDebug('🔔 New message from other user');
                    // You could add a notification sound here
                }
            } else {
                addDebug('⚠️ Message already exists, skipping...');
            }
        } else {
            addDebug(`🚫 Ignoring irrelevant message: ${e.message.sender_id} -> ${e.message.receiver_id}`);
        }
    } else {
        addDebug(`❌ No message data in event: ${JSON.stringify(e)}`);
    }
}

// Clean up Echo listeners when component unmounts
onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`chat.${page.props.auth.user.id}`);
        window.Echo.leave(`chat.${props.user.id}`);
        addDebug('Echo listeners cleaned up');
    }
});

const loadMessages = () => {
    axios.get(`/messages/${props.user.id}`).then((response) => {
        messages.value = response.data;
        scrollToBottom();
    }).catch(error => {
        console.error('Error loading messages: ', error);
    });
}

const clearDebug = () => {
    debugLog.value = [];
}

// Test function to verify ShouldBroadcastNow is working
const testBroadcast = () => {
    addDebug('🧪 Testing ShouldBroadcastNow...');
    axios.post(`/messages/${props.user.id}`, {
        message: 'Test message - ShouldBroadcastNow'
    }).then((response) => {
        addDebug('Test message sent - should appear instantly via broadcast');
    });
}
</script>

<template>

    <Head :title="`Chat with ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6">
            <!-- Debug Panel -->
            <!-- <div class="mb-4 bg-gray-100 p-4 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-gray-700">Debug Log (ShouldBroadcastNow)</h3>
                    <div class="space-x-2">
                        <button @click="testBroadcast" class="text-xs bg-green-500 text-white px-2 py-1 rounded">
                            Test Broadcast
                        </button>
                        <button @click="clearDebug" class="text-xs bg-red-500 text-white px-2 py-1 rounded">
                            Clear
                        </button>
                    </div>
                </div>
                <div class="max-h-32 overflow-y-auto text-xs font-mono">
                    <div v-for="(log, index) in debugLog.slice(-10)" :key="index" class="py-1 border-b border-gray-200">
                        {{ log }}
                    </div>
                    <div v-if="debugLog.length === 0" class="text-gray-500">
                        No debug messages yet...
                    </div>
                </div>
            </div> -->

            <!-- Chat Header -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex items-center space-x-4">
                    <div
                        :class="['flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center text-white font-medium', getRandomColor(user.id)]">
                        {{ getInitials(user.name) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ user.name }}</h2>
                        <p class="text-gray-600">{{ user.email }}</p>
                        <!-- <p class="text-sm text-gray-500">Real-time chat using ShouldBroadcastNow</p> -->
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div ref="messagesContainer" class="bg-white rounded-lg shadow h-96 overflow-y-auto p-4 mb-4">
                <div class="space-y-4">
                    <div v-for="message in messages" :key="message.id"
                        :class="['flex', message.sender_id === page.props.auth.user.id ? 'justify-end' : 'justify-start']">
                        <div :class="[
                            'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
                            message.sender_id === page.props.auth.user.id
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-200 text-gray-900'
                        ]">
                            <p class="text-sm">{{ message.body }}</p>
                            <p :class="[
                                'text-xs mt-1',
                                message.sender_id === page.props.auth.user.id
                                    ? 'text-blue-100'
                                    : 'text-gray-500'
                            ]">
                                {{ new Date(message.created_at).toLocaleTimeString() }}
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="messages.length === 0" class="text-center py-8">
                        <p class="text-gray-500">No messages yet. Start the conversation!</p>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center space-x-4">
                    <input type="text" v-model="newMessage" @keyup.enter="sendMessage" placeholder="Type a message..."
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                    <button @click="sendMessage" :disabled="!newMessage.trim()"
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Send
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>