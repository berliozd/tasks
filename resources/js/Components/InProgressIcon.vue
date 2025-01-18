<script setup>
const emits = defineEmits(['click'])
const props = defineProps({inProgress: Boolean, enabled: Boolean})
</script>

<template>
    <div class="tooltip-left" :class="enabled?'tooltip':''" :data-tip="inProgress? 'In Progress':'Start progress!'"
         @click="enabled?emits('click'):''">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-circle-fading-arrow-up cursor-pointer"
             :class="inProgress ?  enabled?'text-gray-800':'text-gray-400' : enabled?'text-gray-300':'text-gray-400'">
            <path d="M12 2a10 10 0 0 1 7.38 16.75"/>
            <path d="m16 12-4-4-4 4"/>
            <path d="M12 16V8"/>
            <path d="M2.5 8.875a10 10 0 0 0-.5 3"/>
            <path d="M2.83 16a10 10 0 0 0 2.43 3.4"/>
            <path d="M4.636 5.235a10 10 0 0 1 .891-.857"/>
            <path d="M8.644 21.42a10 10 0 0 0 7.631-.38"/>
        </svg>
    </div>
</template>
