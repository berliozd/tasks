<script setup>
defineProps({
    flags: {type: Array, default: () => []},
    // In templates, pass as `size-class="w-5 h-5"` (kebab-case maps to `sizeClass`).
    sizeClass: {type: String, default: 'w-4 h-4'},
    gapClass: {type: String, default: 'gap-1'},
    borderClass: {type: String, default: 'border border-gray-700'},
});
</script>

<template>
    <div class="flex" :class="gapClass" v-if="flags && flags.length">
        <div v-for="flag in flags"
             :key="flag.id"
             class="shrink-0"
             :class="[sizeClass, borderClass]"
             :style="{ 'background-color': flag.color }"
             :title="flag.name"/>
    </div>
</template>
