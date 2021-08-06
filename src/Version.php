<?php

declare(strict_types=1);

namespace Studio24\DesignSystem;

use Composer\InstalledVersions;

final class Version
{
    const PACKAGE = 'studio24/design-system';
    const NAME = 'Design System';

    /**
     * Return current version of Strata Data
     *
     * Requires Composer 2, or returns null if not found
     *
     * @return string|null
     */
    public static function getVersion(): ?string
    {
        if (class_exists('\Composer\InstalledVersions')) {
            if (InstalledVersions::isInstalled(self::PACKAGE)) {
                return InstalledVersions::getPrettyVersion(self::PACKAGE);
            }
        }
        return null;
    }

}
