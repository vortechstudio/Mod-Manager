<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;

class ConvertTextureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:texture';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected string $staging_path = '';
    protected string $nvidiaCmd = '';
    protected array $supportedConversions = [
        'tga_to_dds',
        'dds_to_tga',
        'png_to_tga',
        'png_to_dds',
    ];

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
        $this->nvidiaCmd = getcwd().'/bin/nvidia/nvtt_export.exe';
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourceExtension = $this->choice("Quelle extension souhaitez-vous convertir ?", ['tga', 'dds', 'png'], 0);
        $destinationExtension = $this->choice("Quelle extension souhaitez-vous obtenir ?", ['tga', 'dds', 'png'], 0);

        $mods = $this->getMods($this->staging_path);
        if (empty($mods)) {
            $this->error('No mods found in the staging area.');
            return Command::FAILURE;
        }
        $mod = $this->choice('Selectionner un mod', $mods);
        $folders = $this->getFoldersWithExtension($mod, $sourceExtension);

        if (empty($folders)) {
            $this->error("No folders containing '.{$sourceExtension}' files were found in the mod '{$mod}'.");
            return Command::FAILURE;
        }

        $folder = $this->choice('Selectionner un dossier à traiter', $folders);
        $this->info("Conversion des textures {$sourceExtension} vers {$destinationExtension} dans le mod '{$mod}', dossier {$folder}");

        $deleteSource = $this->confirm("Voulez-vous supprimer les fichiers {$sourceExtension} après conversion ?", true);

        try {
            $this->processConversion($mod, $folder, $sourceExtension, $destinationExtension, $deleteSource);
            $this->info('Conversion completed successfully.');
            $this->call('start', ['--without-config']);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("An error occurred during conversion: {$e->getMessage()}.");
            return Command::FAILURE;
        }


    }


    private function getMods($stagingPath)
    {
        $mods = [];
        foreach (File::directories($stagingPath) as $modPath) {
            $mods[] = basename($modPath);
        }
        return $mods;
    }

    private function getFoldersWithExtension(array|string $mod, array|string $sourceExtension): array
    {
        $modPath = $this->staging_path . DIRECTORY_SEPARATOR . $mod;

        $folders = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modPath));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === strtolower($sourceExtension)) {
                $folders[] = dirname($file->getPathname());
            }
        }

        return array_unique($folders);
    }

    private function processConversion(array|string $mod, array|string $folder, array|string $sourceExtension, array|string $destinationExtension, bool $deleteSource)
    {
        $sourcePath = $folder;

        if (!is_dir($sourcePath)) {
            throw new \Exception("The folder '{$folder}' does not exist in the mod '{$mod}'.");
        }

        $files = glob("{$sourcePath}/*.{$sourceExtension}");

        if (empty($files)) {
            throw new \Exception("No files with the extension '{$sourceExtension}' found in '{$folder}'.");
        }

        foreach ($files as $file) {
            $destinationFile = preg_replace("/\.{$sourceExtension}$/i", ".{$destinationExtension}", $file);

            $this->task("Conversion de la texture {$file} en {$destinationFile}", function () use ($file, $destinationFile, $sourceExtension, $destinationExtension) {
                try {
                    $this->convertFile($file, $destinationFile, $sourceExtension, $destinationExtension);
                    return true;
                } catch (\Exception $e) {
                    $this->error("An error occurred during conversion: {$e->getMessage()}.");
                    return false;
                }
            });


            if ($deleteSource) {
                $this->task("Suppression de la texture original", function () use ($file) {
                    try {
                        $this->deleteFile($file);
                        return true;
                    } catch (\Exception $e) {
                        return false;
                    }
                });
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function convertFile(string $source, string $destination, string $sourceExtension, string $destinationExtension)
    {
        $convert = Process::run("\"{$this->nvidiaCmd}\" -f 23 --mips --save-flip-y \"$source\" -o \"$destination\"");

        if($convert->failed()) {
            throw new \Exception("Erreur de convertion: ".$convert->command());
        }
    }

    private function deleteFile(mixed $file)
    {
        if (unlink($file)) {
            $this->info("Deleted source file: {$file}");
        } else {
            $this->error("Failed to delete source file: {$file}");
        }
    }


}
