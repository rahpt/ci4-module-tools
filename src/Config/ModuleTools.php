<?php

namespace Rahpt\Ci4ModuleTools\Config;

use CodeIgniter\Config\BaseConfig;

class ModuleTools extends BaseConfig
{
    /**
     * Local repository path for development modules
     * Can be absolute or relative to ROOTPATH
     */
    public string $localRepository = 'Modules';

    /**
     * Maximum ZIP file size in bytes (50MB default)
     */
    public int $maxZipSize = 52428800;

    /**
     * Download timeout in seconds
     */
    public int $downloadTimeout = 30;

    /**
     * Maximum number of HTTP redirects to follow
     */
    public int $maxRedirects = 3;

    /**
     * Enable debug information in marketplace
     */
    public bool $debugMode = false;

    /**
     * Allow installation from URLs
     */
    public bool $allowRemoteInstall = true;

    /**
     * Allowed URL schemes for remote installation
     */
    public array $allowedSchemes = ['https'];

    /**
     * Blacklisted IP ranges (prevent SSRF)
     * Private network ranges that should not be accessible
     */
    public array $blacklistedIpRanges = [
        '10.0.0.0/8',       // Private network
        '172.16.0.0/12',    // Private network
        '192.168.0.0/16',   // Private network
        '127.0.0.0/8',      // Loopback
        '169.254.0.0/16',   // Link-local
        '0.0.0.0/8',        // Current network
    ];

    /**
     * Required module structure validation
     * These paths must exist in the module ZIP
     */
    public array $requiredStructure = [
        'Config/Module.php',
    ];

    /**
     * Get the absolute path to local repository
     */
    public function getLocalRepositoryPath(): string
    {
        $path = $this->localRepository;
        
        // If not absolute, make it relative to ROOTPATH
        if (!$this->isAbsolutePath($path)) {
            $path = ROOTPATH . $path;
        }
        
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Check if a path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        // Windows absolute path (C:\, D:\, etc)
        if (preg_match('/^[A-Z]:\\\\/i', $path)) {
            return true;
        }
        
        // Unix absolute path (starts with /)
        if (strpos($path, '/') === 0) {
            return true;
        }
        
        return false;
    }
}
