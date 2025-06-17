<script setup lang="js">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

// Define props to receive data from the controller
const props = defineProps({
    forms: Array,
    user: Object,
});

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-6xl p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                    <p class="mt-2 text-gray-600">Manage your forms and view analytics</p>
                </div>
                <!-- Forms Section -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Your Forms</h2>
                        <Button asChild>
                            <Link href="/form/new" class="gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create New Form
                            </Link>
                        </Button>
                    </div>

                    <div v-if="forms && forms.length > 0" class="space-y-4">
                        <div
                            v-for="form in forms"
                            :key="form.id"
                            class="group rounded-lg border border-gray-200 p-6 hover:border-gray-300 hover:shadow-sm transition-all duration-200"
                        >
                            <Link :href="`/form/${form.id}`" class="block">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary transition-colors">
                                            {{ form.title }}
                                        </h3>
                                        <p v-if="form.description" class="text-gray-600 mt-2 line-clamp-2">
                                            {{ form.description }}
                                        </p>
                                        <div class="flex items-center gap-6 mt-4 text-sm text-gray-500">
                                            <span v-if="form.created_at" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Created {{ new Date(form.created_at).toLocaleDateString() }}
                                            </span>
                                            <span v-if="form.updated_at" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Updated {{ new Date(form.updated_at).toLocaleDateString() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center ml-6 text-gray-400 group-hover:text-primary transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No forms created yet</h3>
                        <p class="text-gray-600 mb-8">Create your first form to start collecting responses</p>
                        <Button asChild size="lg">
                            <Link href="/form/new" class="gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Your First Form
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
