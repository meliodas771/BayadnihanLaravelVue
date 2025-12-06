<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		Schema::create('tasks', function (Blueprint $table) {
			$table->id();
			$table->foreignId('poster_id')->constrained('users')->cascadeOnDelete();
			$table->foreignId('doer_id')->nullable()->constrained('users')->cascadeOnDelete();
			$table->string('title');
			$table->text('description')->nullable();
			$table->string('category')->nullable();
			$table->decimal('price', 10, 2)->nullable();
			$table->string('payment_method')->nullable();
			$table->string('status')->default('open');
			$table->string('attachment_url')->nullable();
			$table->timestamps();
		});
	}
	public function down(): void {
		Schema::dropIfExists('tasks');
	}
};

