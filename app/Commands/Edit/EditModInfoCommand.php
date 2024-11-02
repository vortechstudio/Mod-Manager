<?php

namespace App\Commands\Edit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class EditModInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:edit-info {mod_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edition des informations du mod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mod_path = $this->argument('mod_path');
        $this->menu($mod_path);
    }

    private function menu($selectedMod)
    {
        $menu = select(
            label: "Quelle information souhaitez modifier?",
            options: [
                "name" => "Nom du mod",
                "description" => "Description du mod",
                "version" => "Version du mod",
                "author" => "Auteur du mod",
                "tags" => "Tags du mod",
                "back" => "Retour",
            ]
        );

        match ($menu) {
            "name" => $this->editName($selectedMod),
            "description" => $this->editDescription($selectedMod),
            "version" => $this->editVersion($selectedMod),
            "author" => $this->editAuthor($selectedMod),
            "tags" => $this->editTags($selectedMod),
            "back" => $this->call('start', ['--without-config']),
        };
    }

    private function callStringLua($selectedMod)
    {
        $modFolder = $selectedMod;
        $stringsLuaPath = $modFolder.'/string.lua';

        if (!File::exists($stringsLuaPath)) {
            $this->error("Le fichier strings.lua n'a pas été trouvé pour le mod $selectedMod.");
            return;
        }

        return $stringsLuaPath;
    }

    private function callModLua($selectedMod)
    {
        $modFolder = $selectedMod;
        $stringsLuaPath = $modFolder.'/mod.lua';

        if (!File::exists($stringsLuaPath)) {
            $this->error("Le fichier mod.lua n'a pas été trouvé pour le mod $selectedMod.");
            return;
        }

        return $stringsLuaPath;
    }

    private function editName($selectedMod)
    {
        $name_mod = text("Quel est le nouveau nom du mod?", $selectedMod);

        $this->task("Mise à jour du nom du mod", function () use ($selectedMod, $name_mod) {
            $content = File::get($this->callStringLua($selectedMod));

            $translator = new \App\Services\Translator();
            if($translator->testApi()) {
                $titleFR = $name_mod;
                $titleEN = $translator->translate($titleFR, 'fr', 'en');
                $titleDE = $translator->translate($titleFR, 'fr', 'de');
            } else {
                $titleFR = $titleEN = $titleDE = $name_mod;
            }

            $content = preg_replace(
                [
                    '/(\["NAME_MOD"\] = ").*?(",)/m',  // Langue française
                    '/(en\s*=\s*{[^}]*\["NAME_MOD"\] = ").*?(",)/m', // Anglais
                    '/(de\s*=\s*{[^}]*\["NAME_MOD"\] = ").*?(",)/m'  // Allemand
                ],
                [
                    '$1' . addslashes($titleFR) . '$2',
                    '$1' . addslashes($titleEN) . '$2',
                    '$1' . addslashes($titleDE) . '$2'
                ],
                $content
            );

            // Écrire les modifications dans le fichier
            return File::put($this->callStringLua($selectedMod), $content) !== false;
        });
        $this->menu($selectedMod);
    }

    private function editDescription($selectedMod)
    {
        $desc_mod = textarea("Quel est la nouvelle description du mod?");

        $this->task("Mise à jour de la description du mod", function () use ($selectedMod, $desc_mod) {
            $content = File::get($this->callStringLua($selectedMod));

            $translator = new \App\Services\Translator();
            if($translator->testApi()) {
                $descFR = $desc_mod;
                $descEN = $translator->translate($descFR, 'fr', 'en');
                $descDE = $translator->translate($descFR, 'fr', 'de');
            } else {
                $descFR = $descEN = $descDE = $desc_mod;
            }

            $content = preg_replace(
                [
                    '/(\["DESC_MOD"\] = ").*?(",)/m',  // Langue française
                    '/(en\s*=\s*{[^}]*\["DESC_MOD"\] = ").*?(",)/m', // Anglais
                    '/(de\s*=\s*{[^}]*\["DESC_MOD"\] = ").*?(",)/m'  // Allemand
                ],
                [
                    '$1' . addslashes($descFR) . '$2',
                    '$1' . addslashes($descEN) . '$2',
                    '$1' . addslashes($descDE) . '$2'
                ],
                $content
            );

            // Écrire les modifications dans le fichier
            return File::put($this->callStringLua($selectedMod), $content) !== false;
        });
        $this->menu($selectedMod);
    }

    private function editVersion($selectedMod)
    {
        $version = text("Quelle est la nouvelle version du mod?");

        $this->task("Mise à jour de la version du mod", function () use ($selectedMod,$version) {
            $content = File::get($this->callModLua($selectedMod));

            $content = preg_replace(
                '/(minorVersion\s*=\s*)\d+/',
                '${1}' . $version,
                $content
            );

            return File::put($this->callModLua($selectedMod), $content) !== false;
        });
        $this->menu($selectedMod);
    }

    private function editAuthor($selectedMod)
    {
        $newAuthors  = text("Entrez les auteurs et rôles au format 'Nom:Role' séparés par des virgules");

        $this->task("Mise à jour des auteurs du mod", function () use ($selectedMod, $newAuthors) {
            $content = File::get($this->callModLua($selectedMod));

            $authorsArray = array_map(function($author) {
                [$name, $role] = array_map('trim', explode(':', $author));
                return "                { name = \"$name\", role = \"$role\" }";
            }, explode(',', $newAuthors));

            $formattedAuthors = "authors = {\n" . implode(",\n", $authorsArray) . "\n";

            $updateContent = preg_replace('/authors\s*=\s*{.*?},/s', $formattedAuthors, $content);
            return File::put($this->callModLua($selectedMod), $updateContent) !== false;
        });
        $this->menu($selectedMod);
    }

    private function editTags($selectedMod)
    {
        $tags = text("Entrez les nouveaux tags, séparés par des virgules");

        $this->task("Mise à jour des tags du mod", function () use ($selectedMod, $tags) {
            $content = File::get($this->callModLua($selectedMod));

            $tagsArray = array_map('trim', explode(',', $tags));
            $formattedTags = "tags = { \"" . implode('", "', $tagsArray) . "\" },";

            $updatedContent = preg_replace('/tags\s*=\s*{.*?},/s', $formattedTags, $content);

            if ($updatedContent === null) {
                $this->error("Erreur lors de la mise à jour des tags.");
                return false;
            }

            return File::put($this->callModLua($selectedMod), $updatedContent) !== false;
        });
        $this->menu($selectedMod);
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
