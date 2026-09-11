<script setup>
import {onMounted, ref} from 'vue';
import FormSection from '@/Components/FormSection.vue';
import ActionMessage from '@/Components/ActionMessage.vue';

const props = defineProps({
    permissions: Object,
});

const featureLabels = {
    tasks: 'Tasks',
    needs: 'Needs',
    prospection: 'Prospection',
    documents: 'Documents',
};

const features = ref([]);
const disabled = ref([]);
const saving = ref(false);
const saved = ref(false);
let savedTimer = null;

const load = () => axios.get(route('team-features.index')).then(response => {
    features.value = response.data.features;
    disabled.value = response.data.disabled;
});

onMounted(load);

const isEnabled = (feature) => !disabled.value.includes(feature);

const toggle = (feature) => {
    if (!props.permissions.canUpdateTeam || saving.value) return;
    const next = isEnabled(feature)
        ? [...disabled.value, feature]
        : disabled.value.filter(f => f !== feature);
    saving.value = true;
    axios.patch(route('team-features.update'), {disabled: next}).then(response => {
        disabled.value = response.data.disabled;
        saved.value = true;
        if (savedTimer) clearTimeout(savedTimer);
        savedTimer = setTimeout(() => saved.value = false, 1500);
    }).finally(() => saving.value = false);
};
</script>

<template>
    <FormSection>
        <template #title>
            Features
        </template>

        <template #description>
            Turn modules off for everyone on this team. A disabled module disappears from the
            navigation and can't be opened until it's turned back on.
        </template>

        <template #form>
            <div class="col-span-6 flex flex-col gap-4">
                <div v-for="feature in features" :key="feature" class="flex items-center justify-between gap-4">
                    <span class="text-sm text-gray-700">{{ featureLabels[feature] ?? feature }}</span>
                    <button type="button" @click="toggle(feature)"
                            :disabled="!permissions.canUpdateTeam || saving"
                            :aria-pressed="isEnabled(feature) ? 'true' : 'false'"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="isEnabled(feature) ? 'bg-brand-accent' : 'bg-gray-300'">
                        <span class="inline-block size-4 transform rounded-full bg-white transition"
                              :class="isEnabled(feature) ? 'translate-x-6' : 'translate-x-1'"/>
                    </button>
                </div>
            </div>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="saved">Saved.</ActionMessage>
        </template>
    </FormSection>
</template>
