<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'created_at',
        'updated_at',
    ];

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function generateSubmissionId(): string
    {
        return Str::uuid()->toString();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function addQuestions(array $questions): void
    {
        foreach ($questions as $order => $questionData) {
            $this->questions()->create([
                'title' => $questionData['title'],
                'type' => $questionData['type'],
                'required' => $questionData['required'],
                'order' => $order,
                'options' => $this->formatOptions($questionData['options'] ?? [], $questionData['multipleChoice'] ?? false),
                'allow_multiple' => $questionData['multipleChoice'],
            ]);
        }
    }

    public function getFormattedQuestions(): array
    {
        return $this->questions()
            ->orderBy('order')
            ->get()
            ->map(fn ($question) => $question->toFormattedArray())
            ->toArray();
    }

    public function updateQuestions(array $questions): void
    {
        $currentQuestionIds = $this->questions()->pluck('id')->toArray();
        $processedQuestionIds = [];

        foreach ($questions as $order => $questionData) {
            $optionsToSave = null;

            if ($questionData['type'] === 'choice') {
                $optionsToSave = $this->formatOptions(
                    $questionData['options'] ?? [],
                    $questionData['multipleChoice'] ?? false
                );
            }

            if (isset($questionData['id']) && in_array($questionData['id'], $currentQuestionIds)) {
                $question = $this->questions()->find($questionData['id']);
                $question->update([
                    'title' => $questionData['title'],
                    'type' => $questionData['type'],
                    'options' => $optionsToSave,
                    'required' => $questionData['required'] ?? false,
                    'order' => $order,
                ]);

                $processedQuestionIds[] = $questionData['id'];
            } else {
                $question = $this->questions()->create([
                    'title' => $questionData['title'],
                    'type' => $questionData['type'],
                    'options' => $optionsToSave,
                    'required' => $questionData['required'] ?? false,
                    'order' => $order,
                ]);

                $processedQuestionIds[] = $question->id;
            }
        }

        $questionsToDelete = array_diff($currentQuestionIds, $processedQuestionIds);

        if (! empty($questionsToDelete)) {
            $this->questions()->whereIn('id', $questionsToDelete)->delete();
        }
    }

    private function formatOptions(array $options, bool $allowMultiple = false): ?string
    {
        if (empty($options)) {
            return null;
        }

        if (is_string($options)) {
            return $options;
        }

        $optionTexts = array_column($options, 'text');

        return json_encode([
            'items' => $optionTexts,
            'multiple' => $allowMultiple,
        ]);
    }

    public function regenerateLink()
    {
        // Create new form with same data
        $newForm = self::create([
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        // Transfer relationships to new form
        $this->questions()->update(['form_id' => $newForm->id]);
        $this->answers()->update(['form_id' => $newForm->id]);

        // Delete old form
        $this->delete();

        return $newForm;
    }
}
