<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {ref} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";

const reactiveFlags = ref([]);
const newFlagLabel = ref('');
const selectedColor = ref('#FFFFFF');
const errorMsg = ref('');

const refreshFlags = () => {
    axios.get(route('flags.index'))
        .then(response => {
            reactiveFlags.value = response.data;
        });
}

const addFlag = async () => {
    if (!validateInput()) return
    await axios.post(route('flags.store'), {name: newFlagLabel.value, color: selectedColor.value})
        .then(() => {
            newFlagLabel.value = ''
            refreshFlags();
        })
        .catch((error) => {
            errorMsg.value = error.response.data.message;
        })
}

const validateInput = () => {
    errorMsg.value = '';
    if (newFlagLabel.value === '') {
        errorMsg.value = 'Flag label is required';
        return false;
    }
    return true;
}

const deleteFlag = (flag) => {
    axios.delete(route('flags.delete', flag.id)).then(() => {
        refreshFlags()
    })
}

refreshFlags();
</script>

<template>
    <AppLayout title="Flags">

        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Flags</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="overflow-hidden shadow-lg sm:rounded-lg bg-gray-200 mb-6 mt-8">
                <div class="border border-gray-400 m-4 p-2 flex  flex-col">
                    <div class="flex justify-between align-center items-center gap-2">
                        <input type="text" v-model="newFlagLabel" placeholder="New flag label"
                               class="w-full rounded" @keydown.enter="addFlag">
                        <input type="color" v-model="selectedColor" class="cursor-pointer">
                        <SaveButton @click="addFlag"/>
                    </div>
                    <div>
                        <span class="text-red-600" v-if="errorMsg">{{ errorMsg }}</span>
                    </div>
                </div>
            </div>

        </div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-lg sm:rounded-lg bg-gray-200 mb-2">
                <div v-for="flag in reactiveFlags"
                     class="flex justify-between align-center gap-2 m-4">
                    <div class="border border-gray-400 bg-gray-100 p-2 flex justify-between w-full items-center">
                        <div class=" p-2 w-full">
                            <input type="text" :value="flag.name" class="p-1 rounded w-full mr-2" maxlength="255">
                        </div>
                        <div class=" flex justify-end">
                            <input type="color" :value="flag.color" class="cursor-pointer">
                            <DeleteModal @deleted="deleteFlag(flag)"
                                         label="Are you sure you want to delete this flag?"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
