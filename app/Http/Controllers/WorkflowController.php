<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        return response()->json(Workflow::query()->with(['steps', 'forms'])->latest()->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);
        $validated = $this->validatedDefinition($request);

        $workflow = DB::transaction(function () use ($validated, $request): Workflow {
            $workflow = Workflow::create([
                ...collect($validated)->except('steps')->all(),
                'created_by' => $request->user()->id,
            ]);
            $workflow->steps()->createMany($validated['steps'] ?? []);

            return $workflow;
        });

        return response()->json($workflow->load('steps'), 201);
    }

    public function update(Request $request, Workflow $workflow, AuditTrailService $audit): JsonResponse
    {
        $this->authorize('update', $workflow);
        $validated = $this->validatedDefinition($request, true);
        $status = $validated['status'] ?? $workflow->status;

        if ($workflow->status === 'archived' || ($workflow->status === 'active' && $status === 'draft')) {
            return response()->json(['message' => 'This workflow status transition is not allowed.'], 422);
        }
        if (array_key_exists('steps', $validated) && $workflow->instances()->exists()) {
            return response()->json(['message' => 'A workflow with instances cannot change its steps.'], 409);
        }

        DB::transaction(function () use ($validated, $workflow, $audit, $status): void {
            $oldValues = $workflow->only(['title', 'description', 'version', 'status', 'definition']);
            $workflow->update([...collect($validated)->except('steps')->all(), 'status' => $status]);
            if (array_key_exists('steps', $validated)) {
                $workflow->steps()->delete();
                $workflow->steps()->createMany($validated['steps']);
            }
            $audit->record('workflow.updated', $workflow, $oldValues, $workflow->fresh()->only(['title', 'description', 'version', 'status', 'definition']));
        });

        return response()->json($workflow->fresh()->load('steps'));
    }

    public function destroy(Workflow $workflow): JsonResponse
    {
        $this->authorize('delete', $workflow);
        if ($workflow->instances()->exists()) {
            return response()->json(['message' => 'Workflows with instances cannot be deleted.'], 409);
        }
        $workflow->delete();

        return response()->noContent();
    }

    public function show(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        return response()->json($workflow->load(['steps', 'forms.fields']));
    }

    /** @return array<string, mixed> */
    private function validatedDefinition(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in($updating ? ['draft', 'active', 'archived'] : ['draft', 'active'])],
            'definition' => ['nullable', 'array'],
            'steps' => ['sometimes', 'array'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.type' => ['required', 'in:sequential,parallel,conditional,decision,auto_action'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.rules' => ['nullable', 'array'],
            'steps.*.sla_hours' => ['nullable', 'integer', 'min:1'],
            'steps.*.assignee_role' => ['nullable', 'string', 'max:255'],
            'steps.*.assignee_user_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
