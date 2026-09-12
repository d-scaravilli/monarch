<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('custom_label')->nullable();
            $table->string('file_path')->nullable();
            $table->date('uploaded_at');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // Bring existing medical certificates into the new generic
        // documents section (admin-facing "scheda iscritto") without
        // losing any data. The legacy medical_certificates table and the
        // member-facing "La mia area" page that reads it are untouched
        // and keep working exactly as before.
        DB::table('medical_certificates')->orderBy('id')->get()->each(function ($certificate) {
            DB::table('documents')->insert([
                'user_id' => $certificate->user_id,
                'type' => 'certificato_medico',
                'custom_label' => null,
                'file_path' => null,
                'uploaded_at' => $certificate->issue_date,
                'expiry_date' => $certificate->expiry_date,
                'created_at' => $certificate->created_at,
                'updated_at' => $certificate->updated_at,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
