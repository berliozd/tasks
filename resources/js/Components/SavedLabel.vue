<script setup>
import {useStore} from "@/Composables/store.js";
</script>
<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 -translate-y-1"
        leave-active-class="transition ease-in duration-150"
        leave-to-class="opacity-0 -translate-y-1"
        v-if="useStore().saved"
    >
        <div class="flex justify-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-accent/10 px-3 py-1 text-xs font-semibold text-brand-accent-dark ring-1 ring-brand-accent/20">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
                {{ useStore().savedText }}
            </span>
        </div>
    </Transition>
</template>
