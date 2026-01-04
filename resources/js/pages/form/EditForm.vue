<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
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
import { ChevronDown, ChevronUp, Circle, Copy, GripVertical, Loader2, MessageCircleQuestion, Plus, Square, Trash, RefreshCw } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';
import draggable from 'vuedraggable';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    questions: {
        type: Array,
        required: true,
    },
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
    description: props.form.description || '',
    questions: props.questions.map((q) => ({
        id: q.id,
        title: q.title,
        type: q.type,
        required: q.required ?? false,
        multipleChoice: q.multipleChoice ?? false,
        options: q.options || [],
        rating_levels: q.rating_levels || 5,
        order: q.order,
    })),
});

const nextQuestionId = ref(1);
const nextOptionId = ref(1);

onMounted(function () {
    // Find the highest question ID that already exists
    if (form.questions.length > 0) {
        const questionIds: number[] = [];
        for (let i = 0; i < form.questions.length; i++) {
            const question = form.questions[i];
            if (question.id) {
                questionIds.push(question.id);
            } else {
                questionIds.push(0);
            }
        }
        const maxQuestionId = Math.max(...questionIds);
        nextQuestionId.value = maxQuestionId + 1;
    }

    // Find the highest option ID across all questions
    let maxOptionId = 0;
    for (let i = 0; i < form.questions.length; i++) {
        const question = form.questions[i];
        if (question.options && question.options.length > 0) {
            const optionIds: number[] = [];
            for (let j = 0; j < question.options.length; j++) {
                const option = question.options[j];
                if (option.id) {
                    optionIds.push(option.id);
                } else {
                    optionIds.push(0);
                }
            }
            const questionMaxOptionId = Math.max(...optionIds);
            if (questionMaxOptionId > maxOptionId) {
                maxOptionId = questionMaxOptionId;
            }
        }
    }
    nextOptionId.value = maxOptionId + 1;
});

const addQuestion = () => {
    const newQuestionId = nextQuestionId.value++;
    form.questions.push({
        id: newQuestionId,
        title: 'Question',
        type: 'text',
        required: false,
        multipleChoice: false,
        options: [],
        rating_levels: 5,
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
        rating_levels: questionToCopy.rating_levels,
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
    form.put(route('forms.update', props.form.id), {
        preserveScroll: true,
    });
};

const formLink = computed(() => {
    return route('forms.show', props.form.id);
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
    form.post(route('forms.regenerate_link', { form: props.form.id }), {
        preserveScroll: false,
    });
};
</script>

<template>
    <Head :title="`Edit Form - ${form.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <div class="mx-auto mt-12 max-w-6xl p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Form Details Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Edit form</CardTitle>
                            <CardDescription>Update your form details and questions.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-6">
                                <div class="grid w-full gap-1.5">
                                    <Label for="title"> Title <span class="text-red-500">*</span> </Label>
                                    <Input id="title" v-model="form.title" type="text" placeholder="Enter form title" required />
                                    <div v-if="form.errors.title" class="text-sm text-red-600">
                                        {{ form.errors.title }}
                                    </div>
                                </div>

                                <div class="grid w-full gap-1.5">
                                    <Label for="description"> Description </Label>
                                    <Textarea id="description" v-model="form.description" placeholder="Enter form description (optional)"></Textarea>
                                    <div v-if="form.errors.description" class="text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </div>
                                </div>

                                <!-- Form Link Section -->
                                <div class="grid w-full gap-1.5">
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
                                                <Copy /> {{ copyStatus }}
                                            </Button>
                                        </div>
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button variant="outline">
                                                    <RefreshCw />
                                                    Generate New Link
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This will generate a new UUID for your form link. The current link will no longer work and anyone using it will get a "not found" error. Are you sure you want to continue?
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction @click="regenerateLink">Continue</AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        Share this link to allow others to access and submit your form. Generating a new link will invalidate the current one.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Questions Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Questions</CardTitle>
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
                                                        <Badge variant="destructive" v-if="question.required"> Required </Badge>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <!-- Copy question button -->
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger as-child>
                                                                <Button variant="ghost" type="button" @click="copyQuestion(qIndex)"> <Copy /> </Button>
                                                            </TooltipTrigger>
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
                                                        v-model="form.questions[qIndex].title"
                                                        placeholder="Enter your question title here"
                                                        required
                                                        @focus="$event.target.select()"
                                                    />
                                                    <Input
                                                        v-if="form.questions[qIndex].type === 'text'"
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
                                                        <Select v-model="form.questions[qIndex].type">
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
                                                <div v-if="form.questions[qIndex].type === 'choice'" class="rounded-lg border border-border bg-card p-6">
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

                                                    <div v-if="form.questions[qIndex].options.length === 0">
                                                        <Empty>
                                                            <EmptyHeader>
                                                                <EmptyMedia variant="icon">
                                                                    <MessageCircleQuestion />
                                                                </EmptyMedia>
                                                                <EmptyTitle>No options yet</EmptyTitle>
                                                                <EmptyDescription> Add your first option to get started. </EmptyDescription>
                                                            </EmptyHeader>
                                                            <EmptyContent>
                                                                <Button type="button" @click="addOption(form.questions[qIndex])"> Add option </Button>
                                                            </EmptyContent>
                                                        </Empty>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <div
                                                            v-for="(option, oIndex) in form.questions[qIndex].options"
                                                            :key="option.id"
                                                            class="flex items-center gap-3"
                                                        >
                                                            <div class="relative flex-1">
                                                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground/70">
                                                                    <Circle v-if="!question.multipleChoice" class="h-4 w-4 fill-none" />
                                                                    <Square v-else class="h-4 w-4 fill-none" />
                                                                </span>
                                                                <Input
                                                                    v-model="form.questions[qIndex].options[oIndex].text"
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
                                                                        <Button variant="ghost" type="button" @click="removeOption(form.questions[qIndex], oIndex)">
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
                                                                @click="addOption(form.questions[qIndex])"
                                                                variant="outline"
                                                                v-if="form.questions[qIndex].options.length > 0"
                                                            >
                                                                <Plus />
                                                                Add option
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Rating options -->
                                                <div v-if="form.questions[qIndex].type === 'rating'" class="rounded-lg border border-border bg-card p-6">
                                                    <div class="mb-4 flex items-center justify-between">
                                                        <h4 class="text-sm font-medium text-foreground">Rating options</h4>
                                                    </div>
                                                    <div class="grid gap-2">
                                                        <Label :for="`question-${question.id}-rating-levels`"> Rating levels </Label>
                                                        <Select v-model="form.questions[qIndex].rating_levels">
                                                            <SelectTrigger :id="`question-${question.id}-rating-levels`" class="w-full">
                                                                <SelectValue placeholder="Select number of rating levels" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectGroup>
                                                                    <SelectItem v-for="i in 10" :key="i" :value="i">
                                                                        {{ i }}
                                                                    </SelectItem>
                                                                </SelectGroup>
                                                            </SelectContent>
                                                        </Select>
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
                    <div v-if="Object.keys(form.errors).length > 0" class="rounded-lg border border-red-500 bg-red-50 p-4 dark:bg-red-950">
                        <p class="font-medium text-red-600 dark:text-red-400">Please fix the following errors:</p>
                        <ul class="mt-2 list-inside list-disc text-sm text-red-600 dark:text-red-400">
                            <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="flex justify-between">
                        <Button type="button" variant="ghost" @click="$inertia.visit(route('forms.show', props.form.id))">
                            Back to Form
                        </Button>

                        <div class="flex gap-4">
                            <Button type="button" variant="outline" @click="$inertia.visit('/dashboard')"> Cancel </Button>
                            <Button type="submit" v-if="!form.processing"> Update Form </Button>
                            <Button type="submit" v-if="form.processing" disabled>
                                <Loader2 class="h-4 w-4 animate-spin" />
                                Updating...
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </div>



    </AppLayout>
</template>
