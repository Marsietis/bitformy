<script setup lang="js">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import * as openpgp from 'openpgp';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const props = defineProps({
    form: Object,
    questions: Array,
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

// TODO fix this shit
const initializeAnswers = () =>
    props.questions.reduce((acc, question) => {
        const options = getOptions(question.options);
        if (question.type === 'choice' && options.multiple) {
            acc[question.id] = [];
        } else {
            acc[question.id] = null;
        }
        return acc;
    }, {});

const answers = ref(initializeAnswers());

const submissionStatus = ref('');
const submitted = ref(false);

const breadcrumbs = computed(() => {
    if (!user.value) {
        return [];
    }
    return [
        {
            title: 'Forms',
            href: '/dashboard',
        },
        {
            title: props.form.title,
            href: `/form/${props.form.id}`,
        },
    ];
});

const isFormCreator = computed(() => {
    return user.value && props.form.user_id === user.value.id;
});

const showSharePopover = ref(false);
const formLink = computed(() => {
    return `${window.location.origin}/form/${props.form.id}`;
});
const copyStatus = ref('Copy');

const layout = computed(() => (user.value ? AppLayout : GuestLayout));

const copyLink = () => {
    if (formLink.value) {
        navigator.clipboard.writeText(formLink.value).then(() => {
            copyStatus.value = 'Copied!';
            setTimeout(() => {
                copyStatus.value = 'Copy';
                showSharePopover.value = false;
            }, 2000);
        });
    }
};

const submitForm = async () => {
    for (const question of props.questions) {
        if (question.required) {
            const answer = answers.value[question.id];
            if (answer === null || answer === '' || (Array.isArray(answer) && answer.length === 0)) {
                submissionStatus.value = `Error: The question "${question.title}" is required.`;
                return;
            }
        }
    }

    if (!props.form.user.public_key) {
        submissionStatus.value = 'Error: Form creator has no public key.';
        return;
    }

    submissionStatus.value = 'Encrypting and submitting...';

    try {
        // TODO and fix this
        const publicKey = await openpgp.readKey({ armoredKey: props.form.user.public_key });

        const encryptedAnswers = await Promise.all(
            props.questions
                .map((question) => {
                    const answer = answers.value[question.id];
                    if (answer === undefined || answer === null || answer === '' || (Array.isArray(answer) && answer.length === 0)) {
                        return null;
                    }
                    return { question, answer };
                })
                .filter(Boolean)
                .map(async ({ question, answer }) => {
                    const answerString = typeof answer === 'object' ? JSON.stringify(answer) : String(answer);

                    const message = await openpgp.createMessage({ text: answerString });
                    const encrypted = await openpgp.encrypt({
                        message,
                        encryptionKeys: publicKey,
                    });

                    return {
                        question_id: question.id,
                        answer: encrypted,
                    };
                }),
        );

        router.post(
            `/form/${props.form.id}/submit`,
            { answers: encryptedAnswers },
            {
                onSuccess: () => {
                    submitted.value = true;
                },
                onError: (errors) => {
                    console.error('Submission error:', errors);
                    submissionStatus.value = 'An error occurred during submission.';
                },
            },
        );
    } catch (e) {
        console.error('Encryption failed', e);
        submissionStatus.value = 'Error: Could not encrypt answers.';
    }
};

const submitAnotherResponse = () => {
    answers.value = initializeAnswers();
    submissionStatus.value = '';
    submitted.value = false;
};

const showDeleteConfirm = ref(false);

const editForm = () => {
    router.visit(`/form/${props.form.id}/edit`);
};

const deleteForm = () => {
    router.delete(`/form/${props.form.id}`, {
        onSuccess: () => {
            router.visit('/dashboard');
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
            submissionStatus.value = 'An error occurred while deleting the form.';
        },
    });
    showDeleteConfirm.value = false;
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
};

const viewAnswers = () => {
    router.visit(`/form/${props.form.id}/answers`);
};
</script>

<template>
    <Head :title="form.title" />

    <component :is="layout" :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-4xl p-6">
                <!-- Form Header Card -->
                <div class="mb-6 rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900">{{ form.title }}</h1>
                            <p v-if="form.description" class="mt-3 text-lg text-gray-600">{{ form.description }}</p>
                        </div>

                        <!-- Creator Actions -->
                        <div v-if="isFormCreator" class="ml-6 flex items-center gap-3">
                            <Button @click="viewAnswers" variant="outline" size="sm"> View Answers</Button>
                            <Button @click="editForm" variant="outline" size="sm"> Edit</Button>
                            <Button @click="showDeleteConfirm = true" variant="destructive" size="sm"> Delete</Button>

                            <!-- Share Popover -->
                            <div class="relative">
                                <Button @click="showSharePopover = !showSharePopover" size="sm"> Share</Button>
                                <div
                                    v-if="showSharePopover"
                                    class="ring-opacity-5 absolute right-0 z-10 mt-2 w-80 rounded-lg border border-gray-200 bg-white shadow-lg ring-1 ring-black"
                                >
                                    <div class="p-6">
                                        <h3 class="mb-2 text-lg font-semibold text-gray-900">Share form</h3>
                                        <p class="mb-4 text-sm text-gray-600">Anyone with the link can view and submit this form.</p>
                                        <div class="flex gap-2">
                                            <Input type="text" :model-value="formLink" readonly class="flex-1 bg-gray-50" />
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
                    <div class="mt-6 flex items-center gap-6 text-sm text-gray-500">
                        <span v-if="form.created_at"> Created: {{ new Date(form.created_at).toLocaleDateString() }} </span>
                        <span v-if="form.updated_at"> Updated: {{ new Date(form.updated_at).toLocaleDateString() }} </span>
                    </div>
                </div>

                <!-- Form Content -->
                <form @submit.prevent="submitForm" v-if="!submitted">
                    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
                        <div v-if="questions && questions.length > 0" class="space-y-8">
                            <div
                                v-for="(question, index) in questions"
                                :key="question.id"
                                class="border-b border-gray-100 pb-8 last:border-b-0 last:pb-0"
                            >
                                <div class="mb-6">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                                        >
                                            {{ index + 1 }}
                                        </div>
                                        <div class="flex-1">
                                            <Label class="mb-4 block text-lg font-semibold text-gray-900">
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
                                                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                            :required="
                                                                question.required && !getOptions(question.options).multiple && !answers[question.id]
                                                            "
                                                        />
                                                        <Label
                                                            :for="`q${index}_option${optionIndex}`"
                                                            class="cursor-pointer font-normal text-gray-700"
                                                        >
                                                            {{ option }}
                                                        </Label>
                                                    </div>
                                                </div>
                                                <div v-else class="text-gray-500 italic">No options available</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 text-center">
                            <p class="text-lg text-gray-600">This form has no questions.</p>
                        </div>

                        <div v-if="questions && questions.length > 0" class="mt-8 flex items-center justify-between">
                            <span v-if="submissionStatus" class="text-sm text-gray-600">{{ submissionStatus }}</span>
                            <div class="ml-auto">
                                <Button type="submit" size="lg"> Submit</Button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Success State -->
                <div v-else class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="mb-3 text-2xl font-bold text-gray-900">Form submitted successfully</h2>
                    <p class="mb-8 text-gray-600">Your response has been recorded and encrypted.</p>
                    <Button @click="submitAnotherResponse" variant="outline" size="lg"> Submit another response</Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 z-50 flex h-full w-full items-center justify-center overflow-y-auto bg-gray-600/50 p-4 backdrop-blur-sm"
        >
            <div class="relative mx-auto w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl">
                <div class="p-6">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.124 16.5c-.77.833.192 2.5 1.732 2.5z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-center text-lg font-semibold text-gray-900">Delete Form</h3>
                    <p class="mb-6 text-center text-sm text-gray-600">
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
