<?php

namespace App\Commands\Edit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;

class CompileModCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:compile {mod_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie et prépare le mod pour le déploiement.';
    protected $luaCmd = '';
    protected $magicCmd = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modPath = $this->argument('mod_path');
        $this->luaCmd = getcwd().'/bin/lua/luac.exe';
        if(exec('magick --version', $output) === false) {
            $this->magicCmd = getcwd().'/bin/imagemagick/magick.exe';
        } else {
            $this->magicCmd = 'magick';
        }

        // Vérification des prerequisites
        $this->checkDependencies($modPath);

        // Vérification et compilation du mod
        $this->compileMod($modPath);
        $this->info("Mod préparé pour le déploiement.");
    }

    protected function checkDependencies($modPath)
    {
        $error = 0;
        $this->info("Vérification des prerequisites pour le mod : $modPath");
        // Vérifier les prerequisites (ex : existence des dossiers, présence des textures etc.)
        $this->task("Vérification de l'arborescence du mod", function () use ($modPath, $error) {
            $folders = collect(["res", "res/models", "res/textures", "res/textures/ui"]);
            foreach ($folders as $folder) {
                if(!File::isDirectory($folder)) {
                    $error++;
                    return false;
                }
            }
            return true;
        });
        $this->task("Vérification des scripts LUA", function () use ($modPath, $error) {
            $files = File::allFiles($modPath);
            $hasError = false;

            foreach ($files as $file) {
                if($file->getExtension() == "lua") {
                    $this->info("Vérification du script : ". $file->getFileName());
                    $result = $this->checkLuaSyntax($file->getPathname());

                    if($result !== true) {
                        $hasError = true;
                        $error++;
                        $this->error("Erreur dans le script : {$file->getFileName()}: $result" );
                        return false;
                    } else {
                        return true;
                    }
                }
            }

            if ($hasError) {
                $this->error("Des erreurs de syntaxe Lua ont été détectées.");
                return false;
            } else {
                $this->info("Tous les fichiers Lua sont valides.");
                return true;
            }
        });
        $this->task("Vérification des Textures", function () use ($modPath, $error) {
            $texturePath = realpath("$modPath/res/textures");
            if (!$texturePath || !is_dir($texturePath)) {
                $this->warn("Le dossier des textures n'existe pas ou est inaccessible : $modPath/res/textures");
                $error++;
                return false;
            }

            $textures = [];
            // Utilisation de RecursiveDirectoryIterator pour rechercher tous les .dds
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($texturePath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'dds') {
                    $textures[] = $file->getPathname();
                }
            }

            if (empty($textures)) {
                $this->warn("Aucune texture .dds trouvée dans : $texturePath");
                $error++;
                return false;
            }

            foreach ($textures as $texture) {
                $this->info("Vérification de la texture : $texture");

                if (!$this->hasMipmaps($texture)) {
                    $this->warn("La texture $texture n'a pas le nombre de mipmaps requis.");
                    $error++;
                    return false;
                }

                $size = filesize($texture) / (1024 * 1024); // Taille en Mo
                if ($size > 5) {  // Limite de taille en MB
                    $this->warn("La texture $texture est trop volumineuse ({$size}MB).");
                    $error++;
                    return false;
                }
            }

            return true;
        });
        $this->task("Passage du Mod Validateur d'Urban Game", function () use ($modPath, $error) {
            $validatorPath = getcwd().'/bin/mod_validator/mod_validator.exe';
            $command = "\"$validatorPath\" \"$modPath\" --nopause --fix-mipmaps";
            if(exec($command, $output) === false) {
                $error++;
                return false;
            } else {
                return $output;
            }
        });

        if($error > 1) {
            $this->error("Des erreurs ont été détectées lors de la vérification des prerequisites.");
            $this->call('editmod');
        } else {
            $this->info("Prérequis pour le mod : $modPath sont satisfaits.");
        }
    }

    protected function compileMod($modPath)
    {
        $workshop = confirm(label: "Préparer le mod pour le déploiement sur le Workshop? (Oui/Non)", default: true);
        $modio = confirm(label: "Préparer le mod pour le déploiement sur Mod.io? (Oui/Non)", default: true);
        $tfnet = confirm(label:"Préparer le mod pour le déploiement sur TransportFever.net", default: true);
        $messages = "";

        if ($workshop) {
            $this->task("Compilation du mod pour le Workshop", function () use ($modPath, $messages) {
                //Création de l'image du mod pour le Workshop
                $inputPath = $modPath.'/image_00.tga';
                $outputPath = $modPath.'/workshop_image.jpg';

                $command = "\"{$this->magicCmd}\" convert \"$inputPath\" \"$outputPath\"";
                if(exec($command, $output) === false) {
                    return false;
                }

                $messages.= "Image du mod pour le Workshop créée.\n";
                return true;

            });
        }

        if ($modio) {
            $this->task("Compilation du mod pour Mod.io", function () use ($modPath, $messages) {
                $inputPath = $modPath.'/image_00.tga';
                $outputPath = $modPath.'/modio_image.jpg';

                $command = "\"{$this->magicCmd}\" convert \"$inputPath\" \"$outputPath\"";
                if(exec($command, $output) === false) {
                    return false;
                }

                $messages.= "Image du mod pour mod.io créée.\n";
                return true;
            });
        }

        if ($tfnet) {
            $this->task("Compilation du mod pour TransportFever.net", function () use ($modPath, $messages) {
                //Compression du mod pour TransportFever.net
                $zipPath = $modPath."/mod.zip";
                $command = "zip -r $zipPath $modPath";
                if(exec($command, $output) === false) {
                    return false;
                }
                $messages.= "Archive prete pour le déploiement sur transportfever.net\n";
                return true;
            });
            pclose(popen("start https://www.transportfever.net/filebase/entry-add/", "r"));
        }
    }

    private function checkLuaSyntax($filePath)
    {
        // Commande `luac -p` pour vérifier la syntaxe sans exécuter le fichier
        $command = "$this->luaCmd -p " . escapeshellarg($filePath);
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return implode("\n", $output); // Retourne l'erreur si la syntaxe est invalide
        }

        return true; // Retourne true si le fichier est valide
    }

    protected function hasMipmaps($file)
    {
        // Une implémentation simple vérifiant les mipmaps d'une texture .dds
        // Requiert l'installation d'une bibliothèque d'image comme Intervention Image
        // Ici, nous simulons une vérification de mipmap.

        // TODO: Implémenter la vérification réelle des mipmaps avec une bibliothèque adaptée
        if (!file_exists($file)) {
            $this->warn("Le fichier spécifié n'existe pas : $file");
            return false;
        }

        $command = "$this->magicCmd identify -format '%n'".escapeshellarg($file);
        if(exec($command, $output, $returnVar) === false) {
            return false;
        } else {
            $numFrames = isset($output[0]) ? (int)$output[0] : 0;
            if ($numFrames < 2) { // Généralement, 2 ou plus indique la présence de mipmaps
                $this->warn("Le fichier $file ne contient pas assez de mipmaps.");
                return false;
            }
        }

        return true;
    }
}
