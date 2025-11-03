<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Support\Facades\Gate;

class RegenerateFormLinkController extends Controller
{
    public function __invoke(Form $form)
    {
        Gate::authorize('update', $form);

        $newForm = $form->regenerateLink();

        return redirect()->route('forms.edit', ['form' => $newForm->id])
            ->with('success', 'New form link generated successfully');
    }
}
