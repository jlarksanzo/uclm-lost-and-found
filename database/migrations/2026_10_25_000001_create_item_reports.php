<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::create('item_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('name', 100);
            $table->text('description');
            $table->string('location', 150);
            $table->date('occurred_on');
            $table->string('photo_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('item_reports');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
