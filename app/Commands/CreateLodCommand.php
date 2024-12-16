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
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getConfig();

        $fbxPath = text(label: 'Chemin du fichier .fbx:');
        $outputPath = text(label: 'Chemin du dossier de sortie:');
        $lodLevels = text(label: 'Niveaux de réduction pour chaque LOD (ex: 80,65,45) :');

        // Validation des chemins
        if (!file_exists($fbxPath)) {
            $this->error("Le fichier FBX n'existe pas : {$fbxPath}");
        }
        if (!is_dir($outputPath)) {
            $this->error("Le dossier de sortie n'existe pas : {$outputPath}");
        }

        // Validation des niveaux de LOD
        $lodLevelsArray = array_map('trim', explode(',', $lodLevels));
        if (!$this->validateLodLevels($lodLevelsArray)) {
            $this->error("Les niveaux de réduction sont invalides. Assurez-vous d'entrer des pourcentages positifs séparés par des virgules.");
        }

        $this->task("Création des niveaux de LOD pour le fichier FBX {$fbxPath}", function () use ($fbxPath, $outputPath, $lodLevelsArray) {
            $lodLevelsString = implode(',', $lodLevelsArray); // Convertir pour passer au script Python
            $command = "\"$this->blender_path\" --background --python bin/makelod/makelod.py -- \"$fbxPath\" \"$outputPath\" \"$lodLevelsString\"";

            exec($command, $output, $returnCode);
            if ($returnCode !== 0) {
                $this->error("Erreur lors de la création des LOD :\n" . implode("\n", $output));
            }

            $this->info("Niveaux de LOD créés avec succès.");
        });
        $this->call('start', ['--without-config']);
    }

    private function getConfig()
    {
        $config_file = getcwd() . "/config.json";
        if (!file_exists($config_file)) {
            $this->error("Fichier de configuration manquant : {$config_file}");
            exit(1);
        }
        $config = json_decode(file_get_contents($config_file), true);
        if (!$config || !isset($config['blender_path'])) {
            $this->error("La configuration est invalide ou 'blender_path' est manquant.");
            exit(1);
        }
        $this->blender_path = $config['blender_path'];
    }

    private function validateLodLevels(array $lodLevels): bool
    {
        foreach ($lodLevels as $level) {
            if (!is_numeric($level) || $level <= 0 || $level > 100) {
                return false;
            }
        }
        return true;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
