<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CalendarPlus, Copy, Eye, Pencil, RefreshCw, Share2, Trash } from 'lucide-vue-next';
import * as openpgp from 'openpgp';
import { computed, ref } from 'vue';

const page = usePage();

const props = defineProps<{
    form: any;
}>();

const user = computed(() => page.props.auth.user);
const form = props.form;
const questions = form.questions.map((question: { options: string }) => ({
    ...question,
    options: JSON.parse(question.options),
}));

console.log(questions);
const armoredPublicKey = form.user.public_key;

const breadcrumbs = computed(function () {
    return [
        {
            title: 'Forms',
            href: '/dashboard',
        },
        {
            title: form.title,
            href: `/forms/${form.id}`,
        },
    ];
});

// Function to set up initial empty answers for all questions
const initializeAnswers = () => {
    const initialAnswers = [];

    for (const question of questions) {
        if (question.type === 'choice') {
            if (question.allow_multiple) {
                // Use an empty array for questions that allow multiple answers
                initialAnswers[question.id] = [];
            } else {
                // Use null for all other questions
                initialAnswers[question.id] = null;
            }
        } else {
            // Use null for all other questions
            initialAnswers[question.id] = null;
        }
    }
    return initialAnswers;
};

const answers = ref(initializeAnswers());
const submissionStatus = ref('');
const submitted = ref(false);
const showSharePopover = ref(false);
const copyStatus = ref('Copy');

const formLink = computed(() => {
    return `${page.props.appUrl}/forms/${form.id}`;
});

const layout = computed(function () {
    if (user.value) {
        return AppLayout;
    } else {
        return GuestLayout;
    }
});

const copyLink = () => {
    navigator.clipboard.writeText(formLink.value).then(() => {
        copyStatus.value = 'Copied!';
        setTimeout(() => {
            copyStatus.value = 'Copy';
        }, 2000);
    });
};

const validateRequiredFields = () => {
    for (const question of questions) {
        if (question.required) {
            const answer = answers.value[question.id];

            if (!hasValidAnswer(answer)) {
                submissionStatus.value = 'Error: The question "' + question.title + '" is required.';
                return;
            }
        }
    }
};

const hasValidAnswer = (answer: any) => {
    if (Array.isArray(answer)) return answer.length > 0;
    return answer !== null && answer !== '' && answer !== undefined;
};

const isSubmitting = ref(false); // Is used to prevent multiple submissions at the same time

const submitForm = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    validateRequiredFields();
    if (!armoredPublicKey) {
        submissionStatus.value = 'Error: Form creator has no public key. Cannot encrypt answers.';
        return;
    }

    submissionStatus.value = 'Encrypting and submitting...';

    try {
        const publicKey = await openpgp.readKey({
            armoredKey: armoredPublicKey,
        });

        const answersToEncrypt = [];

        for (const question of questions) {
            const answer = answers.value[question.id];

            if (hasValidAnswer(answer)) {
                answersToEncrypt.push({
                    question: question,
                    answer: answer,
                });
            }
        }

        const encryptedAnswers = [];

        for (const item of answersToEncrypt) {
            const question = item.question;
            const answer = item.answer;

            let answerString = String(answer);
            if (typeof answer === 'object') {
                answerString = JSON.stringify(answer);
            }

            const message = await openpgp.createMessage({
                text: answerString,
            });

            const encryptedMessage = await openpgp.encrypt({
                message: message,
                encryptionKeys: publicKey,
            });

            encryptedAnswers.push({
                question_id: question.id,
                answer: encryptedMessage,
            });
        }

        router.post(
            route('answers.store', form.id),
            { answers: encryptedAnswers },
            {
                onSuccess: function () {
                    submitted.value = true;
                    submissionStatus.value = 'Form submitted successfully!';
                    isSubmitting.value = false;
                },
                onError: function (errors) {
                    console.error('Submission error:', errors);
                    submissionStatus.value = 'An error occurred during submission. Please try again.';
                    isSubmitting.value = false;
                },
            },
        );
    } catch (error) {
        console.error(error);
        submissionStatus.value = 'Error: Could not encrypt answers.';
        isSubmitting.value = false;
    }
};

function submitAnotherResponse() {
    answers.value = initializeAnswers();
    submissionStatus.value = '';
    submitted.value = false;
}

const showDeleteConfirm = ref(false);

function deleteForm() {
    router.delete(route('forms.destroy', form.id), {
        onSuccess: function () {
            router.visit('/dashboard');
        },
    });
    showDeleteConfirm.value = false;
}

function cancelDelete() {
    showDeleteConfirm.value = false;
}
</script>

<template>
    <Head :title="form.title" />

    <component :is="layout" :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 dark:bg-background">
            <div class="mx-auto mt-12 max-w-6xl space-y-6 p-6">
                <!-- Form Header Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ form.title }}</CardTitle>
                        <CardDescription>{{ form.description }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-6">
                            <span class="flex items-center gap-2">
                                <CalendarPlus class="h-4 w-4" />
                                Created:
                                {{
                                    new Date(form.created_at).toLocaleString(undefined, {
                                        year: 'numeric',
                                        month: 'numeric',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        hour12: false,
                                    })
                                }}
                            </span>
                            <span class="flex items-center gap-2">
                                <RefreshCw class="h-4 w-4" />
                                Updated:
                                {{
                                    new Date(form.updated_at).toLocaleString(undefined, {
                                        year: 'numeric',
                                        month: 'numeric',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        hour12: false,
                                    })
                                }}
                            </span>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <div v-if="user && form.user_id === user.id" class="flex items-center gap-1.5">
                            <Button @click="router.visit(route('answers.show', form.id))" variant="outline" size="sm"> <Eye /> View Answers</Button>
                            <Button @click="router.visit(route('forms.edit', form.id))" variant="outline" size="sm"> <Pencil /> Edit</Button>

                            <!-- Delete Confirmation Dialog -->
                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <Button variant="destructive" size="sm"> <Trash /> Delete</Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            This action cannot be undone. This will permanently delete the form
                                            <span class="font-semibold">"{{ form.title }}"</span>
                                            and remove all associated data from our servers.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                        <Button variant="destructive" @click="deleteForm">Delete</Button>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>

                            <!-- Share Popover -->
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button size="sm" class="shrink-0"> <Share2 /> Share </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Share form</DialogTitle>
                                        <DialogDescription> Anyone who has this link will be able to view and submit this form. </DialogDescription>
                                    </DialogHeader>
                                    <div class="flex items-center space-x-2">
                                        <div class="grid flex-1 gap-2">
                                            <Label for="link" class="sr-only"> Link </Label>
                                            <Input id="link" :model-value="formLink" read-only />
                                        </div>
                                        <Button @click="copyLink" size="sm" class="px-3">
                                            {{ copyStatus }}
                                            <Copy class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <DialogFooter class="sm:justify-start">
                                        <DialogClose as-child>
                                            <Button type="button" variant="outline"> Close </Button>
                                        </DialogClose>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        </div></CardFooter
                    >
                </Card>

                <!-- Form Content -->
                <form @submit.prevent="submitForm" v-if="!submitted">
                    <Card>
                        <CardContent v-if="questions && questions.length > 0">
                            <div>
                                <div v-for="(question, index) in questions" :key="question.id" class="py-8">
                                    <div class="mb-6">
                                        <div class="flex items-start gap-4">
                                            <div class="flex-1">
                                                <Label class="mb-4 block text-lg font-semibold text-foreground">
                                                    {{ index + 1 }}. {{ question.title }}
                                                    <span v-if="question.required" class="ml-2">
                                                        <Badge variant="destructive"> Required </Badge>
                                                    </span>
                                                </Label>

                                                <!-- Text question type -->
                                                <div v-if="question.type === 'text'">
                                                    <Input
                                                        type="text"
                                                        :required="question.required"
                                                        v-model="answers[question.id]"
                                                        :placeholder="question.required ? 'Your answer (required)' : 'Your answer'"
                                                        class="w-full"
                                                    />
                                                </div>

                                                <!-- Choice question type -->
                                                <div v-else-if="question.type === 'choice'">
                                                    <div v-if="question.options.length > 0" class="space-y-3">
                                                        <div
                                                            v-for="(option, optionIndex) in question.options"
                                                            :key="optionIndex"
                                                            class="flex items-center gap-3"
                                                        >
                                                            <input
                                                                :type="question.allow_multiple ? 'checkbox' : 'radio'"
                                                                :id="`q${index}_option${optionIndex}`"
                                                                :name="`question_${question.id}`"
                                                                :value="option"
                                                                v-model="answers[question.id]"
                                                                class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                                                :required="question.required && !question.allow_multiple && !answers[question.id]"
                                                            />
                                                            <Label
                                                                :for="`q${index}_option${optionIndex}`"
                                                                class="cursor-pointer font-normal text-foreground"
                                                            >
                                                                {{ option }}
                                                            </Label>
                                                        </div>
                                                    </div>
                                                    <div v-else class="text-muted-foreground/80 italic">No options available</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="questions && questions.length > 0" class="mt-8 flex items-center justify-between">
                                <span v-if="submissionStatus" class="text-sm text-muted-foreground">{{ submissionStatus }}</span>
                                <div class="ml-auto">
                                    <Button type="submit"> Submit</Button>
                                </div>
                            </div>
                        </CardContent>

                        <div v-else class="py-12 text-center">
                            <p class="text-lg text-muted-foreground">This form has no questions.</p>
                        </div>
                    </Card>
                </form>

                <!-- Success State -->
                <div v-else class="rounded-xl border border-border bg-card p-12 text-center shadow-sm">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 dark:bg-emerald-500/25">
                        <svg class="h-8 w-8 text-emerald-500" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="mb-3 text-2xl font-bold text-foreground">Form submitted successfully</h2>
                    <p class="mb-8 text-muted-foreground">Your response has been recorded and encrypted.</p>
                    <Button @click="submitAnotherResponse" variant="outline" size="lg"> Submit another response</Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 z-50 flex h-full w-full items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="relative mx-auto w-full max-w-md rounded-xl border border-border bg-card shadow-xl">
                <div class="p-6">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10 dark:bg-destructive/20">
                        <svg class="h-6 w-6 text-destructive" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.124 16.5c-.77.833.192 2.5 1.732 2.5z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-center text-lg font-semibold text-foreground">Delete Form</h3>
                    <p class="mb-6 text-center text-sm text-muted-foreground">
                        Are you sure you want to delete "{{ form.title }}"? This action cannot be undone.
                    </p>
                    <div class="flex gap-3">
                        <Button @click="cancelDelete" variant="outline" class="flex-1"> Cancel</Button>
                        <Button @click="deleteForm" variant="destructive" class="flex-1"> Delete</Button>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
