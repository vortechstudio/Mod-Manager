<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class InitializeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialisation de l\'application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $configFile = getcwd().'/config.json';

        if (!File::exists($configFile)) {
            $this->warn("Les informations de configuration sont inexistante");
            $config = $this->createConfig();
            $this->saveConfig($configFile, $config);
        } else {
            $config = json_decode(file_get_contents($configFile), true);
            if(!isset($config['staging_path']) || empty($config['staging_path'])) {
                $this->warn("Vous n'avez pas définie le chemin vers votre `staging_area`");
                $config['staging_path'] = $this->askForStagingPath();
                $this->saveConfig($configFile, $config);
            } else if(!isset($config['blender_path']) || empty($config['blender_path'])) {
                $this->warn("Vous n'avez pas définie le chemin vers Blender");
                $config["blender_path"] = $this->askForBlenderPath();
                $this->saveConfig($configFile, $config);
            } else {
                $this->info("Information de configuration correct");
            }
        }

        if(File::isDirectory($config['staging_path'])) {
            $this->info('Le dossier Existe et est Valide !');
        } else {
            $this->error("Le dossier Existe pas ou n'est pas valide. Veuillez vérifier le chemin et essayer à nouveau. Chemin : ".$config['staging_path']);
        }

        $this->ImageMagickIsInstalled();
        $this->ModValidatorIsExist();

        $this->info("Initialisation Terminé.");

    }

    public function createConfig()
    {
        return [
            'staging_path' => $this->askForStagingPath(),
            'blender_path' => $this->askForBlenderPath(),
        ];
    }

    private function askForStagingPath()
    {
        return $this->ask("Veuillez entrer le chemin du staging_area pour vos mods");
    }
    private function askForBlenderPath()
    {
        return $this->ask("Veuillez entrer le chemin de l'installation de blender");
    }

    private function saveConfig($filePath, $config)
    {
        file_put_contents($filePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Configuration sauvegardée dans $filePath.");
    }

    private function ImageMagickIsInstalled()
    {
        if(exec('magick --version', $output) === false) {
            $this->error('Image Magick est introuvable. Tentative d\'installation interne !');
            $this->call('imageMagick:install');
        } else {
            $this->info('Image Magick est installé.');
        }
    }

    private function ModValidatorIsExist()
    {
        if(!File::exists(getcwd().'/bin/mod_validator/mod_validator.exe')) {
            $this->call('mod-validator:install');
        } else {
            $this->info("Mod Validator est installé.");
        }
    }
}
