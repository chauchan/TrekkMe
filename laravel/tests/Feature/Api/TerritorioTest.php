<?php

namespace Tests\Feature\Api;

use App\Models\Area;
use Database\Seeders\TerritorioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerritorioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TerritorioSeeder::class);
    }

    public function test_lista_las_areas_activas(): void
    {
        Area::where('nombre', 'PN Nahuelbuta')->update(['activa' => false]);

        $this->getJson('/api/areas')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nombre', 'Laguna del Laja')
            ->assertJsonStructure(['data' => [['id', 'jurisdiccion_id', 'nombre', 'tipo', 'cobertura_celular']]]);
    }

    public function test_lista_los_senderos_de_un_area(): void
    {
        $nonguen = Area::where('nombre', 'PN Nonguén')->first();

        $this->getJson("/api/areas/{$nonguen->id}/senderos")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nombre', 'Sendero Los Canelos')
            ->assertJsonPath('data.0.distancia_km', 4.5);
    }

    public function test_area_inexistente_responde_404(): void
    {
        $this->getJson('/api/areas/999/senderos')->assertNotFound();
    }
}
