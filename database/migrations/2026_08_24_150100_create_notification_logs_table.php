<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->timestamp('sent_at');

            $table->unique(['type', 'notifiable_type', 'notifiable_id'], 'notification_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
