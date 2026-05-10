import {onBeforeUnmount, onMounted} from 'vue'

export function useIdleRedirect(options?: {
    timeoutMs?: number
    to?: string
}) {
    const to = options?.to ?? '/'
    const timeoutMs = options?.timeoutMs

    let timer: number | undefined

    const schedule = () => {
        if (!timeoutMs) return
        if (timer) window.clearTimeout(timer)
        timer = window.setTimeout(() => window.location.replace(to), timeoutMs)
    }


    const onActivity = () => schedule()

    onMounted(() => {
        schedule()

        // Keep it cheap; these cover most real usage.
        window.addEventListener('mousemove', onActivity, {passive: true})
        window.addEventListener('mousedown', onActivity, {passive: true})
        window.addEventListener('keydown', onActivity)
        window.addEventListener('scroll', onActivity, {passive: true})
        window.addEventListener('touchstart', onActivity, {passive: true})
    })

    onBeforeUnmount(() => {
        if (timer) window.clearTimeout(timer)
        window.removeEventListener('mousemove', onActivity)
        window.removeEventListener('mousedown', onActivity)
        window.removeEventListener('keydown', onActivity)
        window.removeEventListener('scroll', onActivity)
        window.removeEventListener('touchstart', onActivity)
    })
}