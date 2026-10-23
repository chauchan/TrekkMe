<?php

namespace Tests\Unit\Domain;

use App\Domain\Excursion\EstadoExcursion;
use App\Domain\Excursion\Exceptions\TransicionDeEstadoInvalida;
use PHPUnit\Framework\TestCase;

class EstadoExcursionTest extends TestCase
{
    public function test_cierre_en_dos_pasos(): void
    {
        $estado = EstadoExcursion::EnOperativo
            ->transicionarA(EstadoExcursion::CierrePendiente)
            ->transicionarA(EstadoExcursion::Finalizada);

        $this->assertSame(EstadoExcursion::Finalizada, $estado);
    }

    public function test_el_rescatista_no_puede_cerrar_directo(): void
    {
        $this->expectException(TransicionDeEstadoInvalida::class);

        EstadoExcursion::EnOperativo->transicionarA(EstadoExcursion::Finalizada);
    }

    public function test_el_carabinero_puede_rechazar_el_cierre(): void
    {
        $this->assertTrue(EstadoExcursion::CierrePendiente->puedePasarA(EstadoExcursion::EnOperativo));
    }

    public function test_falsa_alarma_desde_en_alerta(): void
    {
        $this->assertTrue(EstadoExcursion::EnAlerta->puedePasarA(EstadoExcursion::Finalizada));
    }

    public function test_estados_terminales(): void
    {
        $this->assertTrue(EstadoExcursion::Finalizada->esTerminal());
        $this->assertTrue(EstadoExcursion::Cancelada->esTerminal());
        $this->assertFalse(EstadoExcursion::EnOperativo->esTerminal());
    }

    public function test_bitacora_solo_con_caso_abierto(): void
    {
        $this->assertTrue(EstadoExcursion::EnOperativo->permiteEscribirBitacora());
        $this->assertTrue(EstadoExcursion::CierrePendiente->permiteEscribirBitacora());
        $this->assertFalse(EstadoExcursion::Finalizada->permiteEscribirBitacora());
    }
}
