import {defineStore} from 'pinia'
import axios from "axios";

export const useMessagesStore = defineStore({
    id: 'messages',
    state: () => ({
        messages: [],
        loading: false,
        error: null
    }),
    actions: {
        async fetchMessages() {
            this.messages = []
            this.loading = true
            try {
                this.messages = await fetch('/messages')
                    .then((response) => response.json())
            } catch (error) {
                this.error = error
            } finally {
                this.loading = false
            }
        },
        async addTodo(text, user) {
            const newMessage = {
                message: text,
                user: user,
                user_id: user.id,
                status: 'sending'
            };
            this.messages.push(newMessage);

            try {
                await axios.post('/send', form).then((response) => {
                    console.log(response.data)
                    this.messages.push(response.data)
                })
            } catch (error) {
                this.error = error
            } finally {
                this.loading = false
            }

        },
    }
})
