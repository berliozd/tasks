<script setup>
import {computed, nextTick, onBeforeUnmount, onMounted, ref, watch} from 'vue'
import FlagSwatches from '@/Components/FlagSwatches.vue'

const props = defineProps({
    allFlags: {type: Array, default: () => []},
    modelValue: {type: Array, default: () => []}, // array of flag ids
    label: {type: String, default: 'Flags'},
    buttonWidthClass: {type: String, default: 'w-32'},
    disabled: {type: Boolean, default: false},
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const rootEl = ref(null)
const panelStyle = ref({})

const ids = computed(() => new Set(props.modelValue ?? []))
const selectedFlags = computed(() => (props.allFlags ?? []).filter(f => ids.value.has(f.id)))

const toggle = () => {
    if (props.disabled) return
    open.value = !open.value
}

const close = () => {
    open.value = false
}

const setValue = (next) => emit('update:modelValue', next)

const toggleId = (flagId) => {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    const idx = current.indexOf(flagId)
    if (idx >= 0) current.splice(idx, 1)
    else current.push(flagId)
    setValue(current)
}

const clear = () => setValue([])

const onDocPointerDown = (e) => {
    if (!open.value) return
    const el = rootEl.value
    if (!el) return
    if (el === e.target || el.contains(e.target)) return
    close()
}

const onDocKeydown = (e) => {
    if (e.key === 'Escape') close()
}

const updatePanelPosition = async () => {
    await nextTick()
    const root = rootEl.value
    if (!root) return
    const btn = root.querySelector('button')
    if (!btn) return

    const r = btn.getBoundingClientRect()
    const margin = 12
    const preferredHeight = 240
    const maxWidth = Math.min(288, window.innerWidth - margin * 2) // 18rem = 288px
    const left = Math.min(
        window.innerWidth - margin - maxWidth,
        Math.max(margin, r.right - maxWidth)
    )

    const spaceBelow = window.innerHeight - r.bottom - margin
    const spaceAbove = r.top - margin
    // Flip above the button when there isn't enough room below but there's more room above.
    const openAbove = spaceBelow < preferredHeight && spaceAbove > spaceBelow

    if (openAbove) {
        const bottom = Math.max(margin, window.innerHeight - r.top + 8)
        panelStyle.value = {
            left: `${left}px`,
            bottom: `${bottom}px`,
            width: `${maxWidth}px`,
            maxHeight: `${Math.max(160, spaceAbove)}px`,
        }
    } else {
        const top = Math.min(window.innerHeight - margin, r.bottom + 8)
        panelStyle.value = {
            left: `${left}px`,
            top: `${top}px`,
            width: `${maxWidth}px`,
            maxHeight: `${Math.max(160, window.innerHeight - top - margin)}px`,
        }
    }
}

watch(open, (v) => {
    if (v) updatePanelPosition()
})

onMounted(() => {
    document.addEventListener('pointerdown', onDocPointerDown)
    document.addEventListener('keydown', onDocKeydown)
    window.addEventListener('resize', updatePanelPosition)
})

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocPointerDown)
    document.removeEventListener('keydown', onDocKeydown)
    window.removeEventListener('resize', updatePanelPosition)
})
</script>

<template>
    <div class="relative" ref="rootEl">
        <button type="button"
                class="h-10 btn btn-sm rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 normal-case justify-between"
                :class="buttonWidthClass"
                :disabled="disabled || !(allFlags && allFlags.length)"
                @click="toggle"
                :aria-expanded="open ? 'true' : 'false'">
            <span class="truncate">{{ label }}</span>
            <span class="inline-flex items-center gap-1">
                <span class="inline-flex items-center justify-center rounded-full bg-brand-accent/10 text-brand-accent-dark text-xs font-semibold tabular-nums min-w-6 h-6 px-2"
                      :class="(modelValue?.length ?? 0) ? '' : 'opacity-0'">
                    {{ modelValue?.length ?? 0 }}
                </span>
                <svg class="ms-1 size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                </svg>
            </span>
        </button>

        <div v-if="open"
             class="fixed z-50 rounded-xl bg-white ring-1 ring-slate-900/[0.06] shadow-card-hover overflow-hidden"
             :style="panelStyle">
            <div class="flex flex-col" :style="{ maxHeight: panelStyle.maxHeight }">
                <div class="sticky top-0 bg-white flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-100">
                    <div class="text-xs text-gray-500 truncate">Select flags</div>
                    <button type="button" class="btn btn-ghost btn-xs" :disabled="!(modelValue?.length ?? 0)" @click="clear">
                        Clear
                    </button>
                </div>

                <div class="flex-1 overflow-auto py-2">
                    <label v-for="flag in allFlags" :key="flag.id"
                           class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-brand-surface cursor-pointer">
                        <input type="checkbox"
                               class="checkbox checkbox-sm checkbox-primary"
                               :checked="ids.has(flag.id)"
                               @change="toggleId(flag.id)"/>
                        <span class="inline-block w-3 h-3 rounded-full ring-1 ring-black/10"
                              :style="{ backgroundColor: flag.color }"/>
                        <span class="text-sm text-gray-800 truncate">{{ flag.name }}</span>
                    </label>
                </div>

                <div v-if="selectedFlags.length" class="sticky bottom-0 bg-white border-t border-gray-100 px-3 py-2">
                    <FlagSwatches :flags="selectedFlags" size-class="w-4 h-4" gap-class="gap-1"/>
                </div>
            </div>
        </div>
    </div>
</template>
