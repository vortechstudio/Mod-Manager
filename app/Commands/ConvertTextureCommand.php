<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
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

    protected $staging_path = '';
    protected $magicCmd = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getConfig();
        if(exec('magick --version', $output) === false) {
            $this->magicCmd = getcwd().'/bin/imagemagick/magick.exe';
        } else {
            $this->magicCmd = 'magick';
        }
        $conversionType = $this->choice('Quel type de conversion souhaitez-vous effectuer ?', ['TGA -> DDS', 'DDS -> TGA'], 0);

        $mods = $this->getMods();
        if (empty($mods)) {
            $this->error("Aucun mod trouvé dans le staging_area.");
            return;
        }
        $selectedMod = $this->choice('Sélectionnez un mod à traiter :', $mods);

        $fileExtension = $conversionType === 'TGA -> DDS' ? 'tga' : 'dds';
        $foldersWithFiles = $this->getFoldersWithFiles($selectedMod, $fileExtension);
        if (empty($foldersWithFiles)) {
            $this->error("Aucun dossier contenant des fichiers .$fileExtension trouvé dans le mod sélectionné.");
            return;
        }

        $selectedFolder = $this->choice('Sélectionnez un dossier pour effectuer la conversion :', $foldersWithFiles);
        if (!$this->confirm("Voulez-vous vraiment effectuer la conversion de $fileExtension dans le dossier $selectedFolder ?")) {
            $this->info("Conversion annulée.");
            $this->call('start', ['--without-config']);
            return;
        }

        $this->task("Conversion en cours...", function () use ($selectedFolder, $fileExtension, $conversionType) {
            $this->convertFiles($selectedFolder, $fileExtension, $conversionType);
        });

        if ($this->confirm("Souhaitez-vous supprimer les fichiers originaux .$fileExtension après la conversion ?")) {
            $this->deleteOriginalFiles($selectedFolder, $fileExtension);
            $this->info("Fichiers originaux .$fileExtension supprimés.");
        }

        $this->call('start', ['--without-config']);
    }

    private function getConfig()
    {
        $config_file = getcwd()."/config.json";
        $config = json_decode(file_get_contents($config_file), true);
        $this->staging_path = $config['staging_path'];
    }

    private function getMods()
    {
        $mods = [];
        foreach (File::directories($this->staging_path) as $modPath) {
            $mods[] = basename($modPath);
        }
        return $mods;
    }

    private function getFoldersWithFiles($selectedMod, $fileExtension)
    {
        $folders = [];
        $modPath = $this->staging_path . '/' . $selectedMod;

        // Appel récursif pour obtenir tous les dossiers contenant des fichiers du type de conversion
        $this->findFoldersWithFiles($modPath, $fileExtension, $folders);

        return $folders;
    }

    private function findFoldersWithFiles($directory, $fileExtension, &$folders)
    {
        // Vérifie si le dossier contient au moins un fichier du type voulu
        if (count(File::glob("$directory/*.$fileExtension")) > 0) {
            $folders[] = $directory;
        }

        // Récupère tous les sous-dossiers et effectue un appel récursif pour chaque sous-dossier
        foreach (File::directories($directory) as $subDirectory) {
            $this->findFoldersWithFiles($subDirectory, $fileExtension, $folders);
        }
    }

    private function convertFiles($selectedFolder, $fileExtension, $conversionType)
    {
        $this->info("Conversion en cours dans le dossier : $selectedFolder...");

        $files = File::glob("$selectedFolder/*.$fileExtension");

        foreach ($files as $file) {
            $outputFile = str_replace(".$fileExtension", $conversionType === 'TGA -> DDS' ? '.dds' : '.tga', $file);

            if ($conversionType === 'TGA -> DDS') {
                $command = $this->getTgaToDdsCommand($file, $outputFile);
            } else {
                $command = "\"{$this->magicCmd}\" convert \"$file\" \"$outputFile\"";
            }

            if (exec($command, $output) === false) {
                return false;
            }
        }

        return true;
    }

    private function getTgaToDdsCommand($inputFile, $outputFile)
    {
        $command = "\"{$this->magicCmd}\" \"$inputFile\" -flip -define dds:mipmaps=13";

        // Vérifie si le fichier a un canal alpha ou contient "normal" dans le nom
        if (strpos($inputFile, 'normal') !== false) {
            $command .= " -compress DXT5"; // Compression DXT5 pour normal maps
        } else {
            $command .= " -compress DXT1"; // Compression DXT1 pour les autres fichiers
        }

        return $command . " \"$outputFile\"";
    }

    private function deleteOriginalFiles($selectedFolder, $fileExtension)
    {
        $files = File::glob("$selectedFolder/*.$fileExtension");
        foreach ($files as $file) {
            File::delete($file);
        }
    }
}
