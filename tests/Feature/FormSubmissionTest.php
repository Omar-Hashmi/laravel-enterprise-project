<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\User;
use App\Models\Workflow;
use App\Services\FormSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_validates_and_stores_a_dynamic_form_submission(): void
    {
        $user = User::factory()->create();
        $workflow = Workflow::create(['title' => 'Leave request', 'created_by' => $user->id]);
        $form = Form::create(['workflow_id' => $workflow->id, 'title' => 'Leave details']);
        FormField::create(['form_id' => $form->id, 'key' => 'reason', 'label' => 'Reason', 'field_type' => 'text', 'field_order' => 1, 'is_required' => true, 'validation_rules' => ['min:3']]);

        $submission = app(FormSubmissionService::class)->submit($form->load('fields'), $user, ['reason' => 'Annual leave']);

        $this->assertSame('Annual leave', $submission->data['reason']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'form.submitted']);
    }

    public function test_it_rejects_fields_that_are_not_defined_by_the_form(): void
    {
        $user = User::factory()->create();
        $workflow = Workflow::create(['title' => 'Expense request', 'created_by' => $user->id]);
        $form = Form::create(['workflow_id' => $workflow->id, 'title' => 'Expense details']);
        FormField::create(['form_id' => $form->id, 'key' => 'amount', 'label' => 'Amount', 'field_type' => 'number', 'field_order' => 1, 'is_required' => true]);

        $this->expectException(ValidationException::class);
        app(FormSubmissionService::class)->submit($form->load('fields'), $user, ['amount' => 10, 'is_admin' => true]);
    }

    public function test_it_stores_uploaded_form_files_privately(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $workflow = Workflow::create(['title' => 'Document request', 'created_by' => $user->id]);
        $form = Form::create(['workflow_id' => $workflow->id, 'title' => 'Document details']);
        FormField::create(['form_id' => $form->id, 'key' => 'attachment', 'label' => 'Attachment', 'field_type' => 'file', 'field_order' => 1, 'is_required' => true]);

        $submission = app(FormSubmissionService::class)->submit($form->load('fields'), $user, ['attachment' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf')]);

        Storage::disk('local')->assertExists($submission->data['attachment']);
        $this->assertStringStartsWith('form-submissions/', $submission->data['attachment']);
    }
}
