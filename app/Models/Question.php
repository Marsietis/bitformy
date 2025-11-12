<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'form_id',
        'title',
        'type',
        'options',
        'allow_multiple',
        'required',
        'order',
    ];

    public function answer(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function toFormattedArray(): array
    {
        $questionData = [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'required' => $this->required,
            'order' => $this->order,
            'options' => [],
            'multipleChoice' => false,
        ];

        if ($this->type === 'choice' && ! empty($this->options)) {
            $optionsData = json_decode($this->options, true);

            if ($optionsData && isset($optionsData['items'])) {
                $formattedOptions = [];
                $optionId = 1;

                foreach ($optionsData['items'] as $optionText) {
                    $formattedOptions[] = [
                        'id' => $optionId,
                        'text' => $optionText,
                    ];
                    $optionId++;
                }

                $questionData['options'] = $formattedOptions;

                if (isset($optionsData['multiple'])) {
                    $questionData['multipleChoice'] = $optionsData['multiple'];
                }
            }
        }

        return $questionData;
    }
}
