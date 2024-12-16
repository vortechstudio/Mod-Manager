<?php

namespace App\Commands\Edit;

use App\Services\TextureManager;
use Illuminate\Console\Command;

class TextureCleanCommand extends Command
{
    protected $signature = 'texture:clean {mod_path}';

    protected $description = 'Identifie et supprime les textures inutilisées dans un mod';

    public function handle(): void
    {
        $modPath = $this->argument('mod_path');
        $manager = new TextureManager($this);
        $manager->handleUnusedFiles($modPath);
        $this->call('editmod');
    }
}
