<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_role')->nullable();
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_to', 'status', 'due_at']);
            $table->index(['assigned_role', 'status', 'due_at']);
            $table->index(['workflow_instance_id', 'workflow_step_id', 'status'], 'workflow_assignments_instance_step_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_assignments');
    }
};
