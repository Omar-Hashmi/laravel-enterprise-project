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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('workflow_instance_id')->nullable()->index()->constrained('workflow_instances')->nullOnDelete();
            $table->foreignId('form_submission_id')->nullable()->index()->constrained('form_submissions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('delegated_by_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('delegated_to_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('delegated_at')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('priority', 32)->default('medium')->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->boolean('sla_breached')->default(false)->index();
            $table->timestamp('completed_at')->nullable();
            $table->text('action_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
