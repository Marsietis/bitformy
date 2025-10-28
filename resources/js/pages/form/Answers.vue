<script setup lang="js">
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import * as openpgp from 'openpgp';
import { computed, onMounted, ref } from 'vue';
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

const getQuestionTitle = (questionId) => {
    const question = props.questions.find((question) => question.id === questionId);
    return question.title;
};

// Export functions
const downloadFile = (content, filename, mimeType) => {
    // Create a blob from the content
    const blob = new Blob([content], { type: mimeType });

    // Create a temporary URL for the blob
    const url = URL.createObjectURL(blob);

    // Create a hidden link element
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;

    // Add link to page, click it, then remove it
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Clean up the temporary URL
    URL.revokeObjectURL(url);
};
const getExportData = () => {
    let data = [];

    if (exportType.value === 'decrypted') {
        // Export decrypted answers
        for (let i = 0; i < decryptedSubmissions.value.length; i++) {
            let submission = decryptedSubmissions.value[i];

            for (let j = 0; j < submission.answers.length; j++) {
                let answer = submission.answers[j];

                data.push({
                    'Submission ID': submission.id,
                    'Submitted At': submission.submittedAt,
                    Question: answer.questionTitle,
                    Answer: answer.answer,
                });
            }
        }
    } else {
        // Export encrypted answers
        for (let i = 0; i < props.answers.length; i++) {
            let answer = props.answers[i];

            data.push({
                'Submission ID': answer.submission_id,
                'Question ID': answer.question_id,
                Question: getQuestionTitle(answer.question_id),
                'Encrypted Answer': answer.answer,
                'Created At': answer.created_at,
                'Updated At': answer.updated_at,
            });
        }
    }

    return data;
};
const exportToCSV = () => {
    const data = getExportData();

    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    // Get the column headers from the first row
    const headers = Object.keys(data[0]);

    let csvLines = [];

    // Add header row
    csvLines.push(headers.join(','));

    // Add data rows
    for (let i = 0; i < data.length; i++) {
        let row = data[i];
        let rowValues = [];

        for (let j = 0; j < headers.length; j++) {
            let header = headers[j];
            let cell = row[header] || '';

            // Check if cell needs quotes (contains comma, quote, or newline)
            if (cell.includes(',') || cell.includes('"') || cell.includes('\n') || cell.includes('\r')) {
                // Escape quotes by doubling them
                cell = cell.replace(/"/g, '""');
                // Wrap in quotes
                cell = '"' + cell + '"';
            }

            rowValues.push(cell);
        }

        csvLines.push(rowValues.join(','));
    }

    // Join all lines with newlines
    const csvContent = csvLines.join('\n');

    const filename = props.form.title + '_answers_' + exportType.value + '.csv';

    downloadFile(csvContent, filename, 'text/csv');
};

const exportToXLS = () => {
    const data = getExportData();

    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const worksheet = XLSX.utils.json_to_sheet(data);

    const workbook = XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Answers');

    const filename = props.form.title + '_answers_' + exportType.value + '.xlsx';

    XLSX.writeFile(workbook, filename);
};
const exportToJSON = () => {
    let data;

    if (exportType.value === 'decrypted') {
        data = decryptedSubmissions.value;
    } else {
        data = props.answers;
    }

    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const jsonContent = JSON.stringify(data, null, 2);
    const filename = props.form.title + '_answers_' + exportType.value + '.json';
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
        const message = await openpgp.readMessage({
            armoredMessage: encryptedAnswer,
        });

        const decryptResult = await openpgp.decrypt({
            message: message,
            decryptionKeys: privateKey,
        });

        return decryptResult.data;
    } catch (error) {
        console.error('Decryption failed for an answer:', error);
        errorMessages.value.push('Failed to decrypt an answer: ' + error.message);
        return '[Decryption Failed]';
    }
};

const processAnswers = async () => {
    isLoading.value = true;
    errorMessages.value = [];

    const privateKeyFromStorage = sessionStorage.getItem('privateKey');

    if (!privateKeyFromStorage) {
        errorMessages.value.push('Private key not found in session storage. Please log in again.');
        isLoading.value = false;
        return;
    }

    privateKeyArmored.value = privateKeyFromStorage;

    let privateKey;
    try {
        privateKey = await openpgp.readPrivateKey({
            armoredKey: privateKeyArmored.value,
        });
    } catch (error) {
        const errorMessage =
            'Unable to read your encryption key. This usually happens when your login session has expired. Please try logging out and logging back in to fix this issue.';
        errorMessages.value.push(errorMessage);
        isLoading.value = false;
        return;
    }

    // Group answers by submission ID
    // Create a map to store submissions
    const submissionsMap = new Map();

    for (let i = 0; i < props.answers.length; i++) {
        let answer = props.answers[i];
        let submissionId = answer.submission_id;

        if (!submissionsMap.has(submissionId)) {
            submissionsMap.set(submissionId, []);
        }

        let answersArray = submissionsMap.get(submissionId);
        answersArray.push(answer);
    }

    const processedSubmissions = [];
    for (const [submissionId, answersInSubmission] of submissionsMap.entries()) {
        const decryptedAnswersForSubmission = [];
        let submissionTime = 'N/A';

        for (let i = 0; i < answersInSubmission.length; i++) {
            let encryptedAnswer = answersInSubmission[i];

            if (encryptedAnswer.answer) {
                const decryptedText = await decryptAnswer(encryptedAnswer.answer, privateKey);

                decryptedAnswersForSubmission.push({
                    questionTitle: getQuestionTitle(encryptedAnswer.question_id),
                    answer: decryptedText,
                });
            }

            // Get submission time from the first answer
            if (answersInSubmission.length > 0) {
                submissionTime = new Date(encryptedAnswer.created_at).toLocaleString();
            }
        }

        processedSubmissions.push({
            id: submissionId,
            answers: decryptedAnswersForSubmission,
            submittedAt: submissionTime,
        });
    }

    // Sort submissions by date
    processedSubmissions.sort(function (a, b) {
        let dateA = new Date(a.submittedAt).getTime();
        let dateB = new Date(b.submittedAt).getTime();
        return dateB - dateA; // Reverse order for newest first
    });

    decryptedSubmissions.value = processedSubmissions;

    isLoading.value = false;
};

onMounted(() => {
    processAnswers();
});
</script>

<template>
    <Head :title="`Answers for ${form.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 dark:bg-background">
            <div class="mx-auto max-w-5xl p-6">
                <!-- Header Card -->
                <div class="mb-6 rounded-xl border border-border bg-card p-8 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-foreground">Answers for {{ form.title }}</h1>
                            <p v-if="form.description" class="mt-3 text-lg text-muted-foreground">{{ form.description }}</p>
                        </div>

                        <!-- Export Dropdown -->
                        <div class="export-dropdown relative">
                            <Button @click="showExportOptions = !showExportOptions" :disabled="isLoading" variant="outline" class="gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Export Answers
                            </Button>

                            <!-- Export Options Modal -->
                            <div
                                v-if="showExportOptions"
                                class="absolute top-full right-0 z-10 mt-2 w-80 rounded-xl border border-border bg-card shadow-lg"
                            >
                                <div class="p-6">
                                    <h3 class="mb-4 text-lg font-semibold text-foreground">Export Options</h3>

                                    <!-- Format Selection -->
                                    <div class="mb-6">
                                        <Label class="mb-3 block text-sm font-medium text-foreground">Export Format</Label>
                                        <div class="space-y-2">
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-muted">
                                                <input
                                                    v-model="exportFormat"
                                                    type="radio"
                                                    value="csv"
                                                    class="h-4 w-4 border-border text-primary focus:ring-primary"
                                                />
                                                <span class="text-sm text-foreground">CSV (.csv)</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-muted">
                                                <input
                                                    v-model="exportFormat"
                                                    type="radio"
                                                    value="xls"
                                                    class="h-4 w-4 border-border text-primary focus:ring-primary"
                                                />
                                                <span class="text-sm text-foreground">Excel (.xlsx)</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-muted">
                                                <input
                                                    v-model="exportFormat"
                                                    type="radio"
                                                    value="json"
                                                    class="h-4 w-4 border-border text-primary focus:ring-primary"
                                                />
                                                <span class="text-sm text-foreground">JSON (.json)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Content Type Selection -->
                                    <div class="mb-6">
                                        <Label class="mb-3 block text-sm font-medium text-foreground">Content Type</Label>
                                        <div class="space-y-2">
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-muted">
                                                <input
                                                    v-model="exportType"
                                                    type="radio"
                                                    value="decrypted"
                                                    class="h-4 w-4 border-border text-primary focus:ring-primary"
                                                />
                                                <span class="text-sm text-foreground">Decrypted answers (human readable)</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-muted">
                                                <input
                                                    v-model="exportType"
                                                    type="radio"
                                                    value="encrypted"
                                                    class="h-4 w-4 border-border text-primary focus:ring-primary"
                                                />
                                                <span class="text-sm text-foreground">Encrypted answers (raw data)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-3">
                                        <Button @click="handleExport" class="flex-1"> Download</Button>
                                        <Button @click="showExportOptions = false" variant="outline" class="flex-1"> Cancel </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="rounded-xl border border-border bg-card p-12 text-center shadow-sm">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                        <svg class="h-8 w-8 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                    </div>
                    <p class="text-lg text-muted-foreground">Loading and decrypting answers...</p>
                </div>

                <!-- Error Messages -->
                <div v-if="errorMessages.length > 0" class="mb-6 rounded-xl border border-destructive/40 bg-destructive/10 p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-destructive" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-destructive">Error occurred</h3>
                            <div class="mt-2 text-sm text-destructive">
                                <ul class="list-inside list-disc space-y-1">
                                    <li v-for="(msg, index) in errorMessages" :key="index">{{ msg }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Submissions State -->
                <div
                    v-if="!isLoading && decryptedSubmissions.length === 0 && errorMessages.length === 0"
                    class="rounded-xl border border-border bg-card p-12 text-center shadow-sm"
                >
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <svg class="h-8 w-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-foreground">No submissions yet</h3>
                    <p class="text-muted-foreground">No submissions found for this form yet.</p>
                </div>

                <!-- Submissions List -->
                <div v-if="!isLoading && decryptedSubmissions.length > 0" class="space-y-6">
                    <div
                        v-for="submission in decryptedSubmissions"
                        :key="submission.id"
                        class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
                    >
                        <div class="border-b border-border/60 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-foreground">Submission ID: {{ submission.id }}</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">Submitted at: {{ submission.submittedAt }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-6">
                                <div
                                    v-for="(decryptedAnswer, index) in submission.answers"
                                    :key="index"
                                    class="border-b border-border/60 pb-6 last:border-b-0 last:pb-0"
                                >
                                    <Label class="mb-2 block text-sm font-medium text-foreground">
                                        {{ decryptedAnswer.questionTitle }}
                                    </Label>
                                    <div class="rounded-lg bg-muted/40 p-4">
                                        <p class="whitespace-pre-wrap text-foreground">{{ decryptedAnswer.answer }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
