<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;

class ImageMagickInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imageMagick:install';

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
        $url = 'https://imagemagick.org/archive/binaries/ImageMagick-7.1.1-39-portable-Q16-x64.zip';
        $zipPath = getcwd().'/temp/imagemagick.zip';
        $extractPath = getcwd().'/bin/imagemagick';

        if(!File::exists(getcwd().'/temp')) {
            File::makeDirectory(getcwd().'/temp',755, true, true);
        }

        if(!File::exists(getcwd().'/bin')) {
            File::makeDirectory(getcwd().'/bin',755, true, true);
        }

        $this->task("Téléchargement de Image Magick", function () use ($url, $zipPath) {
            $response = Http::withoutVerifying()->get($url);

            if($response->successful()) {
                File::put($zipPath, $response->body());
                return true;
            } else {
                $this->error("Erreur lors du téléchargement de image magick");
                Log::error($response->body());
                return false;
            }
        });

        $this->task("Extraction de image magick", function () use ($zipPath, $extractPath) {
            $zip = new \ZipArchive;

            if ($zip->open($zipPath)) {
                if(!File::exists($extractPath)) {
                    File::makeDirectory($extractPath);
                }

                $zip->extractTo($extractPath);
                $zip->close();
                return true;
            } else {
                $this->error("Impossible d'extraire image magick");
                return false;
            }
        });

        $this->task("Test de l'éxecution de magick", function () use ($extractPath) {
            if(exec(getcwd().'/bin/imagemagick/magick --version', $output) === false) {
                $this->error('Erreur du magick');
                exit(1);
            } else {
                $this->info('Image Magick est installé et fonctionnel');
                return true;
            }
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
