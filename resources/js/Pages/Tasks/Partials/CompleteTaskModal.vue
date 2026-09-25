<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CheckButton from "@/Components/CheckButton.vue";
import {ref, watch} from "vue";
import axios from "axios";

const props = defineProps({task: Object});
const emits = defineEmits(['changed']);

const isShowModal = ref(false)
const checked = ref(props.task.completed_at !== null)

watch(
    () => props.task.completed_at,
    (completedAt) => {
        checked.value = completedAt !== null;
    }
);

const hideModal = () => {
    isShowModal.value = false
}

// Completing a non-recurring task opens a modal to ask about a follow-up
// task — the task is only actually marked complete once the user answers
// (any button), not just from clicking the checkbox.
const toggleChecked = async () => {
    if (checked.value) {
        checked.value = false;
        await updateTask()
        emits('changed')
        return;
    }
    if (!props.task.recurrence_id) {
        isShowModal.value = true;
        return;
    }
    checked.value = true;
    await updateTask()
    emits('changed')
}

const getDate = (nbDays) => {
    const now = new Date();
    now.setUTCDate(now.getUTCDate() + nbDays);
    return now;
}

const updateTask = async () => {
    await axios.patch(route('tasks.update', props.task.id), {completed_at: checked.value ? new Date() : null})
}

const complete = async () => {
    checked.value = true;
    await updateTask()
    emits('changed')
}

const cancel = () => {
    hideModal()
}

const confirmNoFollowUp = async () => {
    await complete()
    hideModal()
}

const createTasks = async (nbDays) => {
    await complete()
    await axios.post(
        route('tasks.store'),
        {label: props.task.label, description: props.task.description, scheduled_at: getDate(nbDays)}
    )
    hideModal()
}
</script>

<template>
    <CheckButton :checked="checked"
                 @click="toggleChecked()"/>
    <Modal :show="isShowModal" @close="cancel">
        <div class="p-4 w-full space-y-4 flex flex-col">
            <div>Mark "{{ task.label }}" as completed?</div>
            <div class="flex justify-between gap-2 md:gap-0 flex-col md:flex-row">
                <div class="w-full text-center">
                    <SecondaryButton @click="cancel">No</SecondaryButton>
                </div>
                <div class="w-full text-center">
                    <PrimaryButton @click="confirmNoFollowUp">Yes</PrimaryButton>
                </div>
            </div>
            <div class="text-sm text-gray-500">Or complete it and schedule a follow-up task:</div>
            <div class="flex justify-between gap-2 md:gap-0 flex-col md:flex-row">
                <div class="w-full text-center">
                    <SecondaryButton @click="createTasks(1)">Tomorrow</SecondaryButton>
                </div>
                <div class="w-full text-center">
                    <SecondaryButton @click="createTasks(7)">Next week</SecondaryButton>
                </div>
                <div class="w-full text-center">
                    <SecondaryButton @click="createTasks(30)">Next month</SecondaryButton>
                </div>
                <div class="w-full text-center">
                    <SecondaryButton @click="createTasks(365)">Next year</SecondaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
