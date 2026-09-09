<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('confluence_url')->nullable();
            $table->string('jira_key')->nullable();
            $table->string('jira_url')->nullable();
            $table->string('business_owner')->nullable();
            $table->string('stage')->default('discovery');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('needs');
    }
};
