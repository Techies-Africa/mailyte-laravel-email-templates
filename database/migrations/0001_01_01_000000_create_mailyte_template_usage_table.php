<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailyte_template_usage', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('version')->default('');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailyte_template_usage');
    }
};
