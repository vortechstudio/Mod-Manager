<?php

namespace App\Commands\Edit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\select;

class MenuEditModCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'editmod';

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

        $mods = $this->getMods();
        if (empty($mods)) {
            $this->error("Aucun mod trouvé dans le staging_area.");
            return;
        }
        $selectedMod = $this->choice('Sélectionnez un mod à traiter :', $mods);

        $menu = select(
            label: 'Que souhaitez-vous faire ?',
            options: [
                "modify" => "Modifier les informations du mod",
                "construct" => "Créer une nouvelle construction à partir d'un .mdl",
                "compile" => "Préparer le mod à l'envoie sur les plateformes (Steam, Mod.io, Transportfever.net)",
                "encrypt" => "Crypter le mod",
                "decrypt" => "Decrypter le mod",
                "clean" => "Nettoyer le mod (Texture)",
                "dep" => "Gérez les dépendences",
                "retour" => "Retour au menu principal",
            ],
        );



        match($menu) {
            "modify" => $this->editModInfo($this->staging_path.'/'.$selectedMod),
            "construct" => $this->createConstruction($this->staging_path.'/'.$selectedMod),
            "compile" => $this->compile($this->staging_path.'/'.$selectedMod),
            "encrypt" => $this->encrypt($this->staging_path.'/'.$selectedMod),
            "decrypt" => $this->decrypt($this->staging_path.'/'.$selectedMod),
            "clean" => $this->clean($this->staging_path.'/'.$selectedMod),
            "dep" => $this->dependency($this->staging_path.'/'.$selectedMod),
            "retour" => $this->call('start', ['--without-config']),
        };
    }

    protected function editModInfo($selectedMod)
    {
        $this->call('mod:edit-info', ["mod_path" => $selectedMod]);
    }

    protected function createConstruction($selectedMod)
    {
        $this->call('mod:create-construction', ["mod_path" => $selectedMod]);
    }

    protected function compile($selectedMod)
    {
        $this->call('mod:compile', ["mod_path" => $selectedMod]);
    }

    protected function encrypt($selectedMod)
    {
        $this->call('mod:encrypt', ["mod_path" => $selectedMod]);
    }

    protected function decrypt($selectedMod)
    {
        $this->call('mod:decrypt', ["mod_path" => $selectedMod]);
    }

    protected function clean(string $selectedMod)
    {
        $this->call('texture:clean', ["mod_path" => $selectedMod]);
    }


    private function getConfig()
    {
        $config_file = getcwd()."/config.json";
        $config = json_decode(file_get_contents($config_file), true);
        $this->staging_path = $config['staging_path'];
    }

    private function getMods()
    {
        $mods = [];
        foreach (File::directories($this->staging_path) as $modPath) {
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

    private function dependency(string $selectedMod)
    {
        $this->call('menu:dependency', ["mod_path" => $selectedMod]);
    }
}
