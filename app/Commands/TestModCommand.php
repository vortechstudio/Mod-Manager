<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class TestModCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test du mod';

    protected $staging_path = '';
    protected $magicCmd = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getConfig();
        if(exec('magick --version', $output) === false) {
            $this->magicCmd = getcwd().'/bin/imagemagick/magick.exe';
        } else {
            $this->magicCmd = 'magick';
        }

        $mods = $this->getMods($this->staging_path);
        if (empty($mods)) {
            $this->error("Aucun mod trouvé dans le dossier staging_area.");
            return;
        }

        // 3. Demander au moddeur de sélectionner un mod
        $selectedMod = $this->choice('Sélectionnez un mod à vérifier :', $mods);

        $modPath = "$this->staging_path/$selectedMod";
        $this->info("Vérification du mod dans : $modPath");
        $this->checkStructure($modPath);
        $this->checkTextures($modPath);
        $this->checkConfigFiles($modPath);
        $this->checkOptimization($modPath);
    }

    private function getConfig()
    {
        $config_file = getcwd()."/config.json";
        $config = json_decode(file_get_contents($config_file), true);
        $this->staging_path = $config['staging_path'];
    }

    private function getMods($stagingPath)
    {
        $mods = [];
        foreach (File::directories($stagingPath) as $modPath) {
            $mods[] = basename($modPath);
        }
        return $mods;
    }

    private function checkStructure($path)
    {
        $this->task('Vérification de la structure du mod', function () use ($path) {
            $expectedDirs = [
                'res/models',
                'res/textures',
                'mod.lua'
            ];

            foreach ($expectedDirs as $dir) {
                if (!File::exists("$path/$dir")) {
                    $this->warn("Structure incorrecte : dossier/fichier manquant -> $dir");
                    return false;
                }
            }
            return true;
        });
    }

    private function checkTextures($path)
    {
        $this->task('Vérification des textures', function () use ($path) {
            $texturePath = realpath("$path/res/textures");

            if (!$texturePath || !is_dir($texturePath)) {
                $this->warn("Le dossier des textures n'existe pas ou est inaccessible : $path/res/textures");
                return false;
            }

            $textures = [];
            // Utilisation de RecursiveDirectoryIterator pour rechercher tous les .dds
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($texturePath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'dds') {
                    $textures[] = $file->getPathname();
                }
            }

            if (empty($textures)) {
                $this->warn("Aucune texture .dds trouvée dans : $texturePath");
                return false;
            }

            foreach ($textures as $texture) {
                $this->info("Vérification de la texture : $texture");

                if (!$this->hasMipmaps($texture)) {
                    $this->warn("La texture $texture n'a pas le nombre de mipmaps requis.");
                    return false;
                }

                $size = filesize($texture) / (1024 * 1024); // Taille en Mo
                if ($size > 5) {  // Limite de taille en MB
                    $this->warn("La texture $texture est trop volumineuse (${size}MB).");
                    return false;
                }
            }

            return true;
        });
    }

    public function checkConfigFiles($path)
    {
        $this->task("Vérification des fichiers de configuration", function () use ($path) {
            $modFile = "$path/mod.lua";
            if (!File::exists($modFile)) {
                $this->warn("Fichier mod.lua manquant.");
                return false;
            } else {
                $content = File::get($modFile);
                if (!str_contains($content, 'name') || !str_contains($content, 'description')) {
                    $this->warn("Le fichier mod.lua manque des informations de base (nom ou description).");
                    return false;
                }
            }
            return true;
        });
    }

    protected function checkOptimization($path)
    {
        $this->task("Vérification des optimisations des modèles", function () use ($path) {
            $modelPath = realpath("$path/res/models");

            if (!$modelPath || !is_dir($modelPath)) {
                $this->warn("Le dossier des modèles n'existe pas ou est inaccessible : $path/res/models");
                return false;
            }

            $modelFiles = [];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modelPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'mdl') {
                    $modelFiles[] = $file->getPathname();
                }
            }

            if (empty($modelFiles)) {
                $this->warn("Aucun fichier .mdl trouvé dans : $modelPath");
                return false;
            }

            foreach ($modelFiles as $file) {
                $content = File::get($file);
                if (!str_contains($content, 'lod')) {
                    $this->warn("Le fichier $file ne contient pas de configuration LOD.");
                    return false;
                }
            }

            return true;
        });
    }

    protected function hasMipmaps($file)
    {
        // Une implémentation simple vérifiant les mipmaps d'une texture .dds
        // Requiert l'installation d'une bibliothèque d'image comme Intervention Image
        // Ici, nous simulons une vérification de mipmap.

        // TODO: Implémenter la vérification réelle des mipmaps avec une bibliothèque adaptée
        if (!file_exists($file)) {
            $this->warn("Le fichier spécifié n'existe pas : $file");
            return false;
        }

        $command = "$this->magicCmd identify -format '%n'".escapeshellarg($file);
        if(exec($command, $output, $returnVar) === false) {
            return false;
        } else {
            $numFrames = isset($output[0]) ? (int)$output[0] : 0;
            if ($numFrames < 2) { // Généralement, 2 ou plus indique la présence de mipmaps
                $this->warn("Le fichier $file ne contient pas assez de mipmaps.");
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
