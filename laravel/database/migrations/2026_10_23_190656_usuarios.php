<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->string('id')->primary(); // resc-001, carab-001, usr-001
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('rol'); // excursionista, rescatista, carabinero
            $table->timestamp('email_verificado_en')->nullable();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
