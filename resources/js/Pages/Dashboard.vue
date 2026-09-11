<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link, usePage} from '@inertiajs/vue3';
import {format} from 'date-fns';
import {ref} from 'vue';
import FlagSwatches from '@/Components/FlagSwatches.vue';
import {statusFlags} from '@/Composables/prospectActionStatus.js';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SavedLabel from '@/Components/SavedLabel.vue';
import {useStore} from '@/Composables/store.js';

const props = defineProps({
    todayTasks: {type: Array, default: () => []},
    lateTasks: {type: Array, default: () => []},
    completedTodayTasks: {type: Array, default: () => []},
    prospection: {
        type: Object,
        default: () => ({
            products_count: 0, directories_count: 0, prospects_count: 0, won_count: 0,
            status_counts: {}, top_products: [],
        }),
    },
    documents: {
        type: Object,
        default: () => ({count: 0, recent: []}),
    },
    needs: {
        type: Object,
        default: () => ({count: 0, recent: []}),
    },
});

const stageBgStyle = (color) => ({backgroundColor: `${color}1a`, color});

const page = usePage();
const isFeatureEnabled = (feature) => {
    const disabled = page.props.auth?.user?.current_team?.disabled_features ?? [];
    return !disabled.includes(feature);
};

const formatTime = (date) => date ? format(new Date(date), 'HH:mm') : '';
const formatRecentDate = (date) => date ? format(new Date(date), 'MMM d, HH:mm') : '';

const showFeatureRequestModal = ref(false);
const featureRequestMessage = ref('');
const sendingFeatureRequest = ref(false);
const featureRequestError = ref('');

const openFeatureRequestModal = () => {
    featureRequestMessage.value = '';
    featureRequestError.value = '';
    showFeatureRequestModal.value = true;
};

const closeFeatureRequestModal = () => showFeatureRequestModal.value = false;

const submitFeatureRequest = () => {
    const message = featureRequestMessage.value.trim();
    if (!message) return;
    sendingFeatureRequest.value = true;
    featureRequestError.value = '';
    axios.post(route('feature-requests.store'), {message}).then(() => {
        showFeatureRequestModal.value = false;
        useStore().setSaved('Request sent!');
    }).catch((error) => {
        featureRequestError.value = error.response?.data?.message ?? 'Could not send your request';
    }).finally(() => sendingFeatureRequest.value = false);
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl leading-tight text-slate-900">Dashboard</h2>
                <SavedLabel/>
                <button type="button" @click="openFeatureRequestModal"
                        class="ml-auto shrink-0 inline-flex items-center gap-2 h-10 px-4 rounded-full border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16v12H7l-3 3z"/>
                        <path d="M8 9h8M8 13h5"/>
                    </svg>
                    Submit a request to developer
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-6">

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <template v-if="isFeatureEnabled('tasks')">
                    <div class="surface-card p-4">
                        <div class="text-2xl font-semibold text-slate-900">{{ todayTasks.length }}</div>
                        <div class="text-xs text-gray-500">Tasks due today</div>
                    </div>
                    <div class="surface-card p-4">
                        <div class="text-2xl font-semibold" :class="lateTasks.length ? 'text-red-600' : 'text-slate-900'">
                            {{ lateTasks.length }}
                        </div>
                        <div class="text-xs text-gray-500">Overdue tasks</div>
                    </div>
                    <div class="surface-card p-4">
                        <div class="text-2xl font-semibold text-slate-900">{{ completedTodayTasks.length }}</div>
                        <div class="text-xs text-gray-500">Completed today</div>
                    </div>
                </template>
                <template v-if="isFeatureEnabled('prospection')">
                    <div class="surface-card p-4">
                        <div class="text-2xl font-semibold text-slate-900">{{ prospection.directories_count }}</div>
                        <div class="text-xs text-gray-500">Directories</div>
                    </div>
                    <div class="surface-card p-4">
                        <div class="text-2xl font-semibold text-slate-900">{{ prospection.prospects_count }}</div>
                        <div class="text-xs text-gray-500">Prospects</div>
                    </div>
                    <div class="surface-card p-4">
                        <div class="text-2xl font-semibold text-blue-700">{{ prospection.won_count }}</div>
                        <div class="text-xs text-gray-500">Won</div>
                    </div>
                </template>
                <div v-if="isFeatureEnabled('documents')" class="surface-card p-4">
                    <div class="text-2xl font-semibold text-slate-900">{{ documents.count }}</div>
                    <div class="text-xs text-gray-500">Documents</div>
                </div>
                <div v-if="isFeatureEnabled('needs')" class="surface-card p-4">
                    <div class="text-2xl font-semibold text-slate-900">{{ needs.count }}</div>
                    <div class="text-xs text-gray-500">Needs</div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

                <div v-if="isFeatureEnabled('tasks')" class="surface-card overflow-hidden">
                    <div class="p-4 flex items-center justify-between border-b border-gray-100">
                        <div class="text-sm font-medium text-gray-900">Tasks</div>
                        <Link :href="route('tasks')" class="text-xs font-medium text-brand-navy hover:underline">
                            View all →
                        </Link>
                    </div>

                    <div v-if="!lateTasks.length && !todayTasks.length" class="p-8 text-center text-sm text-gray-400">
                        Nothing due — you're all caught up.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="task in lateTasks" :key="'late-' + task.id"
                             class="flex items-center gap-3 px-4 py-3">
                            <span class="shrink-0 rounded-full bg-red-50 text-red-700 text-xs font-semibold px-2 py-1">
                                Overdue
                            </span>
                            <span class="text-sm text-gray-900 flex-1 min-w-0 truncate">{{ task.label }}</span>
                            <FlagSwatches :flags="task.flags" size-class="w-3 h-3"/>
                        </div>
                        <div v-for="task in todayTasks" :key="'today-' + task.id"
                             class="flex items-center gap-3 px-4 py-3">
                            <span class="shrink-0 text-xs text-gray-400 w-12 tabular-nums">
                                {{ formatTime(task.scheduled_at) }}
                            </span>
                            <span class="text-sm text-gray-900 flex-1 min-w-0 truncate">{{ task.label }}</span>
                            <FlagSwatches :flags="task.flags" size-class="w-3 h-3"/>
                        </div>
                    </div>
                </div>

                <div v-if="isFeatureEnabled('prospection')" class="surface-card overflow-hidden">
                    <div class="p-4 flex items-center justify-between border-b border-gray-100">
                        <div class="text-sm font-medium text-gray-900">Prospection</div>
                        <Link :href="route('products')" class="text-xs font-medium text-brand-navy hover:underline">
                            View products →
                        </Link>
                    </div>

                    <div class="p-4 flex flex-col gap-4">
                        <div v-if="!prospection.products_count" class="text-center text-sm text-gray-400 py-4">
                            No products yet.
                        </div>
                        <div v-else-if="!prospection.prospects_count" class="text-center text-sm text-gray-400 py-4">
                            No prospects yet.
                        </div>
                        <template v-else>
                            <div class="flex flex-wrap gap-1">
                                <span v-if="prospection.won_count"
                                      class="rounded-full text-xs font-semibold px-2 py-1 bg-blue-50 text-blue-700 border border-blue-600">
                                    {{ prospection.won_count }} won
                                </span>
                                <span v-for="flag in statusFlags(prospection.status_counts)" :key="flag.status"
                                      class="rounded-full text-xs font-semibold px-2 py-1" :class="flag.colorClass">
                                    {{ flag.label }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ prospection.prospects_count }} prospect{{ prospection.prospects_count === 1 ? '' : 's' }}
                                across {{ prospection.directories_count }}
                                director{{ prospection.directories_count === 1 ? 'y' : 'ies' }}
                                in {{ prospection.products_count }}
                                product{{ prospection.products_count === 1 ? '' : 's' }}
                            </div>

                            <div v-if="prospection.top_products?.length" class="flex flex-col divide-y divide-gray-100 -mx-4 -mb-4">
                                <Link v-for="product in prospection.top_products" :key="product.id"
                                      :href="route('products.view', product.id)"
                                      class="flex flex-col gap-1 px-4 py-2 hover:bg-brand-surface transition">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-gray-900 flex-1 min-w-0 truncate">{{ product.name }}</span>
                                        <span v-if="product.won_count"
                                              class="shrink-0 text-xs font-semibold text-blue-700">
                                            {{ product.won_count }} won
                                        </span>
                                        <span class="shrink-0 flex items-center gap-1 text-xs text-gray-400 tabular-nums">
                                            <span>{{ product.directories_count }} director{{ product.directories_count === 1 ? 'y' : 'ies' }}</span>
                                            <span class="text-gray-300">/</span>
                                            <span>{{ product.prospects_count }} prospect{{ product.prospects_count === 1 ? '' : 's' }}</span>
                                        </span>
                                    </div>
                                    <div v-if="statusFlags(product.action_status_counts).length" class="flex flex-wrap gap-1">
                                        <span v-for="flag in statusFlags(product.action_status_counts)" :key="flag.status"
                                              class="rounded-full text-[11px] font-medium px-2 py-0.5" :class="flag.colorClass">
                                            {{ flag.label }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </template>
                    </div>
                </div>

                <div v-if="isFeatureEnabled('documents')" class="surface-card overflow-hidden">
                    <div class="p-4 flex items-center justify-between border-b border-gray-100">
                        <div class="text-sm font-medium text-gray-900">Documents</div>
                        <Link :href="route('documents')" class="text-xs font-medium text-brand-navy hover:underline">
                            View all →
                        </Link>
                    </div>

                    <div v-if="!documents.recent.length" class="p-8 text-center text-sm text-gray-400">
                        No documents yet.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <Link v-for="doc in documents.recent" :key="doc.id"
                              :href="route('documents.view', doc.id)"
                              class="flex items-center gap-3 px-4 py-3 hover:bg-brand-surface transition">
                            <span class="text-sm text-gray-900 flex-1 min-w-0 truncate">{{ doc.title }}</span>
                            <span class="shrink-0 text-xs text-gray-400">{{ formatRecentDate(doc.updated_at) }}</span>
                        </Link>
                    </div>
                </div>

                <div v-if="isFeatureEnabled('needs')" class="surface-card overflow-hidden">
                    <div class="p-4 flex items-center justify-between border-b border-gray-100">
                        <div class="text-sm font-medium text-gray-900">Needs</div>
                        <Link :href="route('needs')" class="text-xs font-medium text-brand-navy hover:underline">
                            View board →
                        </Link>
                    </div>

                    <div v-if="!needs.recent.length" class="p-8 text-center text-sm text-gray-400">
                        No needs yet.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <Link v-for="need in needs.recent" :key="need.id" :href="route('needs')"
                              class="flex items-center gap-3 px-4 py-3 hover:bg-brand-surface transition">
                            <span class="text-sm text-gray-900 flex-1 min-w-0 truncate">{{ need.title }}</span>
                            <span class="shrink-0 rounded-full text-[11px] font-medium px-2 py-0.5"
                                  :style="stageBgStyle(need.stage.color)">
                                {{ need.stage.label }}
                            </span>
                        </Link>
                    </div>
                </div>

            </div>
        </div>

        <Modal :show="showFeatureRequestModal" @close="closeFeatureRequestModal">
            <div class="p-6 flex flex-col gap-4">
                <h3 class="text-lg font-medium text-gray-900">Submit a request to the developer</h3>
                <p class="text-sm text-gray-500">
                    Describe a feature, improvement, or issue — it'll be emailed straight to the developer.
                </p>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Your suggestion</label>
                    <textarea v-model="featureRequestMessage" rows="5" autofocus
                              placeholder="It would be great if…"
                              class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm"/>
                </div>
                <div v-if="featureRequestError" class="text-sm text-red-600">{{ featureRequestError }}</div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="closeFeatureRequestModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitFeatureRequest"
                                   :disabled="sendingFeatureRequest || !featureRequestMessage.trim()">
                        {{ sendingFeatureRequest ? 'Sending…' : 'Send' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
