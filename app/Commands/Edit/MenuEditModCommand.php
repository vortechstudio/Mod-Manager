<?php

namespace App\Commands\Edit;

use Illuminate\Console\Scheduling\Schedule;
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $menu = select(
            label: 'Que souhaitez-vous faire ?',
            options: [
                "Modifier les informations du mod",
            ],
        );

        match($menu) {
            "Modifier les informations du mod" => $this->editModInfo(),
        };
    }

    protected function editModInfo()
    {
        $this->call('mod:edit-info');
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
