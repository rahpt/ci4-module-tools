<?php

namespace Config;

use Rahpt\Ci4ModuleTools\Config\ModuleTools as BaseModuleTools;

class ModuleTools extends BaseModuleTools
{
    /**
     * Local repository path for development modules
     * 
     * Windows example: 'c:/www/mods/Modules'
     * Linux example:   '/var/www/modules'
     * Relative:        'Modules' (relative to ROOTPATH)
     */
    public string $localRepository = 'c:/www/mods/Modules';

    /**
     * Maximum ZIP file size in bytes (50MB default)
     */
    public int $maxZipSize = 52428800;

    /**
     * Download timeout in seconds
     */
    public int $downloadTimeout = 30;

    /**
     * Enable debug information in marketplace
     * Set to false in production!
     */
    public bool $debugMode = true; // CHANGE TO FALSE IN PRODUCTION!

    /**
     * Allow installation from URLs
     * Disable in production for security
     */
    public bool $allowRemoteInstall = true;

    /**
     * Allowed URL schemes for remote installation
     * Only HTTPS is recommended for production
     */
    public array $allowedSchemes = ['https'];
}
