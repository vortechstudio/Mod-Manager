<?php

namespace App\Commands\Edit;

use App\Services\Translator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;

class CreateConstructionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:create-construction {mod_path}';

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
        $mod_path = $this->argument('mod_path');
        $type = $this->choice(
            question: 'Quelle type de construction souhaitez-vous créer?',
            choices: [
                "ASSET_DEFAULT",
                "ASSET_TRACK",
            ],
        );

        match($type) {
            "ASSET_DEFAULT" => $this->createConstruction($mod_path, 'asset_default'),
            "ASSET_TRACK" => $this->createConstruction($mod_path, 'asset_track'),
        };
        $this->call('editmod');
    }

    protected function createConstruction($mod_path, $type)
    {
        $this->line("Création d'une nouvelle construction de type $type dans le mod $mod_path");
        $models = collect();
        $name = $this->ask('Quel est le nom de la construction?');
        $description = $this->ask('Quelle est la description de la construction?');
        $categories = $this->ask('Quelles sont les catégories de la construction? (Séparez les catégories par une virgule)');
        $buildMode = $this->choice(
            question: 'Quel est le mode de construction?',
            choices: [
                "MULTI",
                "SINGLE",
                "BRUSH",
            ],
        );
        $this->askForModels($mod_path, $models, $name, $description, $buildMode, $categories, $type);
    }

    protected function askForModels($mod_path, $models, $name, $description, $buildMode, $categories, $type)
    {
        $ms = select(
            label: 'Ajouter un modèle à la construction?',
            options: [
                "add" => "Ajouter",
                "quit" => "Quitter",
            ],
            default: "add",
        );

        match($ms) {
            "add" => $this->addModel($mod_path, $models, $name, $description, $buildMode, $categories, $type),
            "quit" => $this->buildConstruct($mod_path, $models, $name, $description, $buildMode, $categories, $type),
        };
    }

    private function addModel($mod_path, $models, $name, $description, $buildMode, $categories, $type)
    {
        $fileExtension = 'mdl';
        $folderWithFiles = $this->getFolderWithFiles($mod_path, $fileExtension);
        if ($folderWithFiles->count() === 0) {
            $this->error("Aucun dossier contenant des fichiers .$fileExtension trouvé dans le mod sélectionné.");
            return;
        }

        if($folderWithFiles->count() === 1) {
            $selectedFile = $folderWithFiles->toArray()[0];
        } else {
            $selectedFile = $this->choice("Quel modèle souhaitez-vous ajouter?", $folderWithFiles->toArray());
        }

        $models->push($selectedFile);
        $this->askForModels($mod_path, $models, $name, $description, $buildMode, $categories, $type);
    }

    private function getFolderWithFiles($mod_path, $fileExtension)
    {
        $folders = collect();
        $files = File::allFiles($mod_path.'/res');

        foreach ($files as $file) {
            if($file->getExtension() === $fileExtension) {
                $folders->push($file->getPathname());
            }
        }


        return $folders;
    }

    public function buildConstruct($mod_path, $models, $name, $description, $buildMode, $categories, $type)
{
    $this->task("Création de la construction dans le mod $mod_path", function () use ($mod_path, $models, $name, $description, $buildMode, $categories, $type) {
        $nameUp = Str::slug($name, '_')."_NAME";
        $descUp = Str::slug($name, '_')."_DESC";

        // Mise à jour des traductions
        $this->insertToStringFile($mod_path, $nameUp, $descUp, $name, $description);

        // Formattage des catégories
        $categoriesArray = explode(',', $categories);
        $formattedCategories = implode('", "', array_map('trim', $categoriesArray));

        // Formatage des modèles
        $formattedModels = implode(",\n\t\t\t", $models->map(function ($modelPath) use ($mod_path) {
            // Extraction du chemin relatif
            $relativeModelPath = str_replace($mod_path . '/res/models/model/', '', $modelPath);

            // Remplacement des séparateurs de chemin pour unifier au format attendu
            $relativeModelPath = str_replace('\\', '/', $relativeModelPath);

            return '"' . $relativeModelPath . '"';
        })->toArray());

        $fileName = Str::slug($name, '_') . ".con";
        $tgaName = Str::slug($name, '_') . ".tga";
        $filePath = $mod_path . "/res/construction/" . $this->getTypeFormatedString($type) . "/" . $fileName;
        $upperType = Str::upper($type);

        $content = <<<LUA
local constructionutil = require "constructionutil"

local function map(a, f)
    local res = {}
    if a and type(a) == "table" then
        for _, v in ipairs(a) do
            table.insert(res, f(v))
        end
    end
    return res
end

local function intRange(start, stop, step)
    local a = {}
    local i = start
    while i <= stop do
        table.insert(a, i)
        i = i + step
    end
    return a
end

local models = {
    $formattedModels
}

function data()
    return {
        type = "$upperType",
        description = {
            name = _("$nameUp"),
            description = _("$descUp"),
            icon = "ui/construction/{$this->getTypeFormatedString($type)}/$tgaName",
        },
        availability = {
            yearFrom = 1850,
        },
        buildMode = "$buildMode",
        categories = { "$formattedCategories" },
        order = 11,
        skipCollision = true,
        autoRemovable = true,
        params = {
            {
                key = "batiment",
                uiType = "COMBOBOX",
                name = _("$nameUp"),
                values = map(models, function(v) return v end)
            },
LUA;

        // Paramètres conditionnels pour "asset_track"
        if ($type === "asset_track") {
            $content .= <<<LUA

            {
                key = "yoffset",
                name = _("yOffset"),
                values = map(intRange(-100, 100, 5), function(i) return ""..i.." cm" end),
                uiType = "SLIDER",
                defaultIndex = math.floor(#intRange(-100, 100, 5) / 2)
            },
            {
                key = "yoffset2",
                name = _("yOffset large"),
                values = map(intRange(-100, 100, 5), function(i) return ""..i.." cm" end),
                uiType = "SLIDER",
                defaultIndex = math.floor(#intRange(-100, 100, 5) / 2)
            },
LUA;
        }

        $content .= <<<LUA
        },

$content .= <<<LUA
        updateFn = function(params)
            local model = models[params.batiment + 1]
LUA;

    // Ajouter les calculs de yOffset et yOffset2 uniquement si le type est "asset_track"
    if ($type === "asset_track") {
        $content .= <<<LUA
            local yOffset = intRange(-100, 100, 5)[params.yoffset + 1] / 100
            local yOffset2 = intRange(-100, 100, 5)[params.yoffset2 + 1] / 10
LUA;
    } else {
        // Si asset_default, définir yOffset et yOffset2 à zéro pour éviter les erreurs
        $content .= <<<LUA
            local yOffset = 0
            local yOffset2 = 0
LUA;
    }

    // Compléter le reste du bloc avec la transformation
    $content .= <<<LUA
            local result = {}

            result.models = {
                {
                    id = model,
                    transf = constructionutil.rotateTransf(params, {1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0.7, 5 + yOffset + yOffset2, 0, 1}),
                }
            }

            result.terrainAlignmentLists = {
                {
                    type = "GREATER",
                    faces = {},
                }
            }

            return result
        end
LUA;

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $content);
    });
    $this->call('editmod');
}


    private function getTypeFormatedString($type)
    {
        return match($type) {
            "asset_default" => "asset",
            "asset_track"=> "track",
        };
    }

    private function insertToStringFile($mod_path, $nameUp, $descUp, $name, $description)
    {
        $stringFile = $mod_path . "/strings.lua";
        $translator = new Translator();

        $nameFR = $name;
        $descriptionFR = $description;
        $nameEN = $translator->translate($name, 'fr', 'en');
        $nameDE = $translator->translate($name, 'fr', 'de');
        $descriptionEN = $translator->translate($description, 'fr', 'en');
        $descriptionDE = $translator->translate($description, 'fr', 'de');

        $newTranslations = [
            'fr' => [
                "$nameUp" => $nameFR,
                "$descUp" => $descriptionFR,
            ],
            'en' => [
                "$nameUp" => $nameEN,
                "$descUp" => $descriptionEN,
            ],
            'de' => [
                "$nameUp" => $nameDE,
                "$descUp" => $descriptionDE,
            ]
        ];

        if (!File::exists($stringFile)) {
            $initialContent = "function data()\n    return {\n";
            foreach ($newTranslations as $lang => $translations) {
                $initialContent .= "        $lang = {\n";
                foreach ($translations as $key => $value) {
                    $initialContent .= "            [\"$key\"] = \"$value\",\n";
                }
                $initialContent .= "        },\n";
            }
            $initialContent .= "    }\nend\n";
            File::put($stringFile, $initialContent);
        } else {
            $content = File::get($stringFile);
            foreach ($newTranslations as $lang => $translations) {
                foreach ($translations as $key => $value) {
                    $pattern = "/(\\[$key\\] = \").*?(\",)/";
                    $replacement = '$1' . addslashes($value) . '$2';
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, $replacement, $content);
                    } else {
                        $content = preg_replace("/($lang\\s*=\\s*{)/", "$1\n            [\"$key\"] = \"" . addslashes($value) . "\",", $content);
                    }
                }
            }
            File::put($stringFile, $content);
        }
    }
}
