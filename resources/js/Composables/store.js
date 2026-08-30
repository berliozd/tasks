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
            // Which product/directory/prospect the current prospection page is
            // showing, so the persistent ProspectionLayout/tree (which no longer
            // remounts on every navigation) knows what to highlight/expand.
            activeProductId: null,
            activeDirectoryId: null,
            activeProspectId: null,
            // Breadcrumb trail for the current prospection page: an array of
            // {label, href} — href null marks the current/final crumb.
            // Rendered by the persistent ProspectionLayout itself (rather than
            // via a Teleport from the page), since the layout no longer
            // remounts on navigation between prospection pages.
            breadcrumb: [],
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
        },
        setProspectionActive({productId = null, directoryId = null, prospectId = null, breadcrumb = []} = {}) {
            this.activeProductId = productId
            this.activeDirectoryId = directoryId
            this.activeProspectId = prospectId
            this.breadcrumb = breadcrumb
        }
    }
})
