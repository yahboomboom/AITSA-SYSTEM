<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Subject crediting for transferees/returnees: the Registrar submits subjects
// already passed elsewhere (or in old records), the Chair approves them, and
// only then do they become Passed grades marked source = 'credited'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_credit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('subject_credit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_credit_request_id')->constrained()->cascadeOnDelete();
            $table->string('subject_code', 20);
            $table->string('subject_title')->nullable();
            $table->string('final_grade', 10)->nullable();
            $table->timestamps();
        });

        Schema::table('student_grades', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->after('final_grade');
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropColumn('source');
        });
        Schema::dropIfExists('subject_credit_items');
        Schema::dropIfExists('subject_credit_requests');
    }
};
