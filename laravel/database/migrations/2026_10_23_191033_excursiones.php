<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Misma forma que los documentos de la colección "excursiones" de Firestore
    public function up(): void
    {
        Schema::create('excursions', function (Blueprint $table) {
            $table->string('id')->primary(); // exc-001
            $table->string('excursionista');
            $table->string('rut');
            $table->string('telefono');
            $table->unsignedSmallInteger('edad');
            $table->string('experiencia');
            $table->string('condiciones_medicas')->default('Ninguna');
            $table->json('acompanantes')->nullable();
            $table->string('ruta');
            $table->string('contacto_nombre');
            $table->string('contacto_telefono');
            $table->dateTime('salida_at');
            $table->dateTime('retorno_at');
            $table->string('estado'); // ver App\Enums\EstadoExcursion
            $table->string('rescatista_id')->nullable();
            $table->foreign('rescatista_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->string('foto_url')->nullable();
            $table->text('detalle')->nullable();
            $table->boolean('lleva_carpa')->default(false);
            $table->string('color_carpa')->nullable();
            $table->boolean('lleva_linterna')->default(false);
            $table->double('ultima_lat')->nullable();
            $table->double('ultima_lng')->nullable();
            $table->dateTime('ultima_ubicacion_at')->nullable();
            $table->unsignedTinyInteger('bateria')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excursions');
    }
};
