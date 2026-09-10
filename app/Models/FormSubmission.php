<?php

namespace App\Models;

use App\Events\FormSubmitted;
use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    protected $fillable = ['form_id', 'workflow_instance_id', 'submitted_by', 'data', 'submitted_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'submitted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(function (FormSubmission $submission): void {
            FormSubmitted::dispatch($submission);
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
