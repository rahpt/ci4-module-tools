<?php

namespace Rahpt\Ci4ModuleTools\Config;

use CodeIgniter\Config\BaseConfig;

class ModuleTools extends BaseConfig
{
    /**
     * Local repository path for development modules
     * Can be absolute or relative to ROOTPATH
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
        '10.0.0.0/8',       // Private network IPv4
        '172.16.0.0/12',    // Private network IPv4
        '192.168.0.0/16',   // Private network IPv4
        '127.0.0.0/8',      // Loopback IPv4
        '169.254.0.0/16',   // Link-local IPv4
        '0.0.0.0/8',        // Current network
        '::1/128',          // Loopback IPv6
        'fc00::/7',         // Unique Local Address IPv6
        'fe80::/10',        // Link-local IPv6
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

        if (function_exists('setting')) {
            $setPath = setting('ModuleTools.localRepository');
            if ($setPath !== null) {
                $path = $setPath;
            }
        }

        // If not absolute, make it relative to ROOTPATH
        if (!$this->isAbsolutePath($path)) {
            $path = ROOTPATH . $path;
        }

        return rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
    }

    /**
     * Checks if debug mode is enabled (supports settings override)
     */
    public function isDebugEnabled(): bool
    {
        if (function_exists('setting')) {
            return (bool) (setting('ModuleTools.debugMode') ?? $this->debugMode);
        }
        return $this->debugMode;
    }

    /**
     * Checks if remote install is allowed (supports settings override)
     */
    public function isRemoteInstallAllowed(): bool
    {
        if (function_exists('setting')) {
            return (bool) (setting('ModuleTools.allowRemoteInstall') ?? $this->allowRemoteInstall);
        }
        return $this->allowRemoteInstall;
    }

    /**
     * Check if a path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        // Windows absolute path (C:\, D:\, or C:/, D:/)
        if (preg_match('/^[A-Z]:[\\\\\/]/i', $path)) {
            return true;
        }

        // Unix absolute path (starts with /)
        if (strpos($path, '/') === 0) {
            return true;
        }

        return false;
    }
}
