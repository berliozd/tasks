<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {ref} from "vue";

const props = defineProps({label: String});
const emits = defineEmits(['reschedule']);
const isShowModal = ref(false)
const showModal = () => {
    isShowModal.value = true
}

const hideModal = () => {
    isShowModal.value = false
}
const initReschedule = () => {
    hideModal()
    emits('reschedule')
}
</script>

<template>
    <div class="tooltip tooltip-left cursor-pointer" data-tip="Re-schedule" @click="showModal">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-redo-dot">
            <circle cx="12" cy="17" r="1"/>
            <path d="M21 7v6h-6"/>
            <path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/>
        </svg>
    </div>
    <Modal :show="isShowModal">
        <div class="p-4 w-full space-y-4 flex flex-col">
            Reschedule tomorrow?
            <div class="flex justify-end gap-2">
                <SecondaryButton @click="hideModal">Cancel</SecondaryButton>
                <PrimaryButton @click="initReschedule">Reschedule</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
