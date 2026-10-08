<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\FileSystem;

final class ProjectRootLocator
{
    public function getProjectRoot(): string
    {
        $root = getenv('MW_TEST_PROJECT_ROOT');
        if ($root !== false && $root !== '') {
            return $root;
        }

        $cwd = getcwd();
        if ($cwd === false) {
            throw new \RuntimeException('Cannot determine project root');
        }

        return $cwd;
    }
}
