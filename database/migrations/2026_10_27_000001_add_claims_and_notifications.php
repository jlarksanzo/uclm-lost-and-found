<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_reports', fn (Blueprint $table) => $table->string('category', 30)->default('Other')->index());
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('proof');
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['item_report_id', 'user_id']);
        });
        Schema::create('return_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_report_id')->unique()->constrained();
            $table->foreignId('claim_id')->nullable()->constrained();
            $table->foreignId('recipient_id')->constrained('users');
            $table->foreignId('admin_id')->constrained('users');
            $table->text('note');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('return_records');
        Schema::dropIfExists('claims');
        Schema::table('item_reports', fn (Blueprint $table) => $table->dropColumn('category'));
    }
};
