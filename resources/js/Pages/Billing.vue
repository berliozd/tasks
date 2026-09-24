<script setup>
import {computed} from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {usePage} from '@inertiajs/vue3';

const props = defineProps({
    isPro: Boolean,
    onGracePeriod: Boolean,
    endsAt: {type: String, default: null},
    canManageBilling: Boolean,
});

const flashMessage = computed(() => usePage().props.flash?.message);

const formatDate = (date) => date
    ? new Date(date).toLocaleDateString(undefined, {year: 'numeric', month: 'long', day: 'numeric'})
    : '';
</script>

<template>
    <AppLayout title="Billing">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Billing</h2>
        </template>

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-4">
            <div v-if="flashMessage" class="rounded-xl bg-brand-accent/10 ring-1 ring-brand-accent/20 px-4 py-3 text-sm text-brand-accent-dark">
                {{ flashMessage }}
            </div>

            <div class="surface-card p-6 flex flex-col gap-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Current plan</div>
                        <div class="text-2xl font-bold text-slate-900 mt-1">{{ isPro ? 'Pro' : 'Free' }}</div>
                    </div>
                    <span class="rounded-full text-xs font-semibold uppercase tracking-wide px-3 py-1"
                          :class="isPro ? 'bg-brand-accent/10 text-brand-accent-dark' : 'bg-gray-100 text-gray-500'">
                        {{ isPro ? 'Active' : 'Free' }}
                    </span>
                </div>

                <p v-if="isPro && onGracePeriod" class="text-sm text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                    Your subscription is cancelled and will end on {{ formatDate(endsAt) }}. You'll keep Pro
                    access until then.
                </p>

                <template v-if="isPro">
                    <p class="text-sm text-gray-600">
                        Prospection, Documents, and Needs are unlocked for your whole team. Manage your
                        payment method, invoices, or cancel any time from the Stripe billing portal.
                    </p>
                    <a v-if="canManageBilling" :href="route('billing.portal')"
                       class="self-start inline-flex items-center gap-2 rounded-lg bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-card hover:bg-brand-navy-light transition">
                        Manage billing
                    </a>
                    <p v-else class="text-sm text-gray-400">Ask your team owner to manage billing.</p>
                </template>
                <template v-else>
                    <p class="text-sm text-gray-600">
                        You're on the Free plan — Tasks only. Upgrade to Pro to unlock Prospection, Documents,
                        and Needs for your whole team, and get a direct line to shape what we build next: every
                        Pro dashboard has a "Submit a request to developer" button, and real suggestions get
                        studied and built when they make the product better.
                    </p>
                    <a v-if="canManageBilling" :href="route('billing.checkout')"
                       class="self-start inline-flex items-center gap-2 rounded-lg bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-card hover:bg-brand-navy-light transition">
                        Upgrade to Pro
                    </a>
                    <p v-else class="text-sm text-gray-400">Ask your team owner to upgrade to Pro.</p>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
