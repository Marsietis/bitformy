<script setup lang="js">
    import AppLayout from '@/layouts/AppLayout.vue';
    import GuestLayout from '@/layouts/GuestLayout.vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import * as openpgp from 'openpgp';
    import { router } from '@inertiajs/vue3';

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const props = defineProps({
        form: Object,
        questions: Array,
    });

    const getOptions = (optionsString) => {
        if (!optionsString) return { items: [], multiple: false };
        return JSON.parse(optionsString);
    };

    const initializeAnswers = () => props.questions.reduce((acc, question) => {
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
        if (typeof window !== 'undefined') {
            return `${window.location.origin}/form/${props.form.id}`;
        }
        return '';
    });
    const copyStatus = ref('Copy');

    const layout = computed(() => (user.value ? AppLayout : GuestLayout));

    const copyLink = () => {
        if (formLink.value) {
            navigator.clipboard.writeText(formLink.value)
                .then(() => {
                    copyStatus.value = 'Copied!';
                    setTimeout(() => {
                        copyStatus.value = 'Copy';
                        showSharePopover.value = false;
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                    copyStatus.value = 'Failed';
                    setTimeout(() => {
                        copyStatus.value = 'Copy';
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

        if (!props.form.user?.public_key) {
            submissionStatus.value = 'Error: Form creator has no public key.';
            return;
        }

        submissionStatus.value = 'Encrypting and submitting...';

        try {
            const publicKey = await openpgp.readKey({ armoredKey: props.form.user.public_key });

            const encryptedAnswers = await Promise.all(
                props.questions
                    .map(question => {
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
                    })
            );

            router.post(`/form/${props.form.id}/submit`, { answers: encryptedAnswers }, {
                onSuccess: () => {
                    submitted.value = true;
                },
                onError: (errors) => {
                    console.error('Submission error:', errors);
                    submissionStatus.value = 'An error occurred during submission.';
                },
            });

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
            <div class="mx-auto max-w-5xl p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ form.title }}</h1>
                            <p v-if="form.description" class="mt-3 text-gray-600">{{ form.description }}</p>
                        </div>
                        <div v-if="isFormCreator" class="flex items-center gap-2">
                            <button @click="viewAnswers" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                View Answers
                            </button>
                            <button @click="editForm" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Edit
                            </button>
                            <button @click="showDeleteConfirm = true" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Delete
                            </button>
                            <div class="relative">
                                <button @click="showSharePopover = !showSharePopover" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    Share
                                </button>
                                <div v-if="showSharePopover" class="absolute right-0 mt-2 w-72 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                    <div class="p-4">
                                        <p class="text-sm font-medium text-gray-900">Share form</p>
                                        <p class="mt-1 text-sm text-gray-500">Anyone with the link can view and submit this form.</p>
                                        <div class="mt-4 flex rounded-md shadow-sm">
                                            <input type="text" :value="formLink" readonly class="block w-full flex-1 rounded-none rounded-l-md border-gray-300 bg-gray-50 focus:border-primary focus:ring-primary sm:text-sm">
                                            <button @click="copyLink" type="button" class="relative -ml-px inline-flex items-center space-x-2 rounded-r-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                                <span>{{ copyStatus }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-4 text-sm text-muted-foreground">
                        <span v-if="form.created_at">
                            Created: {{ new Date(form.created_at).toLocaleDateString() }}
                        </span>
                        <span v-if="form.updated_at">
                            Updated: {{ new Date(form.updated_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submitForm" v-if="!submitted">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div v-if="questions && questions.length > 0" class="space-y-8">
                            <div
                                v-for="(question, index) in questions"
                                :key="question.id"
                                class="border-b border-gray-200 pb-6 last:border-b-0"
                            >
                                <div class="mb-4">
                                    <div class="flex items-start">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-primary/10 text-primary rounded-full mr-3 font-medium">
                                            {{ index + 1 }}
                                        </span>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">
                                                {{ question.title }}
                                                <span v-if="question.required" class="text-red-500 ml-1">*</span>
                                            </h3>
                                        </div>
                                    </div>

                                    <!-- Text question type -->
                                    <div v-if="question.type === 'text'" class="mt-4 pl-11">
                                        <input
                                            type="text"
                                            :required="question.required"
                                            v-model="answers[question.id]"
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors duration-200"
                                            :placeholder="question.required ? 'Your answer (required)' : 'Your answer'"
                                        />
                                    </div>

                                    <!-- Choice question type -->
                                    <div v-else-if="question.type === 'choice'" class="mt-4 pl-11">
                                        <div v-if="getOptions(question.options).items.length > 0" class="space-y-3">
                                            <div
                                                v-for="(option, optionIndex) in getOptions(question.options).items"
                                                :key="optionIndex"
                                                class="flex items-center"
                                            >
                                                <input
                                                    :type="getOptions(question.options).multiple ? 'checkbox' : 'radio'"
                                                    :id="`q${index}_option${optionIndex}`"
                                                    :name="`question_${question.id}`"
                                                    :value="option"
                                                    v-model="answers[question.id]"
                                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                                                    :required="question.required && (!getOptions(question.options).multiple && !answers[question.id])"
                                                />
                                                <label
                                                    :for="`q${index}_option${optionIndex}`"
                                                    class="ml-2 block text-gray-700"
                                                >
                                                    {{ option }}
                                                </label>
                                            </div>
                                        </div>
                                        <div v-else class="text-gray-500 italic">No options available</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <p class="text-gray-600">This form has no questions.</p>
                        </div>

                        <div v-if="questions && questions.length > 0" class="mt-8 flex justify-end items-center gap-4">
                            <span v-if="submissionStatus" class="text-sm text-gray-600">{{ submissionStatus }}</span>
                            <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Submit
                            </button>
                        </div>
                    </div>
                </form>
                <div v-else class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-2xl font-bold text-gray-900">Form submitted</h2>
                    <p class="mt-2 text-gray-600">Your response has been recorded.</p>
                    <button @click="submitAnotherResponse" type="button" class="mt-6 inline-flex items-center px-6 py-2.5 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Submit another response
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.124 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Form</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to delete "{{ form.title }}"? This action cannot be undone.
                        </p>
                    </div>
                    <div class="flex justify-center gap-4 px-4 py-3">
                        <button @click="cancelDelete" type="button" class="px-4 py-2 bg-white text-gray-500 border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Cancel
                        </button>
                        <button @click="deleteForm" type="button" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>