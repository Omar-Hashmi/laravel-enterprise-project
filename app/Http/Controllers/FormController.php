<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\FormSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    public function index(Workflow $workflow): JsonResponse
    {
        $this->authorize('viewAny', Form::class);

        return response()->json($workflow->forms()->with('fields')->get());
    }

    public function store(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('create', [Form::class, $workflow]);
        $validated = $this->validatedForm($request);
        $form = DB::transaction(function () use ($validated, $workflow): Form {
            $form = $workflow->forms()->create(['title' => $validated['title']]);
            $form->fields()->createMany($validated['fields'] ?? []);

            return $form;
        });

        return response()->json($form->load('fields'), 201);
    }

    public function show(Form $form): JsonResponse
    {
        $this->authorize('view', $form);

        return response()->json($form->load(['workflow', 'fields']));
    }

    public function update(Request $request, Form $form): JsonResponse
    {
        $this->authorize('update', $form);
        $validated = $this->validatedForm($request);
        DB::transaction(function () use ($validated, $form): void {
            $form->update(['title' => $validated['title']]);
            $form->fields()->delete();
            $form->fields()->createMany($validated['fields'] ?? []);
        });

        return response()->json($form->fresh('fields'));
    }

    public function destroy(Form $form): JsonResponse
    {
        $this->authorize('delete', $form);
        $form->delete();

        return response()->noContent();
    }

    public function submit(Request $request, Form $form, FormSubmissionService $service): JsonResponse
    {
        $this->authorize('submit', $form);
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'workflow_instance_id' => ['nullable', 'exists:workflow_instances,id'],
        ]);
        $instance = isset($validated['workflow_instance_id']) ? WorkflowInstance::findOrFail($validated['workflow_instance_id']) : null;
        if ($instance !== null) {
            if ($instance->workflow_id !== $form->workflow_id) {
                return response()->json(['message' => 'The workflow instance does not belong to this form.'], 422);
            }
            $this->authorize('view', $instance);
        }
        $submission = $service->submit($form->load('fields'), $request->user(), $validated['data'], $instance);

        return response()->json($submission, 201);
    }

    /** @return array<string, mixed> */
    private function validatedForm(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'fields' => ['sometimes', 'array'],
            'fields.*.key' => ['nullable', 'string', 'alpha_dash', 'max:100'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.field_type' => ['required', 'in:text,textarea,date,file,multi_select,select,number'],
            'fields.*.field_order' => ['required', 'integer', 'min:0'],
            'fields.*.is_required' => ['sometimes', 'boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.validation_rules' => ['nullable', 'array'],
            'fields.*.conditional_rules' => ['nullable', 'array'],
        ]);
    }
}
