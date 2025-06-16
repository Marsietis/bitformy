<script setup lang="js">
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import * as openpgp from 'openpgp';

const props = defineProps({
    answers: Array,
    questions: Array,
    form: Object,
});

const privateKeyArmored = ref(null);
const decryptedSubmissions = ref([]);
const isLoading = ref(true);
const errorMessages = ref([]);

const breadcrumbs = computed(() => [
    {
        title: 'Forms',
        href: '/dashboard',
    },
    {
        title: props.form.title,
        href: `/form/${props.form.id}`,
    },
    {
        title: 'Answers',
        href: `/form/${props.form.id}/answers`,
    },
]);

// Get the title of a question by its ID
const getQuestionTitle = (questionId) => {
    const question = props.questions.find(question => question.id === questionId);
    return question?.title ?? 'Unknown Question';
};

const decryptAnswer = async (encryptedAnswer, privateKey) => {
    try {
        const message = await openpgp.readMessage({ armoredMessage: encryptedAnswer });
        const { data: decryptedData } = await openpgp.decrypt({
            message,
            decryptionKeys: privateKey,
        });
        return decryptedData;
    } catch (e) {
        console.error('Decryption failed for an answer:', e);
        errorMessages.value.push(`Failed to decrypt an answer: ${e.message}`);
        return '[Decryption Failed]';
    }
};

const processAnswers = async () => {
    isLoading.value = true;
    errorMessages.value = [];
    const pkArmored = sessionStorage.getItem('privateKey');
    if (!pkArmored) {
        errorMessages.value.push('Private key not found in session storage. Please log in again.');
        isLoading.value = false;
        return;
    }
    privateKeyArmored.value = pkArmored;

    let privateKey;
    try {
        privateKey = await openpgp.readPrivateKey({ armoredKey: privateKeyArmored.value });
    } catch (e) {
        const errorMessage = 'Unable to read your encryption key. This usually happens when your login session has expired. Please try logging out and logging back in to fix this issue.';
        errorMessages.value.push(errorMessage);
        isLoading.value = false;
        return;
    }

    // Group answers by submission_id
    const submissionsMap = new Map();
    for (const answer of props.answers) {
        if (!submissionsMap.has(answer.submission_id)) {
            submissionsMap.set(answer.submission_id, []);
        }
        submissionsMap.get(answer.submission_id)?.push(answer);
    }

    const processedSubmissions = [];
    for (const [submissionId, answersInSubmission] of submissionsMap.entries()) {
        const decryptedAnswersForSubmission = [];
        let submissionTime = 'N/A';

        for (const encAnswer of answersInSubmission) {
            if (encAnswer.answer) {
                const decryptedText = await decryptAnswer(encAnswer.answer, privateKey);
                decryptedAnswersForSubmission.push({
                    questionTitle: getQuestionTitle(encAnswer.question_id),
                    answer: decryptedText,
                });
            }
            if (answersInSubmission.length > 0) {
                submissionTime = new Date(encAnswer.created_at).toLocaleString();
            }
        }
        processedSubmissions.push({
            id: submissionId,
            answers: decryptedAnswersForSubmission,
            submittedAt: submissionTime,
        });
    }

    decryptedSubmissions.value = processedSubmissions.sort((a, b) => new Date(b.submittedAt).getTime() - new Date(a.submittedAt).getTime());
    isLoading.value = false;
};

onMounted(() => {
    processAnswers();
});

</script>

<template>

    <Head :title="`Answers for ${form.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-5xl p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Answers for {{ form.title }}</h1>
                    <p v-if="form.description" class="mt-3 text-gray-600">{{ form.description }}</p>
                </div>

                <div v-if="isLoading" class="text-center py-10">
                    <p class="text-lg text-gray-600">Loading and decrypting answers...</p>
                </div>

                <div v-if="errorMessages.length > 0"
                    class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <ul>
                        <li v-for="(msg, index) in errorMessages" :key="index">{{ msg }}</li>
                    </ul>
                </div>

                <div v-if="!isLoading && decryptedSubmissions.length === 0 && errorMessages.length === 0"
                    class="text-center py-10 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-lg text-gray-600">No submissions found for this form yet.</p>
                </div>

                <div v-if="!isLoading && decryptedSubmissions.length > 0" class="space-y-8">
                    <div v-for="submission in decryptedSubmissions" :key="submission.id"
                        class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-800">Submission ID: {{ submission.id.substring(0,
                                8) }}...</h2>
                            <p class="text-sm text-gray-500">Submitted at: {{ submission.submittedAt }}</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <div v-for="(decryptedAnswer, index) in submission.answers" :key="index"
                                class="border-b border-gray-100 pb-4 last:border-b-0 last:pb-0">
                                <h3 class="text-md font-medium text-gray-700">{{ decryptedAnswer.questionTitle }}</h3>
                                <p class="mt-1 text-gray-600 whitespace-pre-wrap">{{ decryptedAnswer.answer }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
