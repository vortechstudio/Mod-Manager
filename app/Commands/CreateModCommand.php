<?php

namespace App\Commands;

use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Str;
use App\Services\Translator;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class CreateModCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createmod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Création de Mod TF2';
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
        $name_mod = text(
            label: 'Quel est le nom de votre mod ?',
            hint: "Tapez le nom du mod tel qu'il doit apparaitre dans le mod.lua, le formatage sera effectuer automatiquement"
        );
        $this->createMod($name_mod);
    }

    public function createMod(string $name_mod)
    {
        Log::info("Démarage de la création de mod");
        Log::info("Date et Heure de début : ".date('d-m-Y H:i:s'));
        $mod_title = $name_mod;
        $name_mod = Str::slug($name_mod.'_1', '_');


        Log::info("Nom du Mod : $name_mod");
        Log::info("Vérification du dossier de staging");
        if(File::isDirectory($this->staging_path.'/'.$name_mod)) {
            $this->error('Le dossier de mod existe déjà');
            $this->call('start', ['--without-config']);
        } else {
            $this->createModDirectory($name_mod);
            $this->createModLuaFile($name_mod);
            $this->createStringLuaFile($name_mod, $mod_title);
            $this->createImageTgaFile($name_mod);
            $this->call('start', ['--without-config']);
        }



    }

    private function getConfig()
    {
        $config_file = getcwd()."/config.json";
        $config = json_decode(file_get_contents($config_file), true);
        $this->staging_path = $config['staging_path'];
    }

    public function createModDirectory(string $name_mod)
    {
        $folders = collect([
            'res',
            'res/audio/effects',
            'res/config/multiple_unit',
            'res/config/sound_set',
            'res/config/ui',
            'res/construction',
            'res/construction/asset',
            'res/models/animations',
            'res/models/materials',
            'res/models/mesh',
            'res/models/model',
            'res/scripts',
            'res/textures/models',
            'res/textures/ui',
            'res/textures/ui/construction/asset',
            'res/textures/ui/construction/categories',
        ]);

        $this->task('Création des dossiers', function () use ($folders, $name_mod) {
            try {
                foreach ($folders as $folder) {
                    File::makeDirectory($this->staging_path.'/'.$name_mod.'/'.$folder, 0777, true, true);
                }
                return true;
            } catch (Exception $e) {
                return false;
            }

        });
    }

    public function createModLuaFile(string $name_mod)
    {
        $authors = text(
            label: 'Quels sont les auteurs de votre mod? (séparez les auteurs par une virgule)',
        );

        $tags = text(
            label: 'Quels sont les tags de votre mod? (séparez les tags par une virgule)',
        );

        $this->task('Création du fichier mod.lua', function () use ($name_mod, $authors, $tags) {
            // Transformation des auteurs en format Lua avec `name` et `role`
            $authorsArray = array_map('trim', explode(',', $authors));
            $authorsLua = implode(",\n                ", array_map(fn($author) => "{
                        name = \"$author\",
                        role = \"CREATOR\"
                    }", $authorsArray));
            $authorsLua = "authors = {\n                $authorsLua\n            },";

            // Transformation des tags en tableau Lua
            $tagsArray = array_map('trim', explode(',', $tags));
            $tagsLua = "tags = { \"" . implode('", "', $tagsArray) . "\" },";

            // Génération du contenu de `mod.lua`
            $content = <<<LUA
function data()
return {
        info = {
            minorVersion = 0,
            severityAdd = 'NONE',
            severityRemove = 'NONE',
            name = _("NAME_MOD"),
            description = _("DESC_MOD"),
            $authorsLua
            $tagsLua
            visible = true,
        }
    }
end
LUA;

            $filePath = $this->staging_path.'/'.$name_mod.'/mod.lua';
            return File::put($filePath, $content) !== false;
        });
    }

    public function createStringLuaFile(string $name_mod, string $mod_title)
    {
        $description = textarea(
            label:'Définissez la description de votre mod',
        );

        $translator = new Translator();

        if($translator->testApi()) {
            $descriptionFR = $description;
            $titleFR = $mod_title;

            $titleEN = $translator->translate($titleFR, 'fr', 'en');
            $titleDE = $translator->translate($titleFR,'fr', 'de');

            $descriptionEN = $translator->translate($descriptionFR,'fr', 'en');
            $descriptionDE = $translator->translate($descriptionFR,'fr', 'de');
        } else {
            $descriptionFR = $description;
            $titleFR = $mod_title;

            $titleEN = $mod_title;
            $titleDE = $mod_title;

            $descriptionEN = $descriptionFR;
            $descriptionDE = $descriptionFR;
        }


        $this->task('Création du fichier strings.lua', function () use ($name_mod, $titleFR, $titleEN, $titleDE, $descriptionFR, $descriptionEN, $descriptionDE) {
            $content = <<<LUA
function data()
    return {
        fr = {
            ["NAME_MOD"] = "$titleFR",
            ["DESC_MOD"] = "$descriptionFR",
        },
        en = {
            ["NAME_MOD"] = "$titleEN",
            ["DESC_MOD"] = "$descriptionEN",
        },
        de = {
            ["NAME_MOD"] = "$titleDE",
            ["DESC_MOD"] = "$descriptionDE",
        },
    }
end
LUA;
        $filePath = $this->staging_path.'/'.$name_mod.'/string.lua';
        return File::put($filePath, $content) !== false;

        });
    }

    public function createImageTgaFile(string $name_mod)
    {
        $width = 512;
        $height = 512;
        $outputPath = $this->staging_path.'/'.$name_mod.'/image_00.tga';

        $this->task('Création du fichier image_00.tga', function () use ($width, $height, $outputPath) {
            $command = "\"{$this->magicCmd}\" -size {$width}x{$height} xc:white \"{$outputPath}\"";
            exec($command, $output, $returnVar);
            if ($returnVar === 0) {return true;} else {return false;}
        });


    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
