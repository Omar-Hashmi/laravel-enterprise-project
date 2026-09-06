<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use App\Models\WorkflowInstance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class FormSubmissionService
{
    public function __construct(private WorkflowConditionEvaluator $conditions, private AuditTrailService $audit) {}

    /** @param array<string, mixed> $data */
    public function submit(Form $form, User $user, array $data, ?WorkflowInstance $instance = null): FormSubmission
    {
        if ($instance !== null && $instance->workflow_id !== $form->workflow_id) {
            throw new \DomainException('The workflow instance does not belong to this form.');
        }

        $rules = [];
        $fieldsByKey = [];
        foreach ($form->fields as $field) {
            if ($field->conditional_rules !== null && ! $this->conditions->passes($field->conditional_rules, $data)) {
                continue;
            }
            $key = $field->key ?: str($field->label)->slug('_')->toString();
            $fieldsByKey[$key] = $field;
            $rules[$key] = array_filter([$field->is_required ? 'required' : 'nullable', ...($field->validation_rules ?? []), ...$this->typeRules($field->field_type)]);
        }
        $validator = Validator::make($data, $rules);
        $validator->after(function ($validator) use ($data, $rules): void {
            $unexpectedKeys = array_diff(array_keys($data), array_keys($rules));
            if ($unexpectedKeys !== []) {
                $validator->errors()->add('data', 'The submission contains fields that are not available on this form.');
            }
        });
        $validatedData = $validator->validate();
        foreach ($fieldsByKey as $key => $field) {
            if ($field->field_type === 'file' && ($validatedData[$key] ?? null) instanceof UploadedFile) {
                $validatedData[$key] = $validatedData[$key]->store('form-submissions');
            }
        }
        $submission = FormSubmission::create([
            'form_id' => $form->id, 'workflow_instance_id' => $instance?->id, 'submitted_by' => $user->id,
            'data' => $validatedData, 'submitted_at' => now(),
        ]);
        $this->audit->record('form.submitted', $submission, null, ['form_id' => $form->id], $user->id);

        return $submission;
    }

    /** @return array<int, string> */
    private function typeRules(string $fieldType): array
    {
        return match ($fieldType) {
            'date' => ['date'],
            'file' => ['file', 'max:10240'],
            'multi_select' => ['array'],
            'number' => ['numeric'],
            default => ['string'],
        };
    }
}
