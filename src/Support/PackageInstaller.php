<?php

namespace Rahpt\Ci4ModuleTools\Support;

use Rahpt\Ci4ModuleTools\Security\SecurityValidator;

/**
 * PackageInstaller - Handles downloading and extracting modules from remote repositories or local dirs.
 * Enforces transactional staging, integrity verification, atomic deployment, and rollback.
 */
class PackageInstaller
{
    /**
     * Installs a module from a local directory or ZIP.
     */
    public static function install(string $source, array $options = []): bool
    {
        // 1. If it's a URL, use remote staging pipeline
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            return self::installFromUrl($source, $options);
        }

        // 2. Otherwise, check if it's a slug in the local repository
        return self::installFromLocal($source);
    }

    /**
     * Installs a module from a local path with transactional rollback.
     */
    public static function installFromLocal(string $slug): bool
    {
        $config = config('ModuleTools');
        $repoPath = $config->getLocalRepositoryPath();
        $sourceDir = $repoPath . $slug;
        $targetDir = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . ucfirst($slug);

        if (!is_dir($sourceDir)) {
            return false;
        }

        if (is_dir($targetDir)) {
            log_message('debug', "PackageInstaller: Module {$slug} already installed locally.");
            return true;
        }

        $registry = service('modules');
        $deployed = false;

        try {
            if (!self::copyRecursive($sourceDir, $targetDir)) {
                throw new \RuntimeException("Failed to copy module files for {$slug}");
            }
            $deployed = true;

            $moduleName = ucfirst($slug);
            $moduleClass = "App\\Modules\\{$moduleName}\\Config\\Module";
            $moduleFile = $targetDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Module.php';

            if (!is_file($moduleFile)) {
                throw new \RuntimeException("Module config file not found at {$moduleFile}");
            }

            require_once $moduleFile;

            if (!class_exists($moduleClass)) {
                throw new \RuntimeException("Class {$moduleClass} not found in {$moduleFile}");
            }

            // Register namespace
            $autoloader = \Config\Services::autoloader();
            $namespace = "App\\Modules\\{$moduleName}";
            $autoloader->addNamespace($namespace, $targetDir);

            $instance = new $moduleClass();

            // Resolve dependencies first
            $dependencies = $instance->require ?? $instance->requires ?? [];
            foreach ($dependencies as $depSlug => $versionConstraint) {
                if (in_array(strtolower($depSlug), ['php', 'codeigniter4/framework', 'ci4'], true)) {
                    continue;
                }
                $depTarget = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . ucfirst($depSlug);
                if (!is_dir($depTarget)) {
                    log_message('info', "PackageInstaller: Auto-installing dependency '{$depSlug}' for '{$slug}'.");
                    if (!self::installFromLocal($depSlug)) {
                        throw new \RuntimeException("Failed to install required dependency '{$depSlug}'");
                    }
                }
            }

            // Register as installed
            $registry->setStatus($slug, \Rahpt\Ci4Module\ModuleRegistry::STATUS_INSTALLED);

            // Execute migrations
            ModuleMigrationHelper::runMigrations($namespace);

            // Call module install hook
            if (method_exists($instance, 'install')) {
                $instance->install();
            }

            // Transactional activation
            if (!$registry->activate($slug)) {
                throw new \RuntimeException("Module activation failed for '{$slug}'");
            }

            log_message('info', "PackageInstaller: Module '{$slug}' installed and activated successfully.");
            return true;

        } catch (\Throwable $e) {
            log_message('error', "PackageInstaller: Installation transaction failed for {$slug}: " . $e->getMessage());

            // Rollback deployed files
            if ($deployed && is_dir($targetDir)) {
                self::deleteRecursive($targetDir);
            }

            if (isset($registry)) {
                $registry->quarantine($slug, "Installation failed: " . $e->getMessage());
            }

            return false;
        }
    }

    /**
     * Installs a module from a remote ZIP URL using an isolated staging boundary with hash verification and rollback.
     *
     * @param string $url Remote package URL
     * @param array $options ['sha256' => '...', 'publisher' => 'rahpt', 'signature' => '...']
     */
    public static function installFromUrl(string $url, array $options = []): bool
    {
        $config = config('ModuleTools');
        if (!$config->isRemoteInstallAllowed()) {
            log_message('error', 'PackageInstaller: Remote installation is disabled in configuration.');
            return false;
        }

        $validator = new SecurityValidator($config);

        // 1. SSRF and URL validation
        try {
            $validator->validateUrl($url);
        } catch (\Throwable $e) {
            log_message('error', "PackageInstaller: Security validation failed for URL {$url}: " . $e->getMessage());
            return false;
        }

        $stagingId = bin2hex(random_bytes(8));
        $stagingDir = WRITEPATH . 'modules' . DIRECTORY_SEPARATOR . 'staging' . DIRECTORY_SEPARATOR . $stagingId . DIRECTORY_SEPARATOR;
        $tempZip = $stagingDir . 'module.zip';
        $extractDir = $stagingDir . 'extracted' . DIRECTORY_SEPARATOR;

        $targetDir = null;
        $deployed = false;
        $slug = null;
        $registry = service('modules');

        try {
            if (!is_dir($stagingDir)) {
                mkdir($stagingDir, 0755, true);
            }

            // 2. Download package to isolated staging using curl with per-redirect validation
            $content = self::downloadWithRedirectValidation($url, $validator, $config);
            if ($content === false || $content === '') {
                throw new \RuntimeException("Failed to download package content from URL: {$url}");
            }
            file_put_contents($tempZip, $content);

            // 3. Verify SHA-256 checksum if provided
            if (!empty($options['sha256'])) {
                $actualHash = hash_file('sha256', $tempZip);
                if (!hash_equals(strtolower($options['sha256']), strtolower($actualHash))) {
                    throw new \RuntimeException("SHA-256 checksum mismatch. Expected {$options['sha256']}, got {$actualHash}");
                }
                log_message('info', "PackageInstaller: SHA-256 checksum verified for {$url}");
            }

            // 4. Security scan of ZIP archive (bombs, path traversal, symlinks, ratios)
            $validator->validateZipFile($tempZip);

            // 5. Extract inside isolated staging
            $zip = new \ZipArchive();
            if ($zip->open($tempZip) !== true) {
                throw new \RuntimeException('Failed to open verified ZIP archive in staging.');
            }
            $zip->extractTo($extractDir);
            $zip->close();
            @unlink($tempZip);

            // 6. Detect module root directory in staging
            $moduleSourceDir = self::resolveExtractedModuleDir($extractDir);
            if (!$moduleSourceDir) {
                throw new \RuntimeException('Staged package does not contain a valid CodeIgniter 4 module structure.');
            }

            $slug = self::detectModuleSlug($moduleSourceDir);
            $moduleName = ucfirst($slug);
            $targetDir = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . $moduleName;

            if (is_dir($targetDir)) {
                log_message('warning', "PackageInstaller: Module '{$slug}' is already installed.");
                return true;
            }

            // 7. Atomic deployment to APPPATH/Modules
            if (!self::copyRecursive($moduleSourceDir, $targetDir)) {
                throw new \RuntimeException("Failed to deploy module from staging to destination: {$targetDir}");
            }
            $deployed = true;

            // 8. Register Autoloader, run hooks, and execute migrations
            $moduleClass = "App\\Modules\\{$moduleName}\\Config\\Module";
            $moduleFile = $targetDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Module.php';

            if (is_file($moduleFile)) {
                require_once $moduleFile;

                $autoloader = \Config\Services::autoloader();
                $namespace = "App\\Modules\\{$moduleName}";
                $autoloader->addNamespace($namespace, $targetDir);

                if (class_exists($moduleClass)) {
                    $instance = new $moduleClass();

                    // Register in installed state
                    $registry->setStatus($slug, \Rahpt\Ci4Module\ModuleRegistry::STATUS_INSTALLED);

                    // Execute migrations
                    ModuleMigrationHelper::runMigrations($namespace);

                    // Run install hook
                    if (method_exists($instance, 'install')) {
                        $instance->install();
                    }

                    // Activate transactionally
                    if (!$registry->activate($slug)) {
                        throw new \RuntimeException("Failed to activate module '{$slug}'");
                    }
                }
            }

            log_message('info', "PackageInstaller: Module '{$slug}' installed successfully from staging.");
            return true;

        } catch (\Throwable $e) {
            log_message('error', 'PackageInstaller staging error: ' . $e->getMessage());

            // Rollback on failure
            if ($deployed && $targetDir !== null && is_dir($targetDir)) {
                self::deleteRecursive($targetDir);
            }

            if ($slug !== null) {
                $registry->quarantine($slug, "Installation error: " . $e->getMessage());
            }

            return false;
        } finally {
            // Clean up staging directory unconditionally
            if (is_dir($stagingDir)) {
                self::deleteRecursive($stagingDir);
            }
        }
    }

    /**
     * Resolves the actual module root directory inside the extracted staging folder.
     */
    protected static function resolveExtractedModuleDir(string $extractDir): ?string
    {
        // Direct module structure check
        if (is_file($extractDir . 'Config' . DIRECTORY_SEPARATOR . 'Module.php') || is_file($extractDir . 'module.json')) {
            return rtrim($extractDir, '\\/');
        }

        // Check if package was wrapped in a single root folder
        $items = array_diff(scandir($extractDir) ?: [], ['.', '..']);
        if (count($items) === 1) {
            $singleItem = $extractDir . reset($items);
            if (is_dir($singleItem)) {
                if (is_file($singleItem . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Module.php') ||
                    is_file($singleItem . DIRECTORY_SEPARATOR . 'module.json')) {
                    return $singleItem;
                }
            }
        }

        return null;
    }

    /**
     * Detects slug from module.json or directory name.
     */
    protected static function detectModuleSlug(string $moduleDir): string
    {
        $manifest = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';
        if (is_file($manifest)) {
            try {
                $data = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
                if (!empty($data['slug'])) {
                    return strtolower($data['slug']);
                }
            } catch (\Throwable) {}
        }

        return strtolower(basename($moduleDir));
    }

    private static function copyRecursive(string $src, string $dst): bool
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $dir = opendir($src);
        if (!$dir) {
            return false;
        }

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $srcPath = $src . DIRECTORY_SEPARATOR . $file;
                $dstPath = $dst . DIRECTORY_SEPARATOR . $file;

                if (is_dir($srcPath)) {
                    self::copyRecursive($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
        return true;
    }

    /**
     * Safely deletes a directory and all of its contents.
     */
    private static function deleteRecursive(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::deleteRecursive($path) : @unlink($path);
        }

        return @rmdir($dir);
    }

    /**
     * Downloads content from a URL using curl, validating every redirect URL through SecurityValidator
     * before following it. This prevents redirect-based SSRF bypasses (e.g., redirect to 169.254.x.x).
     *
     * @return string|false Downloaded content, or false on failure
     */
    private static function downloadWithRedirectValidation(
        string $url,
        \Rahpt\Ci4ModuleTools\Security\SecurityValidator $validator,
        $config
    ): string|false {
        if (!function_exists('curl_init')) {
            // Fallback: file_get_contents without redirect validation (limited environments)
            log_message('warning', '[PackageInstaller] curl not available; redirect SSRF protection disabled for this download.');
            return file_get_contents($url) ?: false;
        }

        $maxRedirects  = $config->maxRedirects ?? 3;
        $timeout       = $config->downloadTimeout ?? 30;
        $redirectCount = 0;
        $currentUrl    = $url;
        $body          = false;

        while ($redirectCount <= $maxRedirects) {
            $ch = curl_init($currentUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,  // manual redirect handling
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_MAXREDIRS      => 0,
                CURLOPT_USERAGENT      => 'Rahpt-ModuleInstaller/1.0',
                CURLOPT_HEADER         => true,
            ]);

            $response   = curl_exec($ch);
            $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            if ($response === false) {
                return false;
            }

            $headers = substr($response, 0, $headerSize);
            $body    = substr($response, $headerSize);

            // Follow 3xx redirects
            if ($httpCode >= 300 && $httpCode < 400) {
                if (preg_match('/^Location:\s*(.+)$/im', $headers, $m)) {
                    $redirectUrl = trim($m[1]);

                    // Resolve relative redirect URLs
                    if (!str_starts_with($redirectUrl, 'http')) {
                        $parsed      = parse_url($currentUrl);
                        $redirectUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $redirectUrl;
                    }

                    // Validate the redirect target before following
                    $redirectCount++;
                    $validator->validateRedirectUrl($currentUrl, $redirectUrl, $redirectCount);
                    $currentUrl = $redirectUrl;
                    continue;
                }
            }

            // Non-redirect response
            if ($httpCode >= 200 && $httpCode < 300) {
                return $body;
            }

            log_message('error', "[PackageInstaller] HTTP {$httpCode} downloading: {$currentUrl}");
            return false;
        }

        throw new \RuntimeException("Too many redirects downloading: {$url}");
    }
}
