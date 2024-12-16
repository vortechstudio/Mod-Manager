<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class VerifSyntaxLuaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verif:lua';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $staging_path = '';
    protected $luaCmd = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getConfig();

        $this->luaCmd = getcwd().'/bin/lua/luac.exe';

        $mods = $this->getMods($this->staging_path);
        if (empty($mods)) {
            $this->error("Aucun mod trouvé dans le dossier staging_area.");
            return;
        }

        // 3. Demander au moddeur de sélectionner un mod
        $selectedMod = $this->choice('Sélectionnez un mod à vérifier :', $mods);

        $modPath = "$this->staging_path/$selectedMod";

        $this->task("Vérification de la syntaxe du mod : $selectedMod", function () use ($modPath) {
            $luaFiles = File::allFiles($modPath);
            $hasErrors = false;

            foreach ($luaFiles as $file) {
                if ($file->getExtension() === 'lua') {
                    $this->info("Vérification du fichier : {$file->getFilename()}");
                    $result = $this->checkLuaSyntax($file->getPathname());

                    if ($result !== true) {
                        $this->error("Erreur dans {$file->getFilename()} : $result");
                        $hasErrors = true;
                        return false;
                    } else {
                        $this->info("Pas d'erreur dans {$file->getFilename()}");
                        return true;
                    }
                }
            }

            if ($hasErrors) {
                $this->error("Des erreurs de syntaxe Lua ont été détectées.");
                return false;
            } else {
                $this->info("Tous les fichiers Lua sont valides.");
                return true;
            }
        });
        $this->call('start', ['--without-config']);
    }

    private function getMods($stagingPath)
    {
        $mods = [];
        foreach (File::directories($stagingPath) as $modPath) {
            $mods[] = basename($modPath);
        }
        return $mods;
    }

    private function checkLuaSyntax($filePath)
    {
        // Commande `luac -p` pour vérifier la syntaxe sans exécuter le fichier
        $command = "$this->luaCmd -p " . escapeshellarg($filePath);
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return implode("\n", $output); // Retourne l'erreur si la syntaxe est invalide
        }

        return true; // Retourne true si le fichier est valide
    }


    private function getConfig()
    {
        $config_file = getcwd()."/config.json";
        $config = json_decode(file_get_contents($config_file), true);
        $this->staging_path = $config['staging_path'];
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
