<?php

namespace App\Console\Commands;

use App\Services\Orders\TechnicianSlaService;
use Illuminate\Console\Command;

class DetectTechnicianDelaysCommand extends Command
{
    protected $signature = 'orders:detect-technician-delays';

    protected $description = 'Detect technician accept/arrival SLA breaches and notify company/platform users';

    public function handle(TechnicianSlaService $sla): int
    {
        $result = $sla->detectAndNotify();

        $this->info(sprintf(
            'Technician delay detection finished. Accept breaches: %d, arrival breaches: %d',
            $result['accept'],
            $result['arrival'],
        ));

        return self::SUCCESS;
    }
}
