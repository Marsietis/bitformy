<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

// Define props to receive data from the controller
defineProps({
    forms: Array,
    user: Object,
});

const breadcrumbs = [
    {
        title: 'Dashboard'
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 dark:bg-background">
            <div class="mx-auto max-w-6xl p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-foreground">Dashboard</h1>
                    <p class="mt-2 text-muted-foreground">Manage your forms and view analytics</p>
                </div>
                <!-- Forms Section -->
                <div class="rounded-xl border border-border bg-card p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-foreground">Your Forms</h2>
                        <Button asChild>
                            <Link :href="route('forms.create')" class="gap-2">
                                <svg class="h-4 w-4" stroke="currentColor" viewBox="0 0 24 24">
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
                            class="group rounded-lg border border-border p-6 transition-all duration-200 hover:border-primary/50 hover:shadow-sm"
                        >
                            <Link :href="route('forms.show', form.id)" class="block">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-foreground transition-colors group-hover:text-primary">
                                            {{ form.title }}
                                        </h3>
                                        <p v-if="form.description" class="mt-2 line-clamp-2 text-muted-foreground">
                                            {{ form.description }}
                                        </p>
                                        <div class="mt-4 flex items-center gap-6 text-sm text-muted-foreground">
                                            <span v-if="form.created_at" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Created {{ new Date(form.created_at).toLocaleDateString() }}
                                            </span>
                                            <span v-if="form.updated_at" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Updated {{ new Date(form.updated_at).toLocaleDateString() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-6 flex items-center text-muted-foreground transition-colors group-hover:text-primary">
                                        <svg class="w-5 h-5" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                            <svg class="h-8 w-8 text-muted-foreground" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-foreground">No forms created yet</h3>
                        <p class="mb-8 text-muted-foreground">Create your first form to start collecting responses</p>
                        <Button asChild size="lg">
                            <Link :href="route('forms.create')" class="gap-2">
                                <svg class="h-4 w-4" stroke="currentColor" viewBox="0 0 24 24">
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
