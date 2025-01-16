<?php

namespace App\Services;

class Updater
{
    protected string $repo;
    protected string $owner;
    protected string $currentVersion;
    protected string $currentPharPath;

    public function __construct(string $owner, string $repo, string $currentVersion, string $currentPharPath = 'modmanager')
    {
        $this->repo = $repo;
        $this->owner = $owner;
        $this->currentVersion = $currentVersion;
        $this->currentPharPath = $currentPharPath;
    }

    /**
     * Vérifie s’il existe une mise à jour plus récente sur GitHub.
     */

    public function checkForUpdate(): ?array
    {
        $uri = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases/latest";

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: ModManager\r\n"
            ]
        ]);

        $json = file_get_contents($uri, false, $context);

        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);

        if (!isset($data['tag_name'])) {
            return null;
        }

        $latestVersion = ltrim($data['tag_name'], 'v');

        if (version_compare($latestVersion, $this->currentVersion, '>')) {
            //Chercher l'asset modmanager
            if (isset($data['assets']) && is_array($data['assets'])) {
                foreach ($data['assets'] as $asset) {
                    if ($asset['name'] === $this->currentPharPath) {
                        return [
                            'version' => $latestVersion,
                            'download_url' => $asset['browser_download_url']
                        ];
                    }
                }
            }
        }

        return null;
    }

    public function update(string $downloadUrl)
    {
        $tmpFile = $this->currentPharPath . '.tmp';

        // Télécharger la mise à jour
        $fileData = @file_get_contents($downloadUrl);
        if (!$fileData) {
            throw new \Exception("Erreur lors du téléchargement de la mise à jour.");
        }

        if (@file_put_contents($tmpFile, $fileData) === false) {
            throw new \Exception("Impossible d'écrire le fichier temporaire.");
        }

        // Sauvegarde de l'ancienne version
        if (file_exists($this->currentPharPath)) {
            $backupPath = $this->currentPharPath . '.bak';
            if (!@rename($this->currentPharPath, $backupPath)) {
                @unlink($tmpFile);
                throw new \Exception("Impossible de sauvegarder l'ancienne version.");
            }
        }

        // Remplacement par le fichier téléchargé
        if (!@rename($tmpFile, $this->currentPharPath)) {
            @unlink($tmpFile);
            throw new \Exception("Erreur lors de l'installation de la mise à jour.");
        }

        // Donner les droits d'exécution
        @chmod($this->currentPharPath, 0755);

        return true;
    }
}
