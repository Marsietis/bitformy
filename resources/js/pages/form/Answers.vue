<script setup lang="js">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import * as openpgp from 'openpgp';
import * as XLSX from 'xlsx';

const props = defineProps({
    answers: Array,
    questions: Array,
    form: Object,
});

const privateKeyArmored = ref(null);
const decryptedSubmissions = ref([]);
const isLoading = ref(true);
const errorMessages = ref([]);

// Export options
const exportFormat = ref('csv');
const exportType = ref('decrypted');
const showExportOptions = ref(false);

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

// Export functions
const downloadFile = (content, filename, mimeType) => {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

const getExportData = () => {
    if (exportType.value === 'decrypted') {
        // Format decrypted data for export
        const data = [];
        decryptedSubmissions.value.forEach(submission => {
            submission.answers.forEach(answer => {
                data.push({
                    'Submission ID': submission.id,
                    'Submitted At': submission.submittedAt,
                    'Question': answer.questionTitle,
                    'Answer': answer.answer
                });
            });
        });
        return data;
    } else {
        // Format encrypted data for export
        const data = [];
        props.answers.forEach(answer => {
            data.push({
                'Submission ID': answer.submission_id,
                'Question ID': answer.question_id,
                'Question': getQuestionTitle(answer.question_id),
                'Encrypted Answer': answer.answer,
                'Created At': answer.created_at,
                'Updated At': answer.updated_at
            });
        });
        return data;
    }
};

const exportToCSV = () => {
    const data = getExportData();
    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => 
            headers.map(header => {
                const cell = row[header] || '';
                // Escape quotes and wrap in quotes if contains comma, quote, or newline
                return /[",\n\r]/.test(cell) 
                    ? `"${cell.replace(/"/g, '""')}"` 
                    : cell;
            }).join(',')
        )
    ].join('\n');

    const filename = `${props.form.title}_answers_${exportType.value}.csv`;
    downloadFile(csvContent, filename, 'text/csv');
};

const exportToXLS = () => {
    const data = getExportData();
    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Answers');
    
    const filename = `${props.form.title}_answers_${exportType.value}.xlsx`;
    XLSX.writeFile(wb, filename);
};

const exportToJSON = () => {
    const data = exportType.value === 'decrypted' 
        ? decryptedSubmissions.value 
        : props.answers;
    
    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const jsonContent = JSON.stringify(data, null, 2);
    const filename = `${props.form.title}_answers_${exportType.value}.json`;
    downloadFile(jsonContent, filename, 'application/json');
};

const handleExport = () => {
    if (exportType.value === 'decrypted' && decryptedSubmissions.value.length === 0) {
        alert('No decrypted data available. Please wait for decryption to complete.');
        return;
    }

    switch (exportFormat.value) {
        case 'csv':
            exportToCSV();
            break;
        case 'xls':
            exportToXLS();
            break;
        case 'json':
            exportToJSON();
            break;
        default:
            alert('Unknown export format');
    }
    
    showExportOptions.value = false;
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

// Close export options when clicking outside
const closeExportOptions = (event) => {
    if (!event.target.closest('.export-dropdown')) {
        showExportOptions.value = false;
    }
};

onMounted(() => {
    processAnswers();
    document.addEventListener('click', closeExportOptions);
});

// Clean up event listener
onUnmounted(() => {
    document.removeEventListener('click', closeExportOptions);
});

</script>

<template>

    <Head :title="`Answers for ${form.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-5xl p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Answers for {{ form.title }}</h1>
                            <p v-if="form.description" class="mt-3 text-gray-600">{{ form.description }}</p>
                        </div>
                        <div class="relative export-dropdown">
                            <button 
                                @click="showExportOptions = !showExportOptions"
                                :disabled="isLoading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export Answers
                            </button>
                            
                            <!-- Export Options Dropdown -->
                            <div v-if="showExportOptions" class="absolute right-0 top-full mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                <div class="p-4">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Options</h3>
                                    
                                    <!-- Format Selection -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input v-model="exportFormat" type="radio" value="csv" class="mr-2">
                                                <span>CSV (.csv)</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input v-model="exportFormat" type="radio" value="xls" class="mr-2">
                                                <span>Excel (.xlsx)</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input v-model="exportFormat" type="radio" value="json" class="mr-2">
                                                <span>JSON (.json)</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Content Type Selection -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input v-model="exportType" type="radio" value="decrypted" class="mr-2">
                                                <span>Decrypted answers (human readable)</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input v-model="exportType" type="radio" value="encrypted" class="mr-2">
                                                <span>Encrypted answers (raw data)</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex gap-2">
                                        <button 
                                            @click="handleExport"
                                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex-1"
                                        >
                                            Download
                                        </button>
                                        <button 
                                            @click="showExportOptions = false"
                                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 flex-1"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
