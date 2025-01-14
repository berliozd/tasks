import {defineStore} from 'pinia'

export const useStore = defineStore('store', {
    state: () => {
        return {
            saved: false,
            savedText: null,
        }
    },
    actions: {
        setSaved(text, delayBeforeHiding = 2000) {
            this.saved = true
            this.savedText = text
            setTimeout(() => {
                this.saved = false
                this.savedText = null
            }, delayBeforeHiding);
        }
    }
})
