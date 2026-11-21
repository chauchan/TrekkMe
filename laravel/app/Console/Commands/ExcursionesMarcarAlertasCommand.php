<?php

namespace App\Console\Commands;

use App\Services\AlertaService;
use Illuminate\Console\Command;

class ExcursionesMarcarAlertasCommand extends Command
{
    protected $signature = 'excursiones:marcar-alertas';

    protected $description = 'Pasa a "en alerta" las excursiones en curso cuya hora de retorno ya venció';

    public function handle(AlertaService $alertas): int
    {
        $marcadas = $alertas->marcarVencidas();

        $this->info(count($marcadas).' excursión(es) pasaron a "en alerta".');
        foreach ($marcadas as $id) {
            $this->line(" - {$id}");
        }

        return self::SUCCESS;
    }
}
