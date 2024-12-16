<?php

namespace App\Commands\Edit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\password;

class DecryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:decrypt {mod_path : Le chemin du mod à décrypter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mod_path = $this->argument('mod_path');
        $name_base = basename($mod_path);

        $this->task("Vérification du dossier de mod", function () use ($mod_path, $name_base) {
            if(!File::exists(getcwd().'/mod_encrypted/'.$name_base)) {
                $this->error('Le dossier de mod encrypté n\'existe pas : mod_encrypted/'.$name_base);
                return false;
            } else {
                return true;
            }
        });
        $this->verifyPassword($mod_path, $name_base);
        $this->call('editmod');

    }

    protected function verifyPassword($mod_path, $name_base)
    {
        $getEncryptedPassword = File::get(getcwd().'/mod_encrypted/'.$name_base.'/password');
        $password = password('Veuillez entrer le mot de passe encrypté :');
        $decryptedPassword = Crypt::decrypt($getEncryptedPassword);
        $pass = 0;
        if($pass < 3) {
            if($password === $decryptedPassword) {
                $this->decryptMod($mod_path, $name_base);
            } else {
                $this->error('Le mot de passe est incorrect');
                $pass++;
                $this->verifyPassword($mod_path, $name_base);
            }
        } else {
            $this->error('Le nombre maximum de tentatives de connexion a été atteint');
        }
    }

    protected function decryptMod($mod_path, $name_base)
    {
        $this->line("Décryption du mod $name_base");
        if(File::copyDirectory(getcwd().'/mod_encrypted/'.$name_base.'/res/scripts', $mod_path.'/res/scripts')) {
            $this->info('Mod décrypté avec succès');
            File::deleteDirectory(getcwd().'/mod_encrypted/'.$name_base);
            $this->call('editmod');
        } else {
            $this->error("Erreur lors de la décryption du mod");
            $this->call('editmod');
        }
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
