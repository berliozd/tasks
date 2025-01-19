<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CheckButton from "@/Components/CheckButton.vue";
import {ref} from "vue";
import axios from "axios";

const isShowModal = ref(false)
const props = defineProps({task: Object});

const hideModal = () => {
    isShowModal.value = false
}

const toggleCompleted = (task) => {
    if (task.completed_at === null) {
        task.completed_at = new Date();
        isShowModal.value = true
    } else {
        task.completed_at = null;
    }
}

const getDate = (nbDays) => {
    const now = new Date();
    now.setUTCDate(now.getUTCDate() + nbDays);
    return now;
}

const createTasks = async (nbDays) => {
    await axios.post(
        route('tasks.store'),
        {label: props.task.label, description: props.task.description, scheduled_at: getDate(nbDays)}
    )
    hideModal()
}
</script>

<template>
    <CheckButton :checked="task.completed_at !== null"
                 @click="toggleCompleted(task)" :enabled="!task.editing"/>
    <Modal :show="isShowModal">
        <div class="p-4 w-full space-y-4 flex flex-col">
            <div>Your task "{{ task.label }}" is marked as completed.</div>
            <div>Do you want to create a similar task?</div>
            <div class="flex justify-between gap-2 md:gap-0 flex-col md:flex-row">
                <div class="w-full text-center">
                    <SecondaryButton @click="hideModal">No</SecondaryButton>
                </div>
                <div class="w-full text-center">
                    <PrimaryButton @click="createTasks(1)">Tomorrow</PrimaryButton>
                </div>
                <div class="w-full text-center">
                    <PrimaryButton @click="createTasks(7)">Next week</PrimaryButton>
                </div>
                <div class="w-full text-center">
                    <PrimaryButton @click="createTasks(30)">Next month</PrimaryButton>
                </div>
                <div class="w-full text-center">
                    <PrimaryButton @click="createTasks(365)">Next year</PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
