<script setup>
const props = defineProps({
    checked: {
        type: Boolean,
        default: false,
    },
})
const emit = defineEmits(['click']);
</script>

<template>
    <svg v-if="props.checked" @click="emit('click')" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
         viewBox="0 0 24 24" fill="none"
         stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
         class="lucide lucide-square-check cursor-pointer">
        <rect width="18" height="18" x="3" y="3" rx="2"/>
        <path d="m9 12 2 2 4-4"/>
    </svg>
    <svg v-if="!props.checked" @click="emit('click')" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
         viewBox="0 0 24 24" fill="none"
         stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square cursor-pointer">
        <rect width="18" height="18" x="3" y="3" rx="2"/>
    </svg>
</template>
