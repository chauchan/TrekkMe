<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Misma forma que los documentos de la colección "bitacora" de Firestore
    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->string('id')->primary(); // bit-001
            $table->string('excursion_id');
            $table->string('rescatista_id');
            $table->string('ruta')->default('');
            $table->dateTime('hora_inicio');
            $table->dateTime('hora_fin')->nullable();
            $table->text('texto');
            $table->dateTime('registrado_at');
            $table->unsignedInteger('version')->default(1);

            $table->foreign('excursion_id')->references('id')->on('excursions')->cascadeOnDelete();
            $table->foreign('rescatista_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
