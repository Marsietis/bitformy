<script setup lang="ts">
import type { ColumnDef, ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { FlexRender, getCoreRowModel, getFilteredRowModel, getPaginationRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { ArrowUpDown, ChevronDown, Copy, Eye, FilePlusIcon, FileX, MoreHorizontal, Pencil, Search, Trash } from 'lucide-vue-next';
import { h, ref } from 'vue';

import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { valueUpdater } from '@/utils';
import { Head, Link, router } from '@inertiajs/vue3';

export interface Form {
    id: number;
    title: string;
    description?: string;
    created_at: string;
    updated_at: string;
}

// Define props to receive data from the controller
const props = defineProps<{
    forms: Form[];
}>();

const breadcrumbs = [
    {
        title: 'Dashboard',
    },
];

// Define table columns
const columns: ColumnDef<Form>[] = [
    {
        accessorKey: 'title',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Title', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) =>
            h(
                Link,
                {
                    href: route('forms.show', row.original.id),
                    class: 'font-medium text-foreground hover:text-primary transition-colors',
                },
                () => row.getValue('title'),
            ),
    },
    {
        accessorKey: 'description',
        header: 'Description',
        cell: ({ row }) => {
            const description = row.getValue('description') as string | undefined;
            return h('div', { class: 'max-w-md truncate text-muted-foreground' }, description || '-');
        },
    },
    {
        accessorKey: 'created_at',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Created', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const date = new Date(row.getValue('created_at'));
            return h('div', { class: 'text-muted-foreground' }, date.toLocaleDateString());
        },
    },
    {
        accessorKey: 'updated_at',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Updated', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const date = new Date(row.getValue('updated_at'));
            return h('div', { class: 'text-muted-foreground' }, date.toLocaleDateString());
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const form = row.original;

            return h(
                DropdownMenu,
                {},
                {
                    default: () => [
                        h(
                            DropdownMenuTrigger,
                            { asChild: true },
                            {
                                default: () =>
                                    h(
                                        Button,
                                        { variant: 'ghost', class: 'h-8 w-8 p-0' },
                                        {
                                            default: () => [h('span', { class: 'sr-only' }, 'Open menu'), h(MoreHorizontal, { class: 'h-4 w-4' })],
                                        },
                                    ),
                            },
                        ),
                        h(
                            DropdownMenuContent,
                            { align: 'end' },
                            {
                                default: () => [
                                    h(
                                        DropdownMenuItem,
                                        {
                                            onClick: () => router.visit(route('forms.show', form.id)),
                                        },
                                        () => [h(Eye), 'View form'],
                                    ),
                                    h(
                                        DropdownMenuItem,
                                        {
                                            onClick: () => router.visit(route('forms.edit', form.id)),
                                        },
                                        () => [h(Pencil), 'Edit form'],
                                    ),
                                    h(DropdownMenuItem, { onClick: () => copyLink(`${window.location.origin}/forms/${form.id}`) }, () => [
                                        h(Copy),
                                        'Copy link',
                                    ]),
                                    h(DropdownMenuItem, { variant: 'destructive', onClick: () => confirmDelete(form) }, () => [
                                        h(Trash),
                                        'Delete form',
                                    ]),
                                ],
                            },
                        ),
                    ],
                },
            );
        },
    },
];

// Initialize table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});

// Delete confirmation state
const formToDelete = ref<Form | null>(null);
const showDeleteDialog = ref(false);

const confirmDelete = (form: Form) => {
    formToDelete.value = form;
    showDeleteDialog.value = true;
};

const deleteForm = () => {
    if (formToDelete.value) {
        router.delete(route('forms.destroy', formToDelete.value.id), {
            onFinish: () => {
                showDeleteDialog.value = false;
                formToDelete.value = null;
            },
        });
    }
};

const copyStatus = ref('Copy');

const copyLink = (link: string) => {
    if (link && link.length > 0) {
        navigator.clipboard.writeText(link).then(() => {
            copyStatus.value = 'Copied!';
            setTimeout(() => {
                copyStatus.value = 'Copy';
            }, 2000);
        });
    }
};

// Create table instance
const table = useVueTable({
    get data() {
        return props.forms || [];
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <div class="mx-auto mt-12 max-w-6xl p-6">
                <!-- Forms Section -->
                <Card class="w-full">
                    <CardHeader class="flex items-center justify-between">
                        <div>
                            <CardTitle>Your Forms</CardTitle>
                            <CardDescription>Manage your forms or create a new one</CardDescription>
                        </div>
                        <Button asChild>
                            <Link :href="route('forms.create')" class="gap-2">
                                <FilePlusIcon />
                                Create New Form
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <!-- DataTable -->
                        <div v-if="forms && forms.length > 0" class="w-full">
                            <!-- Table Controls -->
                            <div class="flex items-center gap-4 py-4">
                                <div class="relative w-full max-w-sm items-center">
                                    <Input
                                        id="search"
                                        type="text"
                                        placeholder="Search..."
                                        class="pl-10"
                                        :model-value="(table.getColumn('title')?.getFilterValue() as string) ?? ''"
                                        @update:model-value="(value) => table.getColumn('title')?.setFilterValue(value)"
                                    />
                                    <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
                                        <Search class="size-6 text-muted-foreground" />
                                    </span>
                                </div>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" class="ml-auto"> Columns <ChevronDown class="ml-2 h-4 w-4" /> </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuCheckboxItem
                                            v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                                            :key="column.id"
                                            class="capitalize"
                                            :model-value="column.getIsVisible()"
                                            @update:model-value="(value) => column.toggleVisibility(!!value)"
                                        >
                                            {{ column.id.replace('_', ' ') }}
                                        </DropdownMenuCheckboxItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>

                            <!-- Table -->
                            <div class="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                            <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                                <FlexRender
                                                    v-if="!header.isPlaceholder"
                                                    :render="header.column.columnDef.header"
                                                    :props="header.getContext()"
                                                />
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <template v-if="table.getRowModel().rows?.length">
                                            <TableRow
                                                v-for="row in table.getRowModel().rows"
                                                :key="row.id"
                                                :data-state="row.getIsSelected() && 'selected'"
                                            >
                                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                                </TableCell>
                                            </TableRow>
                                        </template>

                                        <TableRow v-else>
                                            <TableCell :colspan="columns.length" class="h-24 text-center"> No results. </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <!-- Pagination -->
                            <div class="flex items-center justify-end gap-2 py-4">
                                <div class="flex-1 text-sm text-muted-foreground">{{ table.getFilteredRowModel().rows.length }} form(s) found.</div>
                                <div class="flex gap-2">
                                    <Button variant="outline" size="sm" :disabled="!table.getCanPreviousPage()" @click="table.previousPage()">
                                        Previous
                                    </Button>
                                    <Button variant="outline" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()"> Next </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else>
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <FileX />
                                    </EmptyMedia>
                                    <EmptyTitle>No Forms Yet</EmptyTitle>
                                    <EmptyDescription> You haven't created any forms yet. Get started by creating your first form. </EmptyDescription>
                                </EmptyHeader>
                                <EmptyContent>
                                    <div class="flex gap-2">
                                        <Link :href="route('forms.create')">
                                            <Button>Create Form</Button>
                                        </Link>
                                    </div>
                                </EmptyContent>
                            </Empty>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the form
                        <span v-if="formToDelete" class="font-semibold">"{{ formToDelete.title }}"</span>
                        and remove all associated data from our servers.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <Button variant="destructive" @click="deleteForm">Delete</Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
