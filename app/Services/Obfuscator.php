<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class Obfuscator
{
    private $url = "https://api.luaobfuscator.com/v1/obfuscator/";
    private $apiKey = 'b8662120-85bc-1eaa-64c4-93bede6e92a83779';

    public function obfuscate($luaCode)
    {
        $luaCode = mb_convert_encoding($luaCode, 'UTF-8', 'auto');
        $session_id = $this->newSession($luaCode);

        $response = Http::withoutVerifying()
            ->withHeaders([
                'content-type' => 'application/json',
                'apikey' => $this->apiKey,
                'sessionId' => $session_id,
            ])
            ->post($this->url.'obfuscate', [
               'MinifyAll' => true,
               'Virtualize' => true,
            ]);

        if ($response->successful()) {
            return $response->json()['code'];
        } else {
            throw new Exception("Erreur lors de l'obfuscation : {$response->body()}");
        }
    }

    private function newSession($luaCode)
    {
        $luaCode = mb_convert_encoding($luaCode, 'UTF-8', 'auto');
        $response = Http::withoutVerifying()
            ->withHeaders([
                'content-type' => 'application/json',
                'apikey' => $this->apiKey,
            ])
            ->post($this->url.'newscript', [$luaCode]);

        if ($response->successful()) {
            return $response->json()['session_id'];
        } else {
            throw new Exception("Erreur lors de la création de la session : {$response->body()}");
        }
    }
}
