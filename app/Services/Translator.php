<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class Translator
{
    protected $apiUrl = 'https://lingva.ml/api/v1';

    /**
     * Traduire le texte depuis une langue source vers une langue cible.
     *
     * @param string $text Texte à traduire.
     * @param string $sourceLang Langue source (par exemple, 'fr').
     * @param string $targetLang Langue cible (par exemple, 'en', 'de').
     * @return string Texte traduit.
     * @throws Exception
     */
    public function translate($text, $sourceLang = 'fr', $targetLang = 'en')
    {
        $response = Http::withoutVerifying()->get("{$this->apiUrl}/{$sourceLang}/{$targetLang}/".urlencode($text));

        if ($response->successful()) {
             // Récupération et formatage du texte traduit
            $translatedText = $response->json()['translation'];
            // Remplace les retours à la ligne par \\n pour un format Lua compatible
            $translatedText = str_replace("\n", "\\n", urldecode($translatedText));
            return $translatedText;
        }

        throw new Exception("Erreur lors de la traduction : ".$response->body());
    }
}
