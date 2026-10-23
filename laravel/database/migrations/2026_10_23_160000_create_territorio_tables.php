<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Territorio: jurisdicciones → áreas → senderos.
// Las columnas geográficas (limite, trazado) del modelo de datos se agregan
// cuando se pase a MySQL/PostgreSQL con soporte espacial; SQLite no las tiene.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurisdicciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('region');
            $table->string('comuna');
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurisdiccion_id')->constrained('jurisdicciones');
            $table->string('nombre');
            $table->enum('tipo', ['parque_nacional', 'reserva', 'otro']);
            $table->enum('cobertura_celular', ['nula', 'parcial', 'buena']);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('senderos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas');
            $table->string('nombre');
            $table->enum('dificultad', ['baja', 'media', 'alta', 'experta']);
            $table->decimal('distancia_km', 6, 2)->nullable();
            $table->integer('desnivel_m')->nullable();
            $table->integer('duracion_estimada_min')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['area_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senderos');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('jurisdicciones');
    }
};
