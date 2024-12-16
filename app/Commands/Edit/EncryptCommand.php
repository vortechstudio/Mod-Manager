<?php

namespace App\Commands\Edit;


use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use LaravelZero\Framework\Commands\Command;
use App\Services\Obfuscator;
use Symfony\Component\Process\Process;

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

        $this->warn("L'encryptage du mod concerne actuellement uniquement les scripts lua du mod.");

        // On copie le dossier de mod pour garder une trace local et ont inclue un fichier password qui contient le mot de passe encrypter Hash:Make
        $this->copyDirectoryOfBaseEncrypt($mod_path, $password);
        $this->call('editmod');
    }

    protected function copyDirectoryOfBaseEncrypt($mod_path, $password)
    {
        $pass_hash = Crypt::encrypt($password);
        $files = collect();

        if(File::exists(getcwd().'/mod_encrypted/'. basename($mod_path).'/encrypted')) {
            $this->error("Le dossier de mod encrypté existe déjà : mod_encrypted/".basename($mod_path));
            $this->call('editmod');
        }

        $this->task("Sauvegarde des fichiers du mod", function () use ($mod_path, $pass_hash, $files) {
            File::copyDirectory($mod_path, getcwd(). '/mod_encrypted/'. basename($mod_path));
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($mod_path.'/res/scripts'));
            foreach ($iterator as $file) {
                if($file->isFile() && ($file->getExtension() === 'lua')) {
                    $files->push($file->getPathname());
                }
            }
            File::put(getcwd(). '/mod_encrypted/'. basename($mod_path). '/password', $pass_hash);
            File::put(getcwd(). '/mod_encrypted/'. basename($mod_path). '/encrypted', '');

        });

        if (empty($files)) {
            $this->info("Aucun fichier de script .lua trouvé dans le dossier $mod_path/res/scripts.");
        } else {
            $this->info("Fichiers trouvés dans $mod_path :");
            $this->encryptFiles($files, $mod_path);
        }

    }

    protected function encryptFiles($files, $mod_path)
    {
        $this->task("Encryption des fichiers mdl et lua.", function () use ($files, $mod_path) {
            $cmd = getcwd().'/bin/lua/luac.exe';
            foreach($files->toArray() as $file) {
                $process = new Process([
                    $cmd,
                    '-o', $file,
                    $file
                ]);

                try {
                    $process->mustRun();
                    $this->info("Encryption du script : ".basename($file));
                } catch (\Exception $e) {
                    $this->error("Erreur lors de l'encryption du fichier $file : " . $e->getMessage());
                    return false; // Arrêter le processus si une erreur survient
                }
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
