<script setup>
import {ref} from "vue";

const props = defineProps({
    title: String,
    defaultOpen: {type: Boolean, default: false},
    // Flush sections skip the padded body wrapper, for slot content (like
    // Partials/EmailTemplates.vue) that already lays out its own full-width
    // padding, dividers, etc. and would otherwise get double-padded.
    flush: {type: Boolean, default: false},
});

const open = ref(props.defaultOpen);
</script>

<template>
    <div class="surface-card overflow-hidden">
        <div class="flex items-center gap-2 p-4">
            <button type="button" @click="open = !open" class="min-w-0 flex-1 flex items-center justify-between text-left">
                <div class="text-sm font-medium text-gray-900">{{ title }}</div>
                <svg class="size-4 text-gray-400 transition-transform shrink-0" :class="open ? '' : '-rotate-90'"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <slot name="actions"/>
        </div>
        <div v-show="open" :class="flush ? 'border-t border-gray-100' : 'px-4 pb-4 flex flex-col gap-2'">
            <slot/>
        </div>
    </div>
</template>
