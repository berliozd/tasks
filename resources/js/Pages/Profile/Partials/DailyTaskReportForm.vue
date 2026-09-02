<script setup>
import {ref} from 'vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: Object,
});

const enabled = ref(props.user.daily_report_enabled ?? false);
const hour = ref(props.user.daily_report_hour ?? 8);
const saving = ref(false);
const saved = ref(false);
const errorMsg = ref('');
let savedTimer = null;

const hourLabel = (h) => String(h).padStart(2, '0') + ':00';

const updateSettings = () => {
    saving.value = true;
    saved.value = false;
    errorMsg.value = '';
    axios.patch(route('daily-report-settings.update'), {
        daily_report_enabled: enabled.value,
        daily_report_hour: hour.value,
    }).then(() => {
        saved.value = true;
        if (savedTimer) clearTimeout(savedTimer);
        savedTimer = setTimeout(() => saved.value = false, 2000);
    }).catch((error) => {
        errorMsg.value = error.response?.data?.message ?? 'Could not save settings';
    }).finally(() => saving.value = false);
};
</script>

<template>
    <FormSection @submitted="updateSettings">
        <template #title>
            Daily task report
        </template>

        <template #description>
            Get an email each day listing the tasks you completed and what's scheduled for tomorrow.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="enabled"
                           class="rounded border-gray-300 text-brand-accent focus:ring-brand-accent transition">
                    Send me the daily task report
                </label>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="daily_report_hour" value="Send at"/>
                <select id="daily_report_hour" v-model.number="hour" :disabled="!enabled"
                        class="mt-1 h-10 rounded-lg shadow-sm w-full sm:w-40 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition disabled:opacity-50">
                    <option v-for="h in 24" :key="h - 1" :value="h - 1">{{ hourLabel(h - 1) }}</option>
                </select>
            </div>

            <div v-if="errorMsg" class="col-span-6 sm:col-span-4 text-sm text-red-600">{{ errorMsg }}</div>
        </template>

        <template #actions>
            <span v-if="saved" class="me-3 text-sm text-gray-600">Saved.</span>

            <PrimaryButton :class="{ 'opacity-25': saving }" :disabled="saving">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
