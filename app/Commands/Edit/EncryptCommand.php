<?php

namespace App\Commands\Edit;


use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use LaravelZero\Framework\Commands\Command;
use App\Services\Obfuscator;

class EncryptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:encrypt {mod_path : Le chemin du mod à encrypter}';

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
        $password = $this->secret('Entrez le mot de passe pour encrypter le mod');

        // On copie le dossier de mod pour garder une trace local et ont inclue un fichier password qui contient le mot de passe encrypter Hash:Make
        $this->copyDirectoryOfBaseEncrypt($mod_path, $password);
    }

    protected function copyDirectoryOfBaseEncrypt($mod_path, $password)
    {
        File::copyDirectory($mod_path, getcwd(). '/mod_encrypted/'. basename($mod_path));
        $pass_hash = Crypt::encrypt($password);

        $files = collect();

        $this->task("Sauvegarde des fichiers du mod", function () use ($mod_path, $pass_hash, $files) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($mod_path));
            foreach ($iterator as $file) {
                if($file->isFile() && ($file->getExtension() === 'lua' || $file->getExtension() === 'mdl')) {
                    $files->push($file->getPathname());
                }
            }
        });

        if (empty($files)) {
            $this->info("Aucun fichier .lua ou .mdl trouvé dans le dossier $mod_path.");
        } else {
            $this->info("Fichiers trouvés dans $mod_path :");
            $this->encryptFiles($files, $mod_path);
        }

    }

    protected function encryptFiles($files, $mod_path)
    {
        $this->task("Encryption des fichiers mdl et lua.", function () use ($files, $mod_path) {
            $obs = new Obfuscator();
            foreach($files->toArray() as $file) {
                $contentFile = File::get($file);
                $obs->obfuscate($contentFile);
            }
            $this->info('Encryption des fichiers terminés: '.$mod_path);
            return true;
        });
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
