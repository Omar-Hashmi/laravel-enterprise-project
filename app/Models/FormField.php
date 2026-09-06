<?php

// app/Models/FormField.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $fillable = ['form_id', 'key', 'label', 'field_type', 'field_order', 'is_required', 'options', 'validation_rules', 'conditional_rules'];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'validation_rules' => 'array',
        'conditional_rules' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
