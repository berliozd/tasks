<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SaveButton from "@/Components/SaveButton.vue";
import axios from 'axios';
import {computed, reactive, ref, watch} from "vue";
import debounce from "lodash/debounce";
import {format} from "date-fns";
import {usePage} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";
import SavedLabel from "@/Components/SavedLabel.vue";
import DebuggingTasks from "@/Pages/Tasks/Partials/DebuggingTasks.vue";
import Task from "@/Pages/Tasks/Partials/Task.vue";
import Flags from "@/Pages/Tasks/Partials/Flags.vue";
import FlagMultiSelect from "@/Components/FlagMultiSelect.vue";

const newTaskLabel = ref('');
const newTaskRecurrenceId = ref(null);
const newTaskFlagIds = ref([]);
const props = defineProps({todayTasks: Array, lateTasks: Array, completedTodayTasks: Array});
const lastSaved = ref(new Date());
const reactiveTasks = reactive({});
const belowList = ref(null);
const progress = ref(null);
let storedReactiveTasks = null;
let watchActive = false;

const selectedFlagIds = ref([]);

const scrollTo = (view) => {
    view.value?.scrollIntoView({behavior: 'smooth'})
}

const updateTask = (task) => {
    axios.patch(route('tasks.update', task.id), task).then(
        (response) => {
            // Keep local state in sync so "updated at" refreshes without reloading.
            if (response?.data) {
                task.updated_at = response.data.updated_at;
                task.scheduled_at = response.data.scheduled_at;
                task.completed_at = response.data.completed_at;
                task.recurrence_id = response.data.recurrence_id;
                task.label = response.data.label;
                task.description = response.data.description;
            }
            useStore().setSaved('Saved!');
            lastSaved.value = new Date();
        }
    )
}
const debouncedSave = debounce(updateTask, 300);

const saveReactiveTasks = () => {
    reactiveTasks.value.forEach(task => {
        if (task.id === null || !task.id) return;
        let storedTask = storedReactiveTasks.find(storedTask => storedTask.id === task.id);
        if (JSON.stringify(cleanTask(storedTask)) !== JSON.stringify(cleanTask(task))) {
            debouncedSave(task)
        }
    });
    storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
    calculateProgress()
}

const removeProperty = (obj, key) => {
    const {[key]: _, ...newObj} = obj;
    return newObj;
}
const cleanTask = (storedTask) => {
    storedTask = JSON.parse(JSON.stringify(storedTask));
    return removeProperty(storedTask, 'editing');
}

const addTask = () => {
    if (newTaskLabel.value === '') return;
    watchActive = false;
    reactiveTasks.value.push({
        label: newTaskLabel.value,
        'completed_at': null,
        recurrence_id: newTaskRecurrenceId.value,
        flags: (allFlags.value ?? []).filter(f => (newTaskFlagIds.value ?? []).includes(f.id)),
    });
    axios.post(route('tasks.store'), {
        label: newTaskLabel.value,
        recurrence_id: newTaskRecurrenceId.value,
        flag_ids: newTaskFlagIds.value,
    })
        .then((response) => {
                reactiveTasks.value[reactiveTasks.value.length - 1] = response.data;
            }
        ).then(() => {
            newTaskLabel.value = '';
            newTaskRecurrenceId.value = null;
            newTaskFlagIds.value = [];
            watchActive = true;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
            calculateProgress()
            scrollTo(belowList)
        }
    )
}

const refreshTasks = () => {
    axios.get(route('tasks.index'))
        .then(response => {
            reactiveTasks.value = response.data;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
        }).then(() => {
        watchActive = true
        calculateProgress()
    })
}
refreshTasks();

watch(reactiveTasks, () => {
    if (watchActive) saveReactiveTasks();
})

const calculateProgress = () => {
    let completedTasks = 0;
    let unCompletedTasks = 0;
    reactiveTasks.value.forEach(task => {
        if (task.completed_at === null) {
            unCompletedTasks++;
        } else {
            completedTasks++;
        }
    });
    progress.value = Math.round((completedTasks / (completedTasks + unCompletedTasks)) * 100);
}


const dispatchEvent = () => {
    console.log('dispatching event');
    axios.get(route('event.index'));

}

const allFlags = ref([]);
const getAllFlags = () => {
    axios.get(route('flags.index'))
        .then(response => {
            allFlags.value = response.data;
        });
}
getAllFlags();

const pageFlags = computed(() => {
    const flagsById = new Set();
    const tasks = reactiveTasks.value ?? [];
    tasks.forEach(task => {
        (task.flags ?? []).forEach(flag => flagsById.add(flag.id));
    });
    return (allFlags.value ?? []).filter(flag => flagsById.has(flag.id));
});

const allRecurrences = ref([]);
const getAllRecurrences = () => {
    axios.get(route('recurrences.index'))
        .then(response => {
            allRecurrences.value = response.data;
        });
}
getAllRecurrences();

const filteredTasks = computed(() => {
    const tasks = reactiveTasks.value ?? [];
    if (!selectedFlagIds.value.length) return tasks;
    return tasks.filter(task => {
        if (!task.flags || !task.flags.length) return false;
        // Match ANY selected flag.
        return task.flags.some(flag => selectedFlagIds.value.includes(flag.id));
    });
});

const updateSelectedFlags = (e) => {
    selectedFlagIds.value = e.value;
};

</script>

<template>
    <AppLayout title="Tasks">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Tasks</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="min-h-6 ">
                <SavedLabel/>
                <button @click="dispatchEvent" class="rounded bg-gray-500 m-2 hidden">DISP</button>
            </div>
            <div class="text-xs text-gray-400 flex justify-end pr-2">
                Last saved on {{
                    format(
                        lastSaved,
                        usePage().props.appLocale === 'en' ? 'MM/dd/yyyy HH:mm:ss' : 'dd/MM/yyyy HH:mm:ss'
                    )
                }}
            </div>
            <div class="shadow-sm sm:rounded-lg bg-white ring-1 ring-gray-200 mb-6 overflow-visible">
                <div class="p-4 flex flex-wrap justify-between items-center gap-2">
                    <input type="text" v-model="newTaskLabel" placeholder="New task label"
                           class="w-full md:flex-1 rounded-md border-gray-300 focus:border-gray-400 focus:ring-gray-400"
                           @keydown.enter="addTask">
                    <select v-model="newTaskRecurrenceId"
                            class="h-10 rounded-md border-gray-300 text-sm focus:border-gray-400 focus:ring-gray-400">
                        <option :value="null">No recurrence</option>
                        <option v-for="recurrence in allRecurrences" :key="recurrence.id" :value="recurrence.id">
                            {{ recurrence.label }}
                        </option>
                    </select>
                    <FlagMultiSelect v-model="newTaskFlagIds" :all-flags="allFlags"/>
                    <SaveButton @click="addTask"/>
                </div>
            </div>
            <div class="shadow-sm sm:rounded-lg bg-white ring-1 ring-gray-200 my-6 px-4"
                 v-if="!isNaN(progress) && progress > 0">
                <progress class="my-4 progress w-full" :value="progress" max="100"/>
            </div>

            <Flags :all-flags="pageFlags" @filter="updateSelectedFlags"/>

            <div class="shadow-sm sm:rounded-lg bg-white ring-1 ring-gray-200 mb-2 overflow-visible">
                <div class="divide-y divide-gray-100 sm:rounded-lg">
                    <template v-for="task in filteredTasks" :key="task.id">
                    <Task :task="task" @deleted="refreshTasks()" @changed="refreshTasks()" :all-flags="allFlags"
                          :all-recurrences="allRecurrences"/>
                    </template>
                </div>
            </div>
            <div class="min-h-6" ref="belowList">
                <SavedLabel/>
            </div>
            <DebuggingTasks :todayTasks="todayTasks" :lateTasks="lateTasks"
                            :completedTodayTasks="completedTodayTasks"
                            :class="usePage().props.environment === 'production'?'hidden':''"/>
        </div>
    </AppLayout>
</template>
