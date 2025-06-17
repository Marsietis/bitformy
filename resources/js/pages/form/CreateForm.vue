<script setup lang="js">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';
import draggable from 'vuedraggable';
import { Input } from '@/components/ui/input/index.js';
import { Label } from '@/components/ui/label';

const breadcrumbs = [
    {
        title: 'Forms',
        href: '/dashboard',
    },
    {
        title: 'New Form',
        href: '/form/create',
    },
];

const form = useForm({
    title: '',
    description: '',
    questions: []
});

let nextQuestionId = ref(1);
let nextOptionId = ref(1);

const addQuestion = () => {
    form.questions.push({
        id: nextQuestionId.value++,
        title: '',
        type: 'text',
        required: false,
        multipleChoice: false,
        options: []
    });
};

const removeQuestion = (index) => {
    form.questions.splice(index, 1);
};

const moveQuestionUp = (index) => {
    if (index > 0) {
        const temp = form.questions[index];
        form.questions[index] = form.questions[index - 1];
        form.questions[index - 1] = temp;
    }
};

const moveQuestionDown = (index) => {
    if (index < form.questions.length - 1) {
        const temp = form.questions[index];
        form.questions[index] = form.questions[index + 1];
        form.questions[index + 1] = temp;
    }
};

const addOption = (question) => {
    question.options.push({
        id: nextOptionId.value++,
        text: ''
    });
};

const removeOption = (question, index) => {
    question.options.splice(index, 1);
};

const submit = () => {
    form.post('/form');
};
</script>

<template>
    <Head title="New Form" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-4xl p-6">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Create New Form</h1>
                    <p class="mt-2 text-gray-600">Design your form with custom questions and share it with others.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Form Details Card -->
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Form Details</h2>

                        <div class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="title">
                                    Title <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter form title"
                                    required
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="description">
                                    Description
                                </Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                                    rows="3"
                                    placeholder="Enter form description (optional)"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Card -->
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-8">
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-900">Questions</h2>
                            <p class="text-sm text-gray-600 mt-1">Add and organize your form questions. Drag to reorder.</p>
                        </div>

                        <div v-if="form.questions.length === 0" class="text-center py-12 border-2 border-dashed border-gray-200 rounded-xl">
                            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-600 font-medium mb-4">No questions yet</p>
                            <Button type="button" @click="addQuestion" variant="outline">
                                Add your first question
                            </Button>
                        </div>

                        <div v-else class="space-y-6">
                            <draggable
                                v-model="form.questions"
                                item-key="id"
                                handle=".drag-handle"
                                class="space-y-4"
                                :animation="200"
                            >
                                <template #item="{ element: question, index: qIndex }">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-6 hover:shadow-md transition-shadow duration-200">
                                        <div class="flex justify-between items-start mb-6">
                                            <div class="flex items-center gap-3">
                                                <!-- Drag handle -->
                                                <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 transition-colors p-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        Question {{ qIndex + 1 }}
                                                    </span>
                                                    <span v-if="question.required" class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-red-600 bg-red-100 rounded-full">
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
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </Button>
                                                <!-- Delete Button -->
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    type="button"
                                                    @click="removeQuestion(qIndex)"
                                                    class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="grid gap-2">
                                                    <Label :for="`question-${question.id}-type`">
                                                        Question Type
                                                    </Label>
                                                    <select
                                                        :id="`question-${question.id}-type`"
                                                        v-model="question.type"
                                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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
                                                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                                        />
                                                        <Label :for="`question-${question.id}-required`" class="text-sm font-medium text-gray-700">
                                                            Required question
                                                        </Label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Options Section -->
                                            <div v-if="question.type === 'choice'" class="rounded-lg border border-gray-200 bg-white p-6">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-sm font-medium text-gray-900">Answer Options</h4>
                                                    <div class="flex items-center gap-4">
                                                        <!-- Multiple Choice Toggle -->
                                                        <div class="flex items-center gap-2">
                                                            <input
                                                                :id="`question-${question.id}-multiple`"
                                                                v-model="question.multipleChoice"
                                                                type="checkbox"
                                                                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                                            />
                                                            <Label :for="`question-${question.id}-multiple`" class="text-sm text-gray-600">
                                                                Allow multiple selections
                                                            </Label>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            @click="addOption(question)"
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                            Add Option
                                                        </Button>
                                                    </div>
                                                </div>

                                                <div v-if="question.options.length === 0" class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                                                    <p class="text-gray-500 text-sm">No options added yet</p>
                                                </div>

                                                <div class="space-y-3">
                                                    <div v-for="(option, oIndex) in question.options" :key="option.id" class="flex items-center gap-3">
                                                        <div class="flex-1 relative">
                                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                                                <svg v-if="!question.multipleChoice" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                                                </svg>
                                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <rect x="5" y="5" width="14" height="14" rx="2" stroke-width="2"/>
                                                                </svg>
                                                            </span>
                                                            <input
                                                                v-model="option.text"
                                                                type="text"
                                                                class="w-full pl-10 pr-4 py-2 rounded-md border border-input bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                                placeholder="Option text"
                                                                required
                                                            />
                                                        </div>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            type="button"
                                                            @click="removeOption(question, oIndex)"
                                                            class="text-gray-400 hover:text-red-600 hover:bg-red-50"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                                <Button
                                    type="button"
                                    @click="addQuestion"
                                    variant="outline"
                                    class="gap-2"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Question
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="$inertia.visit('/dashboard')"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Creating...' : 'Create Form' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
