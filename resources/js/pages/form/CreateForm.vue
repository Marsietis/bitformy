<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Input } from '@/components/ui/input/index.js';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Circle, Copy, GripVertical, Loader2, MessageCircleQuestion, Plus, Square, Trash } from 'lucide-vue-next';
import { nextTick, ref } from 'vue';

import draggable from 'vuedraggable';

const props = defineProps({
    questionTypes: {
        type: Array,
        required: true,
    },
});

const breadcrumbs = [
    {
        title: 'Forms',
        href: '/dashboard',
    },
    {
        title: 'New Form',
    },
];

const form = useForm({
    title: '',
    description: '',
    questions: [],
});

const nextQuestionId = ref(1);
const nextOptionId = ref(1);

const addQuestion = () => {
    const newQuestionId = nextQuestionId.value++;
    form.questions.push({
        id: newQuestionId,
        title: 'Question',
        type: 'text',
        required: false,
        multipleChoice: false,
        options: [],
    });
    focusNextQuestionTitle(newQuestionId);
};

const removeQuestion = (index) => {
    form.questions.splice(index, 1);
};

const copyQuestion = (questionIndex) => {
    const questionToCopy = form.questions[questionIndex];
    const newQuestionId = nextQuestionId.value++;
    const copiedQuestion = {
        id: newQuestionId,
        title: questionToCopy.title,
        type: questionToCopy.type,
        required: questionToCopy.required,
        multipleChoice: questionToCopy.multipleChoice,
        options: questionToCopy.options
            ? questionToCopy.options.map((option) => ({
                  id: nextOptionId.value++,
                  text: option.text,
              }))
            : [],
    };
    form.questions.splice(questionIndex + 1, 0, copiedQuestion);
    focusNextQuestionTitle(newQuestionId);
};

const focusNextQuestionTitle = (questionId) => {
    nextTick(() => {
        const input = document.getElementById(`question-${questionId}-title`);
        if (input) {
            input.focus();
            input.select();
        }
    });
};

const moveQuestionUp = (index) => {
    if (index > 0) {
        const currentQuestion = form.questions[index];
        form.questions[index] = form.questions[index - 1];
        form.questions[index - 1] = currentQuestion;
    }
};

const moveQuestionDown = (index) => {
    if (index < form.questions.length - 1) {
        const currentQuestion = form.questions[index];
        form.questions[index] = form.questions[index + 1];
        form.questions[index + 1] = currentQuestion;
    }
};

const addOption = (question) => {
    if (!question.options) {
        question.options = [];
    }

    const newOption = {
        id: nextOptionId.value++,
        text: 'Option ' + (question.options.length + 1),
    };
    question.options.push(newOption);
};

const removeOption = (question, index) => {
    question.options.splice(index, 1);
};

const submit = () => {
    form.post(route('forms.store'));
};
</script>

<template>
    <Head title="New Form" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <div class="mx-auto mt-12 max-w-6xl p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Form Details Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Create form</CardTitle>
                            <CardDescription>Design your form with custom questions and share it with others.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-6">
                                <div class="grid w-full gap-1.5">
                                    <Label for="title"> Title <span class="text-red-500">*</span> </Label>
                                    <Input id="title" v-model="form.title" type="text" placeholder="Enter form title" required />
                                </div>

                                <div class="grid w-full gap-1.5">
                                    <Label for="description"> Description </Label>
                                    <Textarea id="description" v-model="form.description" placeholder="Enter form description (optional)"></Textarea>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Add questions</CardTitle>
                            <CardDescription>Add and organize your form questions. Drag to reorder.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="form.questions.length === 0">
                                <Empty class="border border-dashed">
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <MessageCircleQuestion />
                                        </EmptyMedia>
                                        <EmptyTitle>No questions yet</EmptyTitle>
                                        <EmptyDescription> Add your first question to get started. </EmptyDescription>
                                    </EmptyHeader>
                                    <EmptyContent>
                                        <Button type="button" @click="addQuestion"> Add your first question </Button>
                                    </EmptyContent>
                                </Empty>
                            </div>

                            <div v-else class="space-y-6">
                                <draggable
                                    v-model="form.questions"
                                    item-key="id"
                                    handle=".drag-handle"
                                    class="space-y-4"
                                    :animation="200"
                                    :force-fallback="true"
                                    ghost-class="opacity-0"
                                >
                                    <template #item="{ element: question, index: qIndex }">
                                        <div class="rounded-xl border border-border p-6 transition-shadow duration-200 hover:shadow-md">
                                            <div class="mb-6 flex items-start justify-between">
                                                <div class="flex items-center gap-3">
                                                    <!-- Drag handle -->
                                                    <div
                                                        class="drag-handle cursor-grab p-1 text-muted-foreground transition-colors hover:text-foreground active:cursor-grabbing"
                                                    >
                                                        <GripVertical />
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
                                                    <!-- Copy question button -->
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger as-child>
                                                                <Button variant="ghost" type="button" @click="copyQuestion(qIndex)"> <Copy /> </Button
                                                            ></TooltipTrigger>
                                                            <TooltipContent> Copy question </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <!-- Move Up Button -->
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger as-child>
                                                                <Button
                                                                    variant="ghost"
                                                                    type="button"
                                                                    @click="moveQuestionUp(qIndex)"
                                                                    :disabled="qIndex === 0"
                                                                >
                                                                    <ChevronUp />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Move up</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>

                                                    <!-- Move Down Button -->
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger as-child>
                                                                <Button
                                                                    variant="ghost"
                                                                    type="button"
                                                                    @click="moveQuestionDown(qIndex)"
                                                                    :disabled="qIndex === form.questions.length - 1"
                                                                >
                                                                    <ChevronDown />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Move down</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <!-- Delete Button -->
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger as-child>
                                                                <Button variant="ghost" type="button" @click="removeQuestion(qIndex)">
                                                                    <Trash class="stroke-red-500" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Delete question</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
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
                                                        placeholder="Enter your question title here"
                                                        required
                                                        @focus="$event.target.select()"
                                                    />
                                                    <Input
                                                        v-if="question.type === 'text'"
                                                        class="mt-6 cursor-not-allowed"
                                                        type="text"
                                                        placeholder="Enter your answer"
                                                        disabled
                                                        readonly
                                                    />
                                                </div>
                                                <Separator class="my-4" />
                                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                                    <div class="grid gap-2">
                                                        <Label :for="`question-${question.id}-type`"> Question type </Label>
                                                        <Select v-model="question.type">
                                                            <SelectTrigger :id="`question-${question.id}-type`" class="w-full">
                                                                <SelectValue placeholder="Select a question type" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectGroup>
                                                                    <SelectItem v-for="type in questionTypes" :key="type.value" :value="type.value">
                                                                        {{ type.label }}
                                                                    </SelectItem>
                                                                </SelectGroup>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div class="flex items-center pt-6">
                                                        <div class="flex items-center space-x-2">
                                                            <Switch :id="`question-${question.id}-required`" v-model="question.required" />
                                                            <Label :for="`question-${question.id}-required`">Required question</Label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Options Section -->
                                                <div v-if="question.type === 'choice'" class="rounded-lg border border-border bg-card p-6">
                                                    <div class="mb-4 flex items-center justify-between">
                                                        <h4 class="text-sm font-medium text-foreground">Answer options</h4>
                                                        <div class="flex items-center gap-4">
                                                            <!-- Multiple Choice Toggle -->
                                                            <div class="flex items-center space-x-2" v-if="question.options.length > 0">
                                                                <Switch :id="`question-${question.id}-multiple`" v-model="question.multipleChoice" />
                                                                <Label :for="`question-${question.id}-multiple`">Allow multiple selections</Label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div v-if="question.options.length === 0">
                                                        <Empty>
                                                            <EmptyHeader>
                                                                <EmptyMedia variant="icon">
                                                                    <MessageCircleQuestion />
                                                                </EmptyMedia>
                                                                <EmptyTitle>No options yet</EmptyTitle>
                                                                <EmptyDescription> Add your first option to get started. </EmptyDescription>
                                                            </EmptyHeader>
                                                            <EmptyContent>
                                                                <Button type="button" @click="addOption(question)"> Add option </Button>
                                                            </EmptyContent>
                                                        </Empty>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <div
                                                            v-for="(option, oIndex) in question.options"
                                                            :key="option.id"
                                                            class="flex items-center gap-3"
                                                        >
                                                            <div class="relative flex-1">
                                                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground/70">
                                                                    <Circle v-if="!question.multipleChoice" class="h-4 w-4 fill-none" />

                                                                    <Square v-else class="h-4 w-4 fill-none" />
                                                                </span>
                                                                <Input
                                                                    v-model="option.text"
                                                                    type="text"
                                                                    placeholder="Option text"
                                                                    class="pl-10"
                                                                    required
                                                                    @focus="$event.target.select()"
                                                                />
                                                            </div>

                                                            <TooltipProvider>
                                                                <Tooltip>
                                                                    <TooltipTrigger as-child>
                                                                        <Button variant="ghost" type="button" @click="removeOption(question, oIndex)">
                                                                            <Trash class="stroke-red-500" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>Delete option</TooltipContent>
                                                                </Tooltip>
                                                            </TooltipProvider>
                                                        </div>
                                                        <div class="flex justify-center pt-4">
                                                            <Button
                                                                type="button"
                                                                @click="addOption(question)"
                                                                variant="outline"
                                                                v-if="question.options.length > 0"
                                                            >
                                                                <Plus />
                                                                Add option
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>

                                <!-- Add Question Button -->
                                <div class="flex justify-center">
                                    <Button variant="default" type="button" @click="addQuestion">
                                        <Plus />
                                        Add question
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-4">
                        <Button type="button" variant="outline" @click="$inertia.visit('/dashboard')"> Cancel </Button>
                        <Button type="submit" v-if="!form.processing"> Create form </Button>
                        <Button type="submit" v-if="form.processing">
                            <Loader2 class="h-4 w-4 animate-spin" />
                            Creating...
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
