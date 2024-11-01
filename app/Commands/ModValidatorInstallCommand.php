<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;

class ModValidatorInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod-validator:install';

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
        $url = "https://www.transportfever2.com/wiki/lib/exe/fetch.php?media=modding:tools:tf2_modvalidator_public_v0.10.0.zip";
        $zipPath = getcwd() . "/temp/tf2_modvalidator.zip";
        $extractPath = getcwd(). "/bin/mod_validator";

        if(!File::exists(getcwd().'/temp')) {
            File::makeDirectory(getcwd().'/temp',755, true, true);
        }

        if(!File::exists(getcwd().'/bin')) {
            File::makeDirectory(getcwd().'/bin',755, true, true);
        }

        $this->task("Téléchargement du validateur de mod TF2", function () use ($url, $zipPath) {
            $response = Http::withoutVerifying()->get($url);

            if($response->successful()) {
                File::put($zipPath, $response->body());
                return true;
            } else {
                $this->error("Erreur lors du téléchargement du validateur");
                Log::error($response->body());
                return false;
            }
        });

        $this->task("Extraction du validateur de mod TF2", function () use ($zipPath, $extractPath) {
            $zip = new \ZipArchive;

            if ($zip->open($zipPath)) {
                if(!File::exists($extractPath)) {
                    File::makeDirectory($extractPath);
                }

                $zip->extractTo($extractPath);
                $zip->close();
                return true;
            } else {
                $this->error("Impossible d'extraire le validateur");
                return false;
            }
        });

        $this->task("Renommage de l'éxecutable", function () use ($extractPath) {
            $extractedExePath = $extractPath . '/TF2_ModValidator_Public.exe'; // Nom d'origine du fichier dans le ZIP
            $targetExePath = $extractPath . '/mod_validator.exe';

            if (File::exists($extractedExePath)) {
                File::move($extractedExePath, $targetExePath);
                return true;
            } else {
                $this->error('Le fichier .exe n\'a pas été trouvé après extraction.');
                Log::error($extractedExePath);
                return false;
            }
        });



        File::delete($zipPath);
        $this->info('Installation terminée du mod validateur avec succès!');
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
