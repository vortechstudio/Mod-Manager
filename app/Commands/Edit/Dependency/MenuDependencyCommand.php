<?php

namespace App\Commands\Edit\Dependency;

use App\Services\DependancyManager;
use Illuminate\Console\Command;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MenuDependencyCommand extends Command
{
    protected $signature = 'menu:dependency {mod_path}';
    protected $description = 'Manage mod dependencies for a mod.';

    protected string $dependenciesFile = 'dependencies.json';
    protected string $modLuaFile = 'mod.lua';
    protected string $modPath = '';

    public function handle(): void
    {
        $this->modPath = $this->argument('mod_path');

        // Vérifie et initialise le fichier dependencies.json
        $this->initializeDependenciesFile();

        // Charge les dépendances depuis le fichier JSON
        $dependencies = $this->readDependencies();

        // Affiche le menu d'action
        $menuAction = select(
            label: 'Que souhaitez-vous faire ?',
            options: [
                "list" => "Lister les dépendences",
                "add" => "Ajouter une dépendence",
                "remove" => "Supprimer une dépendence",
                "push" => "Envoyer les modifications",
                "return" => "Retourner au menu principal",
            ],
        );

        if ($menuAction == 'add' || $menuAction == 'remove') {
            $dependence = text(label: "ID de la dépendance (nom_du_mod)");
        } else {
            $dependence = '';
        }

        match ($menuAction) {
            "list" => $this->listDependencies($dependencies),
            "add" => $this->addDependency($dependencies, $dependence),
            "remove" => $this->removeDependency($dependencies, $dependence),
            "push" => $this->updateModLuaDependencies($dependencies),
            "return" => $this->call('editmod')
        };
    }

    protected function initializeDependenciesFile(): void
    {
        $jsonPath = $this->modPath . '/' . $this->dependenciesFile;
        $luaPath = $this->modPath . '/' . $this->modLuaFile;

        if (!file_exists($jsonPath)) {
            if (!file_exists($luaPath)) {
                $this->error("Le fichier mod.lua n'existe pas.");
                return;
            }

            // Extraire les dépendances depuis mod.lua
            $modLuaContent = file_get_contents($luaPath);
            preg_match('/requiredMods\s*=\s*\{(.*?)\}/s', $modLuaContent, $matches);

            $dependencies = [];
            if (!empty($matches[1])) {
                preg_match_all('/modId\s*=\s*"([^"]+)"/', $matches[1], $modIds);
                foreach ($modIds[1] as $modId) {
                    $dependencies[] = ['modId' => $modId];
                }
            }

            // Crée le fichier JSON avec les dépendances extraites
            file_put_contents($jsonPath, json_encode($dependencies, JSON_PRETTY_PRINT));
            $this->info("Le fichier dependencies.json a été initialisé à partir de mod.lua.");
        }
    }

    protected function readDependencies(): array
    {
        $filePath = $this->modPath . '/' . $this->dependenciesFile;
        if (!file_exists($filePath)) {
            return [];
        }
        return json_decode(file_get_contents($filePath), true);
    }

    protected function writeDependencies(array $dependencies): void
    {
        $filePath = $this->modPath . '/' . $this->dependenciesFile;
        file_put_contents($filePath, json_encode($dependencies, JSON_PRETTY_PRINT));
    }

    protected function addDependency(array &$dependencies, string $modId): void
    {
        if (!$modId) {
            $this->error('Vous devez spécifier un modId pour ajouter une dépendance.');
            return;
        }

        foreach ($dependencies as $dependency) {
            if ($dependency['modId'] === $modId) {
                $this->info("La dépendance '{$modId}' existe déjà.");
                return;
            }
        }

        $dependencies[] = ['modId' => $modId];
        $this->writeDependencies($dependencies);
        $this->info("La dépendance '{$modId}' a été ajoutée.");
        $this->handle();
    }

    protected function listDependencies(array $dependencies): void
    {
        if (empty($dependencies)) {
            $this->info('Aucune dépendance trouvée.');
            return;
        }

        $this->table(['Mod ID'], $dependencies);
        $this->handle();
    }

    protected function removeDependency(array &$dependencies, string $modId): void
    {
        if (!$modId) {
            $this->error('Vous devez spécifier un modId pour supprimer une dépendance.');
            return;
        }

        $filtered = array_filter($dependencies, fn($dependency) => $dependency['modId'] !== $modId);
        if (count($filtered) === count($dependencies)) {
            $this->info("La dépendance '{$modId}' n'existe pas.");
            return;
        }

        $dependencies = $filtered;
        $this->writeDependencies($dependencies);
        $this->info("La dépendance '{$modId}' a été supprimée.");
        $this->handle();
    }

    protected function updateModLuaDependencies(array $dependencies): void
    {
        $luaPath = $this->modPath . '/' . $this->modLuaFile;

        if (!file_exists($luaPath)) {
            $this->error("Le fichier mod.lua n'existe pas.");
            return;
        }

        $modLuaContent = file_get_contents($luaPath);

        // Génère le contenu pour la clé requiredMods
        $dependenciesLua = "requiredMods = {\n";
        foreach ($dependencies as $dependency) {
            $dependenciesLua .= "            {\n";
            $dependenciesLua .= "                modId = \"" . $dependency['modId'] . "\",\n";
            $dependenciesLua .= "            },\n";
        }
        $dependenciesLua = rtrim($dependenciesLua, ",\n") . "\n        }";

        // Insère ou met à jour requiredMods après "visible = true,"
        $updatedLuaContent = preg_replace(
            '/(visible\s*=\s*true,)/s',
            "$1\n        $dependenciesLua,",
            $modLuaContent
        );

        if ($updatedLuaContent) {
            file_put_contents($luaPath, $updatedLuaContent);
            $this->info("Les dépendances ont été mises à jour dans mod.lua.");
            $this->handle();
        } else {
            $this->error("Impossible de mettre à jour mod.lua. Vérifiez la structure du fichier.");
        }
    }
}
