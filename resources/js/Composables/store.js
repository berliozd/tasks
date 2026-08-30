import {defineStore} from 'pinia'

export const useStore = defineStore('store', {
    state: () => {
        return {
            saved: false,
            savedText: null,
            // Bumped whenever a product/directory/prospect is added or removed
            // elsewhere on the page, so the (separately-mounted) prospection
            // tree sidebar knows to refetch instead of going stale.
            prospectionTreeVersion: 0,
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
        },
        refreshProspectionTree() {
            this.prospectionTreeVersion++
        }
    }
})
