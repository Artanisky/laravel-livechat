<template>
    <AppLayout title="Chat">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Chat
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto flex space-x-6 flex-row items-start sm:px-6 lg:px-8">
                <div class="bg-white basis-3/4 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="mx-6 py-4">
                        <p class="text-lg text-blue-600 mb-4">Сообщение</p>
                        <div class="border rounded-lg p-4">
                            <div class="overflow-y-auto h-96" ref="chat">
                                <div
                                    class="flex flex-col space-y-4 p-3 ">
                                    <chat-messages :user="$page.props.auth.user" :messages="messages"></chat-messages>
                                </div>
                            </div>
                            <div class="border-t-2 border-gray-200 pt-4 mb-2 sm:mb-0">
                                <div class="relative flex">
                                    <input v-model="newMessage" type="text"
                                           placeholder="Сообщение"
                                           @keyup.enter="sendMessage"
                                           @keydown="sendTypingEvent"
                                           class="w-full border-0 focus:outline-none text-gray-600 pl-4 rounded bg-gray-200 py-3"
                                    >
                                    <div class="absolute right-1 items-center inset-y-0 hidden sm:flex">
                                        <button @click="sendMessage" type="button"
                                                class="inline-flex items-center justify-center rounded h-8 w-20 transition duration-500 ease-in-out text-white bg-blue-500 hover:bg-blue-400 focus:outline-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                 fill="currentColor"
                                                 class="h-6 w-6 transform rotate-90">
                                                <path
                                                    d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-gray-500 pt-2 animate-pulse" v-if="activeUser">
                                    {{ activeUser.name }} печатает...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <aside class="basis-1/4 ">
                    <div class="bg-white shadow-xl sm:rounded-lg px-6 py-4 mb-6">
                        <p class="text-lg text-blue-600">Состояние</p>
                        <p>Имя пользователя: {{ $page.props.auth.user.name }}</p>
                    </div>
                    <div class="bg-white shadow-xl sm:rounded-lg px-6 py-4 mb-6">
                        <p class="text-lg text-blue-600 mb-4">Список пользователей</p>
                        <ul>
                            <li class="py-2" v-for="(user, index) in users" :key="index">
                                {{ user.name }}
                            </li>
                        </ul>
                    </div>
                    <div class="bg-white shadow-xl sm:rounded-lg px-6 py-4 mb-6">
                        <p class="text-lg text-blue-600 mb-4">Подсказка</p>
                        <p>Чтобы статус прочитал стал активным нужно нажать сообщение</p>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>


<script>
import {defineComponent} from 'vue'
import {usePage} from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";
import ChatMessages from "@/Components/ChatMessages.vue";
import axios from "axios";

export default defineComponent({
    name: "Chat",
    components: {ChatMessages, AppLayout},
    data() {
        const page = usePage()
        return {
            messages: [],
            newMessage: '',
            user: page.props.auth.user,
            users: [],
            activeUser: false,
            typingTimer: false,
        }
    },
    created() {
        this.fetchMessages();

        Echo.join('chat')
            .here(user => {
                this.users = user;
            })
            .joining(user => {
                this.users.push(user);
            })
            .leaving(user => {
                this.users = this.users.filter(u => u.id !== user.id);
            })
            .listen('MessageSent', (e) => {
                this.messages.push({
                    created_at: e.message.created_at,
                    id: e.message.id,
                    message: e.message.message,
                    updated_at: e.message.updated_at,
                    user: e.user,
                    user_id: e.user.id,
                });
            })
            .listen('MessageSeen', (e) => {
                console.log(e)
                const foundIndex = this.messages.findIndex(x => x.id === e.message.id);

                const newInfo = {
                    messages_seen: [
                        {
                            user: e.user
                        }
                    ]
                }

                this.messages[foundIndex] = {
                    ...this.messages[foundIndex],
                    ...newInfo
                };
                console.log(this.messages[foundIndex])
                console.log(e)
            })
            .listenForWhisper('typing', user => {
                console.log('asdasd')
                this.activeUser = user;

                if (this.typingTimer) {
                    clearTimeout(this.typingTimer);
                }

                this.typingTimer = setTimeout(() => {
                    this.activeUser = false;
                }, 3000);
            })
    },

    methods: {
        fetchMessages() {
            axios.get('messages').then(response => {
                this.messages = response.data;
            }).then(() => {
                this.$refs.chat.scrollTop = this.$refs.chat.scrollHeight;
            })
        },

        async sendMessage() {
            this.messages.push({
                message: this.newMessage,
                user: this.user,
                user_id: this.user.id,
                status: 'Отправляется'
            });

            await axios.post('/send', {message: this.newMessage})
                .then((response) => {
                    if (response.data) {
                        this.messages[this.messages.length - 1] = {
                            created_at: response.data.created_at,
                            id: response.data.id,
                            message: response.data.message,
                            updated_at: response.data.updated_at,
                            user: this.user,
                            user_id: this.user.id,
                            status: 'Доставлено'
                        }
                    }
                })
                .then(() => {
                    this.$refs.chat.scrollTop = this.$refs.chat.scrollHeight;
                })
                .catch((e) => {
                    console.log(e)
                    this.messages[this.messages.length - 1] = {
                        message: this.newMessage,
                        user: this.user,
                        user_id: this.user.id,
                        status: 'Не доставлено'
                    }
                })
            this.newMessage = '';
        },

        sendTypingEvent() {
            Echo.join('chat')
                .whisper('typing', this.user);
        }
    }
})
</script>
