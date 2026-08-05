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
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Flags</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="surface-card mb-6">
                <div class="p-4 flex flex-col gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" v-model="newFlagLabel" placeholder="New flag label"
                               class="w-full sm:flex-1 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                               @keydown.enter="addFlag">
                        <input type="color" v-model="selectedColor"
                               class="h-10 w-10 cursor-pointer rounded-lg border border-gray-300 p-0.5 bg-white">
                        <SaveButton @click="addFlag"/>
                    </div>
                    <div v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</div>
                </div>
            </div>

        </div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="surface-card overflow-hidden">
                <div v-if="!reactiveFlags.length" class="p-8 text-center text-sm text-gray-400">
                    No flags yet. Add one above to start organizing your tasks.
                </div>
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="flag in reactiveFlags" :key="flag.id"
                         class="flex items-center gap-3 px-4 py-3">
                        <span class="shrink-0 inline-block w-4 h-4 rounded-full ring-1 ring-black/10"
                              :style="{ backgroundColor: flag.color }"/>
                        <input type="text" v-model="flag.name"
                               class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                               maxlength="255">
                        <input type="color" v-model="flag.color"
                               class="h-10 w-10 shrink-0 cursor-pointer rounded-lg border border-gray-300 p-0.5 bg-white">
                        <div class="w-16 shrink-0 text-[11px] leading-3 text-center">
                            <span v-if="savingById[flag.id]" class="text-gray-400">Saving…</span>
                            <span v-else-if="savedById[flag.id]" class="text-brand-accent-dark font-medium">Saved</span>
                        </div>
                        <div class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                            <DeleteModal @deleted="deleteFlag(flag)"
                                         label="Are you sure you want to delete this flag?"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
