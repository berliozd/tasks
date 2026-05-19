<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {ref, watch} from "vue";
import SaveButton from "@/Components/SaveButton.vue";
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import debounce from "lodash/debounce";

const reactiveFlags = ref([]);
const newFlagLabel = ref('');
const selectedColor = ref('#FFFFFF');
const errorMsg = ref('');
let storedFlags = null;
let watchActive = false;
const savingById = ref({});
const savedById = ref({});
const savedTimersById = ref({});

const refreshFlags = () => {
    axios.get(route('flags.index'))
        .then(response => {
            reactiveFlags.value = response.data;
            storedFlags = JSON.parse(JSON.stringify(reactiveFlags.value));
            watchActive = true;
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

const cleanFlag = (flag) => {
    // Avoid comparing transient UI fields.
    const {id, name, color} = flag;
    return {id, name, color};
}

const updateFlag = (flag) => {
    savingById.value[flag.id] = true;
    axios.patch(route('flags.update', flag.id), {name: flag.name, color: flag.color}).then(() => {
        storedFlags = JSON.parse(JSON.stringify(reactiveFlags.value));
        savingById.value[flag.id] = false;

        savedById.value[flag.id] = true;
        if (savedTimersById.value[flag.id]) {
            clearTimeout(savedTimersById.value[flag.id]);
        }
        savedTimersById.value[flag.id] = setTimeout(() => {
            savedById.value[flag.id] = false;
            savedTimersById.value[flag.id] = null;
        }, 1500);
    }).catch(() => {
        savingById.value[flag.id] = false;
    });
}

const debouncedUpdateFlag = debounce(updateFlag, 250);

watch(reactiveFlags, () => {
    if (!watchActive) return;
    for (const flag of (reactiveFlags.value ?? [])) {
        const stored = (storedFlags ?? []).find(f => f.id === flag.id);
        if (!stored) continue;
        if (JSON.stringify(cleanFlag(stored)) !== JSON.stringify(cleanFlag(flag))) {
            debouncedUpdateFlag(flag);
        }
    }
}, {deep: true});

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
                     class="flex justify-between align-center gap-2 my-2 mx-4">
                    <div class="border border-gray-400 bg-gray-100 p-3 w-full">
                        <div class="grid grid-cols-[1fr_auto] gap-2 items-center">
                            <input type="text" v-model="flag.name"
                                   class="h-10 px-2 rounded w-full border border-gray-300"
                                   maxlength="255">
                            <div class="flex items-center gap-2">
                                <input type="color" v-model="flag.color"
                                       class="h-10 w-10 cursor-pointer rounded border border-gray-300 p-0 bg-transparent">
                                <DeleteModal @deleted="deleteFlag(flag)"
                                             label="Are you sure you want to delete this flag?"/>
                            </div>
                            <div class="col-span-2 h-3 text-[11px] leading-3">
                                <span v-if="savingById[flag.id]" class="text-gray-400">Saving...</span>
                                <span v-else-if="savedById[flag.id]" class="text-emerald-600">Saved</span>
                                <span v-else class="invisible">Saved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
