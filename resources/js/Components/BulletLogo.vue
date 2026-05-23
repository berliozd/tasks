<script setup>
const props = defineProps({
    // Tailwind "size-*" scale. Kept as a string so you can pass "12", "16", etc.
    // We map to known classes (instead of building class names dynamically) so Tailwind
    // doesn't purge them in production.
    size: { type: String, default: "10" },
})

const sizeClasses = {
    "8": "size-8",
    "10": "size-10",
    "12": "size-12",
    "14": "size-14",
    "16": "size-16",
    "20": "size-20",
}

const boxSizeClass = sizeClasses[props.size] ?? sizeClasses["12"]
</script>
<template>
    <div
        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-navy sm:size-16"
    >
        <img src="/img/tasks-nobg.png" class="object-contain" :class="boxSizeClass" alt="Tasks">
    </div>
</template>
