<?php

namespace App\Events;

use App\Models\FormSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}
}
