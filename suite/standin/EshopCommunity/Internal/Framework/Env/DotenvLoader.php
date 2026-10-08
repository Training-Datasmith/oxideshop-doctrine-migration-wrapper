<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Env;

final class DotenvLoader
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    public function loadEnvironmentVariables(): void
    {
        $envFile = $this->projectRoot . '/.env';
        if (!is_file($envFile)) {
            return;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\"'");
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}
