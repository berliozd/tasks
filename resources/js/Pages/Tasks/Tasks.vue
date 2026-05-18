<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SaveButton from "@/Components/SaveButton.vue";
import axios from 'axios';
import {computed, onBeforeUnmount, onMounted, reactive, ref, watch} from "vue";
import debounce from "lodash/debounce";
import {format} from "date-fns";
import {usePage} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";
import SavedLabel from "@/Components/SavedLabel.vue";
import DebuggingTasks from "@/Pages/Tasks/Partials/DebuggingTasks.vue";
import Task from "@/Pages/Tasks/Partials/Task.vue";
import Flags from "@/Pages/Tasks/Partials/Flags.vue";
import FlagSwatches from "@/Components/FlagSwatches.vue";

const newTaskLabel = ref('');
const newTaskRecurrenceId = ref(null);
const newTaskFlagIds = ref([]);
const newTaskFlagsOpen = ref(false);
const newTaskFlagsEl = ref(null);
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
        () => {
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

const toggleNewTaskFlags = () => {
    newTaskFlagsOpen.value = !newTaskFlagsOpen.value;
}

const closeNewTaskFlags = () => {
    newTaskFlagsOpen.value = false;
}

const toggleNewTaskFlagId = (flagId) => {
    const idx = newTaskFlagIds.value.indexOf(flagId);
    if (idx >= 0) {
        newTaskFlagIds.value.splice(idx, 1);
    } else {
        newTaskFlagIds.value.push(flagId);
    }
}

const selectedNewTaskFlags = computed(() => {
    const ids = new Set(newTaskFlagIds.value ?? []);
    return (allFlags.value ?? []).filter(f => ids.has(f.id));
});

const onDocPointerDown = (e) => {
    if (!newTaskFlagsOpen.value) return;
    const el = newTaskFlagsEl.value;
    if (!el) return;
    if (el === e.target || el.contains(e.target)) return;
    closeNewTaskFlags();
}

const onDocKeydown = (e) => {
    if (e.key === 'Escape') closeNewTaskFlags();
}

onMounted(() => {
    document.addEventListener('pointerdown', onDocPointerDown);
    document.addEventListener('keydown', onDocKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocPointerDown);
    document.removeEventListener('keydown', onDocKeydown);
});

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
                    <div class="relative" ref="newTaskFlagsEl">
                        <button type="button"
                                class="h-10 w-32 btn btn-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 normal-case justify-between"
                                :disabled="!allFlags?.length"
                                @click="toggleNewTaskFlags"
                                :aria-expanded="newTaskFlagsOpen ? 'true' : 'false'">
                            <span class="truncate">Flags</span>
                            <span class="inline-flex items-center gap-1">
                                <span class="inline-flex items-center justify-center rounded-full bg-gray-100 text-gray-700 text-xs tabular-nums min-w-6 h-6 px-2"
                                      :class="newTaskFlagIds.length ? '' : 'opacity-0'">
                                    {{ newTaskFlagIds.length }}
                                </span>
                            <svg class="ms-1 size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                            </svg>
                            </span>
                        </button>

                        <div v-if="newTaskFlagsOpen"
                             class="absolute right-0 z-30 mt-2 w-[min(18rem,calc(100vw-2rem))] rounded-lg bg-white ring-1 ring-gray-200 shadow-lg overflow-hidden">
                            <div class="flex flex-col max-h-[60vh]">
                            <div class="sticky top-0 bg-white flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-100">
                                <div class="text-xs text-gray-500 truncate">
                                    Select flags
                                </div>
                                <button type="button"
                                        class="btn btn-ghost btn-xs"
                                        :disabled="!newTaskFlagIds.length"
                                        @click="newTaskFlagIds = []">
                                    Clear
                                </button>
                            </div>

                            <div class="flex-1 overflow-auto py-2">
                                <label v-for="flag in allFlags" :key="flag.id"
                                       class="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox"
                                           class="checkbox checkbox-sm"
                                           :checked="newTaskFlagIds.includes(flag.id)"
                                           @change="toggleNewTaskFlagId(flag.id)"/>
                                    <span class="inline-block w-3 h-3 border border-gray-700"
                                          :style="{ backgroundColor: flag.color }"/>
                                    <span class="text-sm text-gray-800 truncate">{{ flag.name }}</span>
                                </label>
                            </div>

                            <div v-if="selectedNewTaskFlags.length"
                                 class="sticky bottom-0 bg-white border-t border-gray-100 px-3 py-2">
                                <FlagSwatches :flags="selectedNewTaskFlags" size-class="w-4 h-4" gap-class="gap-1"/>
                            </div>
                            </div>
                        </div>
                    </div>
                    <SaveButton @click="addTask"/>
                </div>
            </div>
            <div class="shadow-sm sm:rounded-lg bg-white ring-1 ring-gray-200 my-6 px-4"
                 v-if="!isNaN(progress) && progress > 0">
                <progress class="my-4 progress w-full" :value="progress" max="100"/>
            </div>

            <Flags :all-flags="pageFlags" @filter="updateSelectedFlags"/>

            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white ring-1 ring-gray-200 mb-2">
                <div class="divide-y divide-gray-100">
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
