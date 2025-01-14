<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {ref} from "vue";
import DeleteButton from "@/Components/DeleteButton.vue";
import axios from "axios";

const props = defineProps({task: Object});
const emits = defineEmits(['deleted']);
const isShowModal = ref(false)
const showModal = () => {
    isShowModal.value = true
}

const hideModal = () => {
    isShowModal.value = false
}

const deleteTask = (task) => {
    axios.delete(route('tasks.delete', task.id)).then(() => {
        emits('deleted')
        hideModal()
    })
}
</script>

<template>
    <DeleteButton @click="showModal"></DeleteButton>
    <Modal :show="isShowModal">
        <div class="p-4 w-full space-y-4 flex flex-col">
            Are you sure you want to delete this task?
            <div class="flex justify-end gap-2">
                <SecondaryButton @click="hideModal">Cancel</SecondaryButton>
                <PrimaryButton @click="deleteTask(props.task)">Delete</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
