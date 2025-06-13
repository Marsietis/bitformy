<script setup lang="js">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// Define props to receive data from the controller
const props = defineProps({
    forms: Array,
    formCount: Number,
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
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Stats cards could go here -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex flex-col h-full justify-center items-center">
                        <div class="text-2xl font-bold">{{ formCount }}</div>
                        <div class="text-sm text-muted-foreground">Total Forms</div>
                    </div>
                </div>
            </div>

            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 p-6 md:min-h-min dark:border-sidebar-border">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold">Your Forms</h2>
                    <Link
                        href="form/new"
                        class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                    >
                        Create New Form
                    </Link>
                </div>

                <div v-if="forms && forms.length > 0" class="space-y-3">
                    <div
                        v-for="form in forms"
                        :key="form.id"
                        class="group border border-sidebar-border/70 rounded-lg p-4 hover:bg-accent/50 transition-colors dark:border-sidebar-border"
                    >
                        <Link
                            :href="`/form/${form.id}`"
                            class="block"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium group-hover:text-primary transition-colors">
                                        {{ form.title}}
                                    </h3>
                                    <p v-if="form.description" class="text-sm text-muted-foreground mt-1">
                                        {{ form.description }}
                                    </p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                                        <span v-if="form.created_at">
                                            Created: {{ new Date(form.created_at).toLocaleDateString() }}
                                        </span>
                                        <span v-if="form.updated_at">
                                            Updated: {{ new Date(form.updated_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center text-muted-foreground group-hover:text-primary transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <div class="text-muted-foreground mb-4">
                        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-lg">No forms created yet</p>
                        <p class="text-sm">Create your first form to get started</p>
                    </div>
                    <Link
                        href="/form/new"
                        class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                    >
                        Create Your First Form
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
