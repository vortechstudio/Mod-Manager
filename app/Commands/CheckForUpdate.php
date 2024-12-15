<?php

namespace App\Commands;

use App\Services\Updater;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class CheckForUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updater';

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
        $config = config('app');
        $updater = new Updater(
            $config['github_owner'],
            $config['github_repo'],
            $config['version']
        );

        $updateInfo = $updater->checkForUpdate();
        if ($updateInfo) {
            $this->info("Une nouvelle version ({$updateInfo['version']}) est disponible ! Vous avez actuellement la version {$config['version']}.");

            if ($this->confirm('Voulez-vous mettre à jour maintenant ?')) {
                $this->info('Téléchargement de la nouvelle version...');
                if ($updater->update($updateInfo['download_url'])) {
                    $this->info('Mise à jour effectuée avec succès ! Veuillez relancer l’application.');
                } else {
                    $this->error('La mise à jour a échoué. Veuillez réessayer plus tard.');
                }
            } else {
                $this->info('Mise à jour ignorée.');
            }
        } else {
            $this->info('Aucune mise à jour n’est disponible. Vous utilisez la dernière version.');
        }

        return 0;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
