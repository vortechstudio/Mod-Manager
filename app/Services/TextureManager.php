<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TextureManager
{
    protected $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function handleUnusedFiles($modPath)
    {
        // Paths
        $materialDir = $modPath . '/res/models/material';
        $modelDir = $modPath . '/res/models/model';
        $textureDir = $modPath . '/res/textures/models';

        // Étape 1 : Identifier les fichiers .mtl non référencés dans les fichiers .mdl
        $unreferencedMtlFiles = $this->findUnreferencedFiles($materialDir, $modelDir, 'mtl', 'mdl');

        if (!empty($unreferencedMtlFiles)) {
            $this->command->info("Fichiers .mtl non référencés dans les fichiers .mdl :");
            foreach ($unreferencedMtlFiles as $file) {
                $this->command->line(" - $file");
            }
        } else {
            $this->command->info("Aucun fichier .mtl non référencé trouvé.");
        }

        // Étape 2 : Identifier les fichiers .dds non référencés dans les fichiers .mtl
        $unreferencedDdsFiles = $this->findUnreferencedFiles($textureDir, $materialDir, 'dds', 'mtl');

        if (!empty($unreferencedDdsFiles)) {
            $this->command->info("Fichiers .dds non référencés dans les fichiers .mtl :");
            foreach ($unreferencedDdsFiles as $file) {
                $this->command->line(" - $file");
            }

            if ($this->command->confirm("Voulez-vous supprimer ces fichiers .dds non référencés ?")) {
                foreach ($unreferencedDdsFiles as $file) {
                    File::delete($file);
                    $this->command->info("Supprimé : $file");
                }
            }
        } else {
            $this->command->info("Aucun fichier .dds non référencé trouvé.");
        }
    }

    private function findUnreferencedFiles($searchDir, $referenceDir, $searchExt, $referenceExt)
    {
        $unreferencedFiles = [];

        // Trouver tous les fichiers avec l'extension à rechercher
        $searchFiles = $this->getAllFilesRecursively($searchDir, $searchExt);

        foreach ($searchFiles as $searchFile) {
            $baseName = basename($searchFile);
            $isReferenced = false;

            // Vérifier si le fichier est référencé dans les fichiers du répertoire de référence
            $referenceFiles = $this->getAllFilesRecursively($referenceDir, $referenceExt);

            foreach ($referenceFiles as $referenceFile) {
                $content = File::get($referenceFile);
                if (str_contains($content, $baseName)) {
                    $isReferenced = true;
                    break;
                }
            }

            if (!$isReferenced) {
                $unreferencedFiles[] = $searchFile;
            }
        }

        return $unreferencedFiles;
    }

    /**
     * Parcourir les fichiers d'un dossier de manière récursive.
     *
     * @param string $path Chemin du dossier à parcourir.
     * @param string $extension Extension des fichiers à rechercher.
     * @return \Illuminate\Support\Collection
     */
    private function getAllFilesRecursively($path, $extension)
    {
        $files = collect();

        if (File::exists($path)) {
            $allFiles = File::allFiles($path);
            foreach ($allFiles as $file) {
                if ($file->getExtension() === $extension) {
                    $files->push($file->getRealPath());
                }
            }
        }

        return $files;
    }
}
