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
        Schema::table('workflows', function (Blueprint $table) {
            $table->json('definition')->nullable()->after('status');
            $table->index(['status', 'created_at']);
        });
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->string('assignee_role')->nullable()->after('sla_hours');
            $table->foreignId('assignee_user_id')->nullable()->after('assignee_role')->constrained('users')->nullOnDelete();
            $table->index(['workflow_id', 'type']);
        });
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('key')->nullable()->after('form_id');
            $table->unsignedInteger('field_order')->default(0)->after('field_type');
            $table->json('conditional_rules')->nullable()->after('validation_rules');
            $table->index(['form_id', 'field_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn(['key', 'field_order', 'conditional_rules']);
        });
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['assignee_user_id']);
            $table->dropColumn(['assignee_role', 'assignee_user_id']);
        });
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn('definition');
        });
    }
};
