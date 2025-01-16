<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start {--without-config : Start the application without config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Démarrage de l\'application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(!$this->option('without-config')) {
            $this->call('updater');
            $this->call("initialize");
        }
        $menu = select(
            label: 'Mod Manager '.config('app.version'),
            options: ["Création de mod", "Editer un mod", "Conversion Textures", "Vérification du mod", "Test Du Mod (Béta !)", "Créer des Lods (Béta !)", "Lua Checker", "Générer un code MISSION", "Quitter"],
        );

        match ($menu) {
            "Création de mod" => $this->createMod(),
            "Editer un mod" => $this->editMod(),
            "Conversion Textures" => $this->convert(),
            "Vérification du mod" => $this->verify(),
            "Test Du Mod (Béta !)" => $this->testMod(),
            "Créer des Lods (Béta !)" => $this->createLod(),
            "Lua Checker" => $this->luaChecker(),
            "Générer un code MISSION" => $this->generateCodeMission(),
            "Quitter" => exit(),
        };
    }

    public function createMod()
    {
        $this->call("createmod");
    }

    public function editMod()
    {
        $this->call("editmod");
    }

    public function convert()
    {
        $this->call('convert:texture');
    }

    public function verify()
    {
        $this->call('mod:verify');
    }

    public function testMod()
    {
        $this->call('mod:test');
    }

    public function createLod()
    {
        $this->call('createlod');
    }

    public function luaChecker()
    {
        $this->call('verif:lua');
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    private function generateCodeMission()
    {
        $this->call('mission:interactive');
    }
}
