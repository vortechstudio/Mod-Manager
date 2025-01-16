<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;

class TestModCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test du mod';

    protected $staging_path = '';
    protected $magicCmd = '';
    protected array $errors = [];

    /**
     * Execute the console command.
     */

    public function __construct()
    {
        parent::__construct();
        $configFile = getcwd() . "/config.json";
        if (!file_exists($configFile)) {
            throw new \Exception('Configuration file not found.');
        }

        $config = json_decode(file_get_contents($configFile), true);
        if (!isset($config['staging_path'])) {
            throw new \Exception('Staging path not defined in configuration.');
        }

        $this->staging_path = $config['staging_path'];
        $this->magicCmd = getcwd().'/bin/imagemagick/magick.exe';
    }

    public function handle()
    {
        $mods = $this->getMods($this->staging_path);
        if (empty($mods)) {
            $this->error('No mods found in the staging area.');
            return Command::FAILURE;
        }
        $mod = $this->choice('Selectionner un mod', $mods);
        $this->info("Validating mod '{$mod}'...");

        $this->validateStructure($mod);
        $this->validateTextures($mod);
        $this->validateConfig($mod);

        if (!empty($this->errors)) {
            $this->error("Validation completed with errors:");
            foreach ($this->errors as $error) {
                $this->error(" - {$error}");
            }
            return Command::FAILURE;
        }

        $this->info('Validation completed successfully. No errors found.');
        return Command::SUCCESS;
    }

    private function getMods(mixed $staging_path)
    {
        $mods = [];
        foreach (File::directories($staging_path) as $modPath) {
            $mods[] = basename($modPath);
        }
        return $mods;
    }

    private function validateStructure(array|string $mod): void
    {
        $modPath = $this->staging_path . DIRECTORY_SEPARATOR . $mod;

        $requiredFolders = ['res', 'res/textures'];
        foreach ($requiredFolders as $folder) {
            $folderPath = $modPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
            if (!is_dir($folderPath)) {
                $this->errors[] = "Dossiers requis manquant: {$folder}";
            }
        }
    }

    private function validateTextures(array|string $mod)
    {
        $texturesPath = $this->staging_path . DIRECTORY_SEPARATOR . $mod . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'textures';

        if (!is_dir($texturesPath)) {
            $this->errors[] = "Dossiers requis manquant: res/textures";
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($texturesPath));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if ($extension !== 'dds') {
                    $this->errors[] = "Format de texture invalide: {$file->getPathname()} not dds";
                }

                // Validate DDS properties
                $this->validateDDS($file->getPathname());
            }
        }
    }

    private function validateDDS($getPathname): void
    {
        //dd($getPathname);
        $command = "$this->magicCmd identify -format '%w:%h:%n' \"$getPathname\"";

        $process = Process::run($command);

        if(!$process->successful()) {
            $this->errors[] = "Erreur lors de l'analyse de la texture: {$getPathname}";
        }

        $values = str_replace("'", "", $process->output());
        $def = explode(':', $values);

        if ($def[0] !== $def[1]) {
            $this->errors[] = "Mauvais format de texture: {$getPathname}";
        }

        // Check if dimensions are a power of 2
        if (($def[0] & ($def[0] - 1)) !== 0) {
            $this->errors[] = "La dimension de la texture n'est pas une puissance de 2: {$getPathname}";
        }

        // Check minimum mipmap levels
        if ((int)$def[2] < 1) {
            $this->errors[] = "La texture n'à pas asset de mipmaps: {$getPathname}(".(int)$def[2].") pour 13";
        }
    }

    private function validateConfig(array|string $mod)
    {
        $configPath = $this->staging_path . DIRECTORY_SEPARATOR . $mod . DIRECTORY_SEPARATOR . 'mod.lua';

        if (!file_exists($configPath)) {
            $this->errors[] = "Fichier requis manquant: mod.lua";
        }

        $content = file_get_contents($configPath);
        if (strpos($content, 'name') === false || strpos($content, 'description') === false) {
            $this->errors[] = "Les clef requise 'name' et 'description' sont absente du fichier 'mod.lua'";
        }
    }

}
