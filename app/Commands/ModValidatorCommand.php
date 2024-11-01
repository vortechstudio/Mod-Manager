<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class ModValidatorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $staging_path = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getConfig();
        // 2. Lister les mods disponibles dans le dossier staging_area
        $mods = $this->getMods($this->staging_path);
        if (empty($mods)) {
            $this->error("Aucun mod trouvé dans le dossier staging_area.");
            return;
        }

        // 3. Demander au moddeur de sélectionner un mod
        $selectedMod = $this->choice('Sélectionnez un mod à vérifier :', $mods);

        $modPath = "$this->staging_path/$selectedMod";
        $validatorPath = getcwd().'/bin/mod_validator/mod_validator.exe';

        $this->task('Vérification du mod : '.$selectedMod, function () use ($modPath, $validatorPath) {
            $command = "\"$validatorPath\" \"$modPath\" --nopause --fix-mipmaps";
            if(exec($command, $output) === false) {
                return false;
            } else {
                return $output;
            }
        });
        $this->call('start', ['--without-config']);
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

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
