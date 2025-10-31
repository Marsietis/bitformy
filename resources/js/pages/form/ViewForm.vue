<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import * as openpgp from 'openpgp';
import { computed, ref } from 'vue';

const page = usePage();
const props = defineProps({
    form: Object,
});

const user = computed(() => page.props.auth.user)
const form = props.form;
const questions = form.questions;
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

const getOptions = (optionsString) => {
    if (!optionsString) {
        return {
            items: [],
            multiple: false,
        };
    }
    return JSON.parse(optionsString);
};

// Function to set up initial empty answers for all questions
const initializeAnswers = () => {
    const initialAnswers = {};

    for (const question of questions) {
        const options = getOptions(question.options);

        if (question.type === 'choice' && options.multiple === true) {
            // Use an empty array for questions that allow multiple answers
            initialAnswers[question.id] = [];
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

const submitForm = async () => {
    // check if all required questions have answers
    for (const question of questions) {
        if (question.required === true) {
            const answer = answers.value[question.id];

            let isEmptyAnswer = false;

            if (answer === null || answer === '') {
                isEmptyAnswer = true;
            }

            if (Array.isArray(answer) && answer.length === 0) {
                isEmptyAnswer = true;
            }

            if (isEmptyAnswer) {
                submissionStatus.value = 'Error: The question "' + question.title + '" is required.';
                return;
            }
        }
    }

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

            let hasAnswer = false;

            if (answer !== null && answer !== '') {
                hasAnswer = true;
            }

            if (Array.isArray(answer) && answer.length > 0) {
                hasAnswer = true;
            }

            if (hasAnswer) {
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
                },
                onError: function (errors) {
                    console.error('Submission error:', errors);
                    submissionStatus.value = 'An error occurred during submission. Please try again.';
                },
            },
        );
    } catch (error) {
        console.error(error);
        submissionStatus.value = 'Error: Could not encrypt answers.';
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
            <div class="mx-auto max-w-4xl p-6">
                <!-- Form Header Card -->
                <div class="mb-6 rounded-xl border border-border bg-card p-8 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-foreground">{{ form.title }}</h1>
                            <p v-if="form.description" class="mt-3 text-lg text-muted-foreground">{{ form.description }}</p>
                        </div>

                        <!-- Creator Actions -->
                        <div v-if="user && form.user_id === user.id" class="ml-6 flex items-center gap-3">
                            <Button @click="router.visit(route('answers.show', form.id));" variant="outline" size="sm"> View Answers</Button>
                            <Button @click="router.visit(route('forms.edit', form.id));" variant="outline" size="sm"> Edit</Button>
                            <Button @click="showDeleteConfirm = true" variant="destructive" size="sm"> Delete</Button>

                            <!-- Share Popover -->
                            <div class="relative">
                                <Button @click="showSharePopover = !showSharePopover" size="sm"> Share</Button>
                                <div
                                    v-if="showSharePopover"
                                    class="ring-opacity-5 absolute right-0 z-10 mt-2 w-80 rounded-lg border border-border bg-card shadow-lg ring-1 ring-black/10"
                                >
                                    <div class="p-6">
                                        <h3 class="mb-2 text-lg font-semibold text-foreground">Share form</h3>
                                        <p class="mb-4 text-sm text-muted-foreground">Anyone with the link can view and submit this form.</p>
                                        <div class="flex gap-2">
                                            <Input type="text" :model-value="formLink" readonly class="flex-1 bg-muted" />
                                            <Button @click="copyLink" variant="outline" size="sm" class="shrink-0">
                                                {{ copyStatus }}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Meta Info -->
                    <div class="mt-6 flex items-center gap-6 text-sm text-muted-foreground">
                        <span v-if="form.created_at"> Created: {{ new Date(form.created_at).toLocaleDateString() }} </span>
                        <span v-if="form.updated_at"> Updated: {{ new Date(form.updated_at).toLocaleDateString() }} </span>
                    </div>
                </div>

                <!-- Form Content -->
                <form @submit.prevent="submitForm" v-if="!submitted">
                    <div class="rounded-xl border border-border bg-card p-8 shadow-sm">
                        <div v-if="questions && questions.length > 0" class="space-y-8">
                            <div
                                v-for="(question, index) in questions"
                                :key="question.id"
                                class="border-b border-border/60 pb-8 last:border-b-0 last:pb-0"
                            >
                                <div class="mb-6">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                                        >
                                            {{ index + 1 }}
                                        </div>
                                        <div class="flex-1">
                                            <Label class="mb-4 block text-lg font-semibold text-foreground">
                                                {{ question.title }}
                                                <span v-if="question.required" class="ml-1 text-red-500">*</span>
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
                                                <div v-if="getOptions(question.options).items.length > 0" class="space-y-3">
                                                    <div
                                                        v-for="(option, optionIndex) in getOptions(question.options).items"
                                                        :key="optionIndex"
                                                        class="flex items-center gap-3"
                                                    >
                                                        <input
                                                            :type="getOptions(question.options).multiple ? 'checkbox' : 'radio'"
                                                            :id="`q${index}_option${optionIndex}`"
                                                            :name="`question_${question.id}`"
                                                            :value="option"
                                                            v-model="answers[question.id]"
                                                        class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                                            :required="
                                                                question.required && !getOptions(question.options).multiple && !answers[question.id]
                                                            "
                                                        />
                                                        <Label :for="`q${index}_option${optionIndex}`" class="cursor-pointer font-normal text-foreground">
                                                            {{ option }}
                                                        </Label>
                                                    </div>
                                                </div>
                                                <div v-else class="italic text-muted-foreground/80">No options available</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 text-center">
                            <p class="text-lg text-muted-foreground">This form has no questions.</p>
                        </div>

                        <div v-if="questions && questions.length > 0" class="mt-8 flex items-center justify-between">
                            <span v-if="submissionStatus" class="text-sm text-muted-foreground">{{ submissionStatus }}</span>
                            <div class="ml-auto">
                                <Button type="submit" size="lg"> Submit</Button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Success State -->
                <div v-else class="rounded-xl border border-border bg-card p-12 text-center shadow-sm">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 dark:bg-emerald-500/25">
                        <svg class="h-8 w-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <svg class="h-6 w-6 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
