<template>
    <p v-if="!messages">Loading posts...</p>
    <div v-else class="chat-message" v-for="message in messages" @click.once="hasSeen(message.id)">
        <div class="flex items-end" :class="{'justify-end': message.user.id === user.id}">
            <div class="flex flex-col space-y-2 max-w-xs mx-2 order-2"
                 :class="{'items-end': message.user.id === user.id, 'items-start': message.user.id !== user.id}">
                <div>
                    <div>
                        {{ message.user.id !== user.id ? message.user.name : 'вы' }}
                    </div>
                    <div class="px-4 py-2 rounded-lg inline-block w-full"
                         :class="{'bg-blue-600 text-white': message.user.id === user.id, 'bg-gray-300 text-gray-600': message.user.id !== user.id}"
                    >
                        {{ message.message }}
                        <p v-if="message.created_at" class="text-xs text-right">{{ moment(message.created_at).fromNow() }}</p>
                    </div>
                    <div v-if="message.messages_seen" class="bg-gray-50 text-xs px-4 mt-1 py-2">
                        Список прочитавших:
                        <ul>
                            <li v-for="seen in message.messages_seen">
                                <span v-if="seen">{{seen.user.name}}</span>
                            </li>
                        </ul>
                    </div>
                    <div v-if="message.status" class="text-sm">{{ message.status }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from "moment";
import axios from "axios";
export default {
    name: 'ChatMessages',
    props: {
        user: {
            required: true,
            type: Object
        },
        messages: {
            required: false,
            type: Object
        }
    },
    data() {
        return {
            moment: moment
        }
    },
    methods: {
        hasSeen: (id) => {
            console.log(id)
            axios.post('/seen', {message_id: Number(id)})
        }
    }
};
</script>
