<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {ref} from "vue";
import DeleteButton from "@/Components/DeleteButton.vue";

const props = defineProps({label: String});
const emits = defineEmits(['deleted']);
const isShowModal = ref(false)
const showModal = () => {
    isShowModal.value = true
}

const hideModal = () => {
    isShowModal.value = false
}
const initDelete = () => {
    hideModal()
    emits('deleted')
}
</script>

<template>
    <DeleteButton @click="showModal"></DeleteButton>
    <Modal :show="isShowModal">
        <div class="p-4 w-full space-y-4 flex flex-col">
            {{label}}
            <div class="flex justify-end gap-2">
                <SecondaryButton @click="hideModal">Cancel</SecondaryButton>
                <PrimaryButton @click="initDelete">Delete</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
