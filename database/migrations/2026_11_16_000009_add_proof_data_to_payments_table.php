<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->longText('proof_data')->nullable()->after('proof_path');
            $table->string('proof_mime', 100)->nullable()->after('proof_data');
            $table->string('proof_name')->nullable()->after('proof_mime');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['proof_data', 'proof_mime', 'proof_name']);
        });
    }
};
