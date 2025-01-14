<script setup>
import {useStore} from "@/Composables/store.js";
</script>
<template>
    <Transition
        enter-active-class="transition ease-in-out"
        enter-from-class="opacity-0"
        leave-active-class="transition ease-in-out"
        leave-to-class="opacity-0"
        v-if="useStore().saved"
    >
        <div class="flex justify-center font-bold text-gray-600">
            {{ useStore().savedText }}
        </div>
    </Transition>
</template>
