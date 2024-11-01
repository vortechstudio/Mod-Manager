<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\text;

class CreateLodCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createlod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Création des niveaux de LOD par Blender';

    protected $blender_path = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getConfig();
        $fbxPath = text(
            label: 'Chemin du fichier .fbx:',
        );

        $outputPath = text(
            label: 'Chemin du dossier de sortie:',
        );

        if (!file_exists($fbxPath) || !is_dir($outputPath)) {
            $this->error('Le fichier FBX ou le dossier de sortie est invalide.');
            return 1;
        }

        $this->task("Création des niveaux de LOD pour le fichier FBX {$fbxPath}", function () use ($fbxPath, $outputPath) {
            $command = "\"$this->blender_path\" --background --python bin/makelod/makelod.py -- \"$fbxPath\" \"$outputPath\"";

            if (exec($command, $output) === false) {
                return false;
            } else {
                return true;
            }
        });
    }

    private function getConfig()
    {
        $config_file = getcwd()."/config.json";
        $config = json_decode(file_get_contents($config_file), true);
        $this->blender_path = $config['blender_path'];
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
