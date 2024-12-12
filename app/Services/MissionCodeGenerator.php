<?php

namespace App\Services;

class MissionCodeGenerator
{
    protected array $config;
    protected static int $counter = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generate(string $line, string $branch, string $direction, string $serviceType, string $timeSlot): string
    {
        $firstLetter = $this->determineFirstLetter($line, $branch, $direction);
        $middleLetter = $this->determineMiddleLetter($serviceType, $timeSlot);
        $lastLetter = $this->determineLastLetter();
        return strtoupper($firstLetter.$middleLetter.$lastLetter);
    }

    private function determineFirstLetter(string $line, string $branch, string $direction)
    {
        $lines = $this->config['lines'] ?? [];
        if (!isset($lines[$line]['branches'][$branch][$direction])) {
            return 'Z';
        }

        return $lines[$line]['branches'][$branch][$direction];
    }

    private function determineMiddleLetter(string $serviceType, string $timeSlot)
    {
        $serviceTypes = $this->config['serviceTypes'] ?? [];
        $timeSlots = $this->config['timeSlots'] ?? [];

        $serviceLetter = $serviceTypes[$serviceType] ?? 'O';
        $timeLetter = $timeSlots[$timeSlot] ?? 'F';

        return $serviceLetter . $timeLetter;
    }

    private function determineLastLetter()
    {
        $letters = $this->config['lastLetters'] ?? ['A', 'E', 'I', 'O', 'U'];
        $letter = $letters[self::$counter % count($letters)];
        self::$counter++;
        return $letter;
    }


}
