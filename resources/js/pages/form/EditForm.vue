<script setup lang="js">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input/index.js';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    form: Object,
    questions: Array,
});

const breadcrumbs = [
    {
        title: 'Forms',
        href: '/dashboard',
    },
    {
        title: props.form.title,
        href: `/form/${props.form.id}`,
    },
    {
        title: 'Edit Form',
        href: `/form/${props.form.id}/edit`,
    },
];

const form = useForm({
    title: props.form.title,
    description: props.form.description || '', // empty string if description is not provided
    questions: props.questions,
});

let nextQuestionId = ref(1);
let nextOptionId = ref(1);

onMounted(function () {
    // Find the highest question ID that already exists
    if (form.questions.length > 0) {
        let questionIds = [];
        for (let i = 0; i < form.questions.length; i++) {
            let question = form.questions[i];
            if (question.id) {
                questionIds.push(question.id);
            } else {
                questionIds.push(0);
            }
        }
        let maxQuestionId = Math.max(...questionIds);
        nextQuestionId.value = maxQuestionId + 1;
    }

    // Find the highest option ID across all questions
    let maxOptionId = 0;
    for (let i = 0; i < form.questions.length; i++) {
        let question = form.questions[i];
        if (question.options && question.options.length > 0) {
            let optionIds = [];
            for (let j = 0; j < question.options.length; j++) {
                let option = question.options[j];
                if (option.id) {
                    optionIds.push(option.id);
                } else {
                    optionIds.push(0);
                }
            }
            let questionMaxOptionId = Math.max(...optionIds);
            if (questionMaxOptionId > maxOptionId) {
                maxOptionId = questionMaxOptionId;
            }
        }
    }
    nextOptionId.value = maxOptionId + 1;
});

const addQuestion = () => {
    form.questions.push({
        id: nextQuestionId.value++,
        title: '',
        type: 'text',
        required: false,
        multipleChoice: false,
        options: [],
    });
};

const removeQuestion = (index) => {
    form.questions.splice(index, 1);
};

const moveQuestionUp = (index) => {
    if (index > 0) {
        let currentQuestion = form.questions[index];
        form.questions[index] = form.questions[index - 1];
        form.questions[index - 1] = currentQuestion;
    }
};

const moveQuestionDown = (index) => {
    if (index < form.questions.length - 1) {
        let currentQuestion = form.questions[index];
        form.questions[index] = form.questions[index + 1];
        form.questions[index + 1] = currentQuestion;
    }
};

const addOption = (question) => {
    if (!question.options) {
        question.options = [];
    }

    let newOption = {
        id: nextOptionId.value,
        text: '',
    };
    question.options.push(newOption);

    nextOptionId.value = nextOptionId.value + 1;
};

const removeOption = (question, index) => {
    question.options.splice(index, 1);
};

const submit = () => {
    form.put(`/form/${props.form.id}`, {
        preserveScroll: true,
    });
};

const showRegenerateConfirm = ref(false);
const isRegeneratingLink = ref(false);

const formLink = computed(() => {
    return `${window.location.origin}/form/${props.form.id}`;
});

const copyStatus = ref('Copy');

const copyLink = () => {
    if (formLink.value) {
        navigator.clipboard.writeText(formLink.value).then(() => {
            copyStatus.value = 'Copied!';
            setTimeout(() => {
                copyStatus.value = 'Copy';
            }, 2000);
        });
    }
};

const regenerateLink = () => {
    isRegeneratingLink.value = true;
    form.post(route('forms.regenerate_link', { form: props.form.id }), {
        preserveScroll: false,
        onFinish: () => {
            isRegeneratingLink.value = false;
            showRegenerateConfirm.value = false;
        },
        onError: () => {
            isRegeneratingLink.value = false;
        },
    });
};

const cancelRegenerateLink = () => {
    showRegenerateConfirm.value = false;
};
</script>

<template>
    <Head :title="`Edit Form - ${form.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 dark:bg-background">
            <div class="mx-auto max-w-4xl p-6">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-foreground">Edit Form</h1>
                    <p class="mt-2 text-muted-foreground">Update your form details and questions</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Form Details Card -->
                    <div class="rounded-xl border border-border bg-card p-8 shadow-sm">
                        <h2 class="mb-6 text-xl font-semibold text-foreground">Form Details</h2>

                        <div class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="title"> Title <span class="text-red-500">*</span> </Label>
                                <Input id="title" v-model="form.title" type="text" placeholder="Enter form title" required />
                                <div v-if="form.errors.title" class="text-sm text-red-600">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="description"> Description </Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="min-h-[100px] w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    rows="3"
                                    placeholder="Enter form description (optional)"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <!-- Form Link Section -->
                            <div class="grid gap-2">
                                <Label> Form Link </Label>
                                <div class="flex gap-3">
                                    <div
                                        class="flex flex-1 rounded-lg border border-input bg-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
                                    >
                                        <Input
                                            type="text"
                                            :model-value="formLink"
                                            readonly
                                            class="rounded-r-none border-0 bg-background focus-visible:ring-0 focus-visible:ring-offset-0"
                                            placeholder="Form link will appear here"
                                        />
                                        <Button type="button" @click="copyLink" variant="outline" class="rounded-l-none border-l-0">
                                            {{ copyStatus }}
                                        </Button>
                                    </div>
                                    <Button type="button" variant="outline" @click="showRegenerateConfirm = true" class="shrink-0 gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            />
                                        </svg>
                                        Generate New Link
                                    </Button>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Share this link to allow others to access and submit your form. Generating a new link will invalidate the current
                                    one.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Card -->
                    <div class="rounded-xl border border-border bg-card p-8 shadow-sm">
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-foreground">Questions</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Drag to reorder, edit, or add new questions</p>
                        </div>

                        <div v-if="form.questions.length === 0" class="rounded-xl border-2 border-dashed border-border/60 py-12 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                <svg class="h-8 w-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <p class="mb-4 font-medium text-muted-foreground">No questions yet</p>
                            <Button type="button" @click="addQuestion" variant="outline"> Add your first question </Button>
                        </div>

                        <div v-else class="space-y-6">
                            <draggable v-model="form.questions" item-key="id" handle=".drag-handle" class="space-y-4" :animation="200">
                                <template #item="{ element: question, index: qIndex }">
                                    <div class="rounded-xl border border-border bg-muted/40 p-6 transition-shadow duration-200 hover:shadow-md">
                                        <div class="mb-6 flex items-start justify-between">
                                            <div class="flex items-center gap-3">
                                                <!-- Drag handle -->
                                                <div
                                                    class="drag-handle cursor-move p-1 text-muted-foreground transition-colors hover:text-foreground"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-foreground"> Question {{ qIndex + 1 }} </span>
                                                    <span
                                                        v-if="question.required"
                                                        class="inline-flex items-center rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive dark:bg-destructive/20"
                                                    >
                                                        Required
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <!-- Move Up Button -->
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    type="button"
                                                    @click="moveQuestionUp(qIndex)"
                                                    :disabled="qIndex === 0"
                                                    title="Move up"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                </Button>
                                                <!-- Move Down Button -->
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    type="button"
                                                    @click="moveQuestionDown(qIndex)"
                                                    :disabled="qIndex === form.questions.length - 1"
                                                    title="Move down"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </Button>
                                                <!-- Delete Button -->
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    type="button"
                                                    @click="removeQuestion(qIndex)"
                                                    class="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        />
                                                    </svg>
                                                </Button>
                                            </div>
                                        </div>

                                        <div class="space-y-6">
                                            <div class="grid gap-2">
                                                <Label :for="`question-${question.id}-title`">
                                                    Question Text <span class="text-red-500">*</span>
                                                </Label>
                                                <Input
                                                    :id="`question-${question.id}-title`"
                                                    v-model="question.title"
                                                    type="text"
                                                    placeholder="Enter your question"
                                                    required
                                                />
                                            </div>

                                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                                <div class="grid gap-2">
                                                    <Label :for="`question-${question.id}-type`"> Question Type </Label>
                                                    <select
                                                        :id="`question-${question.id}-type`"
                                                        v-model="question.type"
                                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        <option value="text">Text Answer</option>
                                                        <option value="choice">Choice</option>
                                                    </select>
                                                </div>

                                                <div class="flex items-center pt-6">
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            :id="`question-${question.id}-required`"
                                                            v-model="question.required"
                                                            type="checkbox"
                                                            class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                                                        />
                                                        <Label :for="`question-${question.id}-required`" class="text-sm font-medium text-foreground">
                                                            Required question
                                                        </Label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Options Section -->
                                            <div v-if="question.type === 'choice'" class="rounded-lg border border-border bg-card p-6">
                                                <div class="mb-4 flex items-center justify-between">
                                                    <h4 class="text-sm font-medium text-foreground">Answer Options</h4>
                                                    <div class="flex items-center gap-4">
                                                        <!-- Multiple Choice Toggle -->
                                                        <div class="flex items-center gap-2">
                                                            <input
                                                                :id="`question-${question.id}-multiple`"
                                                                v-model="question.multipleChoice"
                                                                type="checkbox"
                                                                class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                                                            />
                                                            <Label :for="`question-${question.id}-multiple`" class="text-sm text-muted-foreground">
                                                                Allow multiple selections
                                                            </Label>
                                                        </div>
                                                        <Button type="button" @click="addOption(question)" variant="outline" size="sm">
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="mr-1.5 h-3.5 w-3.5"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke="currentColor"
                                                            >
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 4v16m8-8H4"
                                                                />
                                                            </svg>
                                                            Add Option
                                                        </Button>
                                                    </div>
                                                </div>

                                                <div
                                                    v-if="!question.options || question.options.length === 0"
                                                    class="rounded-lg border-2 border-dashed border-border/60 bg-muted py-8 text-center"
                                                >
                                                    <p class="text-sm text-muted-foreground">No options added yet</p>
                                                </div>

                                                <div class="space-y-3">
                                                    <div
                                                        v-for="(option, oIndex) in question.options"
                                                        :key="option.id"
                                                        class="flex items-center gap-3"
                                                    >
                                                        <div class="relative flex-1">
                                                            <span class="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground">
                                                                <svg
                                                                    v-if="!question.multipleChoice"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4"
                                                                    fill="none"
                                                                    viewBox="0 0 24 24"
                                                                    stroke="currentColor"
                                                                >
                                                                    <circle cx="12" cy="12" r="9" stroke-width="2" />
                                                                </svg>
                                                                <svg
                                                                    v-else
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4"
                                                                    fill="none"
                                                                    viewBox="0 0 24 24"
                                                                    stroke="currentColor"
                                                                >
                                                                    <rect x="5" y="5" width="14" height="14" rx="2" stroke-width="2" />
                                                                </svg>
                                                            </span>
                                                            <input
                                                                v-model="option.text"
                                                                type="text"
                                                                class="w-full rounded-md border border-input bg-background py-2 pr-4 pl-10 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                                placeholder="Option text"
                                                                required
                                                            />
                                                        </div>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            type="button"
                                                            @click="removeOption(question, oIndex)"
                                                            class="text-muted-foreground hover:bg-destructive/10 hover:text-destructive dark:hover:bg-destructive/20"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke="currentColor"
                                                            >
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M6 18L18 6M6 6l12 12"
                                                                />
                                                            </svg>
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </draggable>

                            <!-- Add Question Button -->
                            <div class="flex justify-center pt-4">
                                <Button type="button" @click="addQuestion" variant="outline" class="gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Question
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-between">
                        <Button type="button" variant="ghost" @click="$inertia.visit(`/form/${props.form.id}`)" class="gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Form
                        </Button>

                        <div class="flex gap-4">
                            <Button type="button" variant="outline" @click="$inertia.visit('/dashboard')"> Cancel </Button>
                            <Button type="submit" :disabled="form.processing" class="gap-2">
                                <svg
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                {{ form.processing ? 'Updating...' : 'Update Form' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Regenerate Link Confirmation Modal -->
        <div v-if="showRegenerateConfirm" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-background/80 backdrop-blur-sm transition-opacity" @click="cancelRegenerateLink"></div>
                <div class="relative w-full max-w-lg transform overflow-hidden rounded-xl border border-border bg-card shadow-xl transition-all">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-500/20">
                                <svg
                                    class="h-6 w-6 text-orange-600 dark:text-orange-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-2 text-base leading-6 font-semibold text-foreground">Generate New Form Link</h3>
                                <p class="text-sm text-muted-foreground">
                                    This will generate a new UUID for your form link. The current link will no longer work and anyone using it will
                                    get a "not found" error. Are you sure you want to continue?
                                </p>
                            </div>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <Button type="button" variant="outline" @click="cancelRegenerateLink" :disabled="isRegeneratingLink" class="flex-1">
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                @click="regenerateLink"
                                :disabled="isRegeneratingLink"
                                class="flex-1 gap-2 bg-orange-600 hover:bg-orange-700 focus:ring-orange-500"
                            >
                                <svg
                                    v-if="isRegeneratingLink"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                {{ isRegeneratingLink ? 'Generating...' : 'Generate New Link' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
