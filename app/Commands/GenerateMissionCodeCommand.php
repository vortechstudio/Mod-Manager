<?php

namespace App\Commands;

use App\Services\MissionCodeGenerator;
use Illuminate\Console\Command;

class GenerateMissionCodeCommand extends Command
{
    protected $signature = 'mission:interactive';

    protected $description = 'Command description';

    public function handle(): void
    {
        $config = config('mission');
        // 1. Choix de la ligne
        $lines = array_keys($config['lines']);
        $line = $this->choice('Choisissez une ligne', $lines, 0);

        // 2. Choix de la branche
        $branches = array_keys($config['lines'][$line]['branches']);
        $branch = $this->choice('Choisissez une branche', $branches, 0);

        // 3. Sens (inbound/outbound)
        $direction = $this->choice('Sens du train', ['outbound', 'inbound'], 0);

        // 4. Type de service
        $serviceTypes = array_keys($config['serviceTypes']);
        $serviceType = $this->choice('Type de service', $serviceTypes, 0);

        // 5. Créneau horaire
        $timeSlots = array_keys($config['timeSlots']);
        $timeSlot = $this->choice('Créneau horaire', $timeSlots, 0);

        // Génération du code mission
        $generator = new MissionCodeGenerator($config);
        $code = $generator->generate($line, $branch, $direction, $serviceType, $timeSlot);

        $this->info("Le code mission généré est : $code");

    }
}
