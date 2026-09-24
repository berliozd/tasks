<script setup>
import {computed, ref} from 'vue';
import {Head, Link, usePage} from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Ad from '@/Components/Ad.vue';

defineProps({
    canLogin: {type: Boolean},
    canRegister: {type: Boolean},
});

const currency = ref('usd'); // 'usd' | 'eur'
const billing = ref('monthly'); // 'monthly' | 'annual'

const CURRENCY_SYMBOL = {usd: '$', eur: '€'};

const formatPrice = (amount) => currency.value === 'usd'
    ? `${CURRENCY_SYMBOL.usd}${amount}`
    : `${amount} ${CURRENCY_SYMBOL.eur}`;

const plans = [
    {
        key: 'free',
        name: 'Free',
        tagline: 'For individuals getting organized',
        priceMonthly: 0,
        priceAnnual: 0,
        cta: 'Get started free',
        features: [
            '1 user',
            'Tasks: schedule, flags, recurring tasks',
            'Daily email recap',
            'Community support',
        ],
    },
    {
        key: 'pro',
        name: 'Pro',
        tagline: 'Everything unlocked, and a say in what we build next',
        priceMonthly: 12,
        priceAnnual: 9,
        highlighted: true,
        cta: 'Get started',
        features: [
            'Everything in Free',
            'Unlimited team members',
            'Prospection: AI-generated directories, LinkedIn &amp; web search, outreach logging, real email sending',
            'Documents: Markdown knowledge base with auto-tagging',
            'Needs: configurable Kanban pipeline with activity log &amp; CSV export',
            'Per-team feature controls',
            'Your feature requests reviewed and built when they make the product better',
            'Email support',
        ],
    },
];

const priceFor = (plan) => billing.value === 'monthly' ? plan.priceMonthly : plan.priceAnnual;

const faqs = [
    {
        q: 'Do I need a credit card to try it?',
        a: 'No — the Free plan never asks for payment details. Create an account and start with Tasks right away.',
    },
    {
        q: 'What does "built together" actually mean?',
        a: 'Pro comes with a "Submit a request to developer" button right on your dashboard. Every suggestion is read and genuinely considered — the relevant ones get built and shipped. It\'s not a suggestion box that goes nowhere: this platform grows from what its paying users actually need.',
    },
    {
        q: 'Can a team turn off modules it doesn\'t use?',
        a: 'Yes. Team owners can enable or disable Tasks, Prospection, Documents, and Needs independently for their whole team at any time from Team Settings — useful if you only need part of the toolkit.',
    },
    {
        q: 'Can I change plans later?',
        a: 'Yes, you can move between Free and Pro at any time as your team\'s needs change. Pro is a flat price per team, however many members you have.',
    },
    {
        q: 'What\'s the difference between monthly and annual billing?',
        a: 'Annual billing is paid once a year at a reduced per-month rate — the total shown reflects that discount.',
    },
];
</script>

<template>
    <Head title="Pricing"/>
    <GuestLayout :show-home-link="false">
        <template #header-right>
            <div class="flex items-center gap-4">
                <Link href="/" class="text-sm font-medium text-slate-200 hover:text-white transition">
                    Home
                </Link>
                <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                      class="text-sm font-medium text-slate-200 hover:text-white transition">
                    Log in
                </Link>
                <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                      class="text-sm font-medium text-slate-200 hover:text-white transition">
                    Dashboard
                </Link>
            </div>
        </template>

        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -my-8 text-gray-600">

            <section class="w-full bg-gray-50">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">
                        Two plans. One platform we build together.
                    </h1>
                    <p class="mt-4 text-sm/relaxed">
                        Start free with Tasks. Go Pro to unlock Prospection, Documents, and Needs for your
                        whole team — and a direct line to shape what we build next.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                        <div class="inline-flex rounded-lg bg-white p-1 ring-1 ring-slate-900/[0.06] shadow-card">
                            <button type="button" @click="billing = 'monthly'"
                                    class="px-4 py-1.5 rounded-md text-sm font-medium transition"
                                    :class="billing === 'monthly' ? 'bg-brand-navy text-white' : 'text-gray-600 hover:text-gray-900'">
                                Monthly
                            </button>
                            <button type="button" @click="billing = 'annual'"
                                    class="px-4 py-1.5 rounded-md text-sm font-medium transition inline-flex items-center gap-1.5"
                                    :class="billing === 'annual' ? 'bg-brand-navy text-white' : 'text-gray-600 hover:text-gray-900'">
                                Annual
                                <span class="rounded-full text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5"
                                      :class="billing === 'annual' ? 'bg-white/20 text-white' : 'bg-brand-accent/10 text-brand-accent-dark'">
                                    Save ~20%
                                </span>
                            </button>
                        </div>

                        <div class="inline-flex rounded-lg bg-white p-1 ring-1 ring-slate-900/[0.06] shadow-card">
                            <button type="button" @click="currency = 'usd'"
                                    class="px-4 py-1.5 rounded-md text-sm font-medium transition"
                                    :class="currency === 'usd' ? 'bg-brand-navy text-white' : 'text-gray-600 hover:text-gray-900'">
                                USD
                            </button>
                            <button type="button" @click="currency = 'eur'"
                                    class="px-4 py-1.5 rounded-md text-sm font-medium transition"
                                    :class="currency === 'eur' ? 'bg-brand-navy text-white' : 'text-gray-600 hover:text-gray-900'">
                                EUR
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="w-full bg-white">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div v-for="plan in plans" :key="plan.key"
                             class="flex flex-col rounded-2xl p-8 ring-1 transition"
                             :class="plan.highlighted
                                ? 'bg-brand-navy text-white shadow-card-hover ring-brand-navy scale-[1.02]'
                                : 'bg-white ring-slate-900/[0.06] shadow-card hover:shadow-card-hover'">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="text-lg font-semibold" :class="plan.highlighted ? 'text-white' : 'text-black'">
                                    {{ plan.name }}
                                </h2>
                                <span v-if="plan.highlighted"
                                      class="rounded-full bg-white/15 text-white text-[10px] font-semibold uppercase tracking-wide px-2 py-1">
                                    Most popular
                                </span>
                            </div>
                            <p class="mt-2 text-sm/relaxed" :class="plan.highlighted ? 'text-slate-200' : 'text-gray-500'">
                                {{ plan.tagline }}
                            </p>

                            <div class="mt-6 flex items-baseline gap-1">
                                <span class="text-4xl font-bold" :class="plan.highlighted ? 'text-white' : 'text-black'">
                                    {{ formatPrice(priceFor(plan)) }}
                                </span>
                                <span class="text-sm" :class="plan.highlighted ? 'text-slate-300' : 'text-gray-500'">
                                    / team / month
                                </span>
                            </div>
                            <p v-if="billing === 'annual' && priceFor(plan) > 0"
                               class="mt-1 text-xs" :class="plan.highlighted ? 'text-slate-300' : 'text-gray-400'">
                                Billed annually
                            </p>

                            <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')"
                                  class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold shadow-card transition"
                                  :class="plan.highlighted
                                    ? 'bg-white text-brand-navy hover:bg-gray-100'
                                    : 'bg-brand-navy text-white hover:bg-brand-navy-light'">
                                {{ plan.cta }}
                            </Link>
                            <Link v-if="$page.props.auth.user" :href="plan.key === 'pro' ? route('billing') : route('dashboard')"
                                  class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold shadow-card transition"
                                  :class="plan.highlighted
                                    ? 'bg-white text-brand-navy hover:bg-gray-100'
                                    : 'bg-brand-navy text-white hover:bg-brand-navy-light'">
                                {{ plan.key === 'pro' ? 'Upgrade to Pro' : 'Go to dashboard' }}
                            </Link>

                            <ul class="mt-8 flex flex-col gap-3 text-sm/relaxed">
                                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2">
                                    <svg class="mt-0.5 size-4 shrink-0" :class="plan.highlighted ? 'text-white' : 'text-brand-accent-dark'"
                                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    <span :class="plan.highlighted ? 'text-slate-100' : 'text-gray-600'" v-html="feature"/>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="w-full bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="flex flex-col items-center text-center">
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-brand-navy">
                            <svg class="size-7 stroke-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M17.25 6.75L22.5 12l-5.25 5.25M6.75 17.25L1.5 12l5.25-5.25M14.25 4.5l-4.5 15"/>
                            </svg>
                        </div>
                        <h2 class="mt-4 text-xl font-semibold text-black">It's your platform too</h2>
                        <p class="mt-3 max-w-2xl text-sm/relaxed">
                            Pro isn't just a bigger feature list — it's a say in what gets built next. Every
                            Pro dashboard has a <span class="font-semibold text-black">"Submit a request to
                            developer"</span> button. Real suggestions get read, studied, and — when they
                            genuinely make the product better — built and shipped. Plenty of what's already
                            in this app started exactly that way. You're not just a customer; you're one of
                            the people shaping where this goes.
                        </p>
                    </div>
                </div>
            </section>

            <section class="w-full bg-gray-50">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <h2 class="text-xl font-semibold text-black text-center">Frequently asked questions</h2>
                    <div class="mt-8 flex flex-col divide-y divide-gray-200 rounded-xl bg-white shadow-card ring-1 ring-slate-900/[0.06]">
                        <div v-for="faq in faqs" :key="faq.q" class="p-5">
                            <div class="text-sm font-semibold text-black">{{ faq.q }}</div>
                            <p class="mt-1.5 text-sm/relaxed">{{ faq.a }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Ad/>

                <footer class="py-16 text-center text-sm text-gray-600">
                    {{ usePage().props.appName }} - @copyright 2026 - Addeos
                </footer>
            </div>
        </div>
    </GuestLayout>
</template>
