<script setup>
import {nextTick, onBeforeUnmount, onMounted, ref, watch} from 'vue';
import DeleteButton from '@/Components/DeleteButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({label: {type: String, default: 'Are you sure you want to delete this?'}});
const emit = defineEmits(['deleted']);

const open = ref(false);
const rootEl = ref(null);
const triggerEl = ref(null);
const panelStyle = ref({});

const toggle = () => {
    open.value = !open.value;
}
const close = () => {
    open.value = false;
}
const confirmDelete = () => {
    close();
    emit('deleted');
}

const updatePosition = async () => {
    await nextTick();
    const btn = triggerEl.value;
    if (!btn) return;

    const r = btn.getBoundingClientRect();
    const margin = 8;
    const width = 224;
    const left = Math.min(window.innerWidth - margin - width, Math.max(margin, r.right - width));
    const top = Math.min(window.innerHeight - margin, r.bottom + 6);

    panelStyle.value = {left: `${left}px`, top: `${top}px`, width: `${width}px`};
}

const onDocPointerDown = (e) => {
    if (!open.value) return;
    const el = rootEl.value;
    if (!el || el.contains(e.target)) return;
    close();
}
const onDocKeydown = (e) => {
    if (e.key === 'Escape') close();
}

watch(open, (v) => {
    if (v) updatePosition();
});

onMounted(() => {
    document.addEventListener('pointerdown', onDocPointerDown);
    document.addEventListener('keydown', onDocKeydown);
    window.addEventListener('resize', updatePosition);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocPointerDown);
    document.removeEventListener('keydown', onDocKeydown);
    window.removeEventListener('resize', updatePosition);
});
</script>

<template>
    <div class="inline-flex" ref="rootEl">
        <span ref="triggerEl" @click.stop="toggle"
              class="inline-flex w-7 h-7 items-center justify-center rounded-full hover:bg-red-50 transition">
            <DeleteButton/>
        </span>

        <div v-if="open" class="fixed z-50 rounded-xl bg-white ring-1 ring-slate-900/[0.06] shadow-card-hover p-3"
             :style="panelStyle" @click.stop>
            <div class="text-sm text-gray-700 mb-3">{{ label }}</div>
            <div class="flex justify-end gap-2">
                <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                <PrimaryButton type="button" @click="confirmDelete">Delete</PrimaryButton>
            </div>
        </div>
    </div>
</template>
