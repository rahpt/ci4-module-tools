<?php

namespace Rahpt\Ci4ModuleTools\Support;

use Rahpt\Ci4ModuleTools\Security\SecurityValidator;

/**
 * PackageInstaller - Handles downloading and extracting modules from remote repositories or local dirs.
 * Enforces secure staging, validation, and atomic deployment.
 */
class PackageInstaller
{
    /**
     * Installs a module from a local directory or ZIP.
     */
    public static function install(string $source): bool
    {
        // 1. If it's a URL, use remote staging pipeline
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            return self::installFromUrl($source);
        }

        // 2. Otherwise, check if it's a slug in the local repository
        return self::installFromLocal($source);
    }

    /**
     * Installs a module from a local path (copying directory).
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
            // Already installed
            log_message('debug', "PackageInstaller: Module {$slug} already installed locally.");
            return true;
        }

        $result = self::copyRecursive($sourceDir, $targetDir);

        if ($result) {
            log_message('debug', "PackageInstaller: Files copied for {$slug}. Triggering setup...");

            $moduleName = ucfirst($slug);
            $moduleClass = "App\\Modules\\{$moduleName}\\Config\\Module";
            $moduleFile = $targetDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Module.php';

            log_message('debug', "PackageInstaller: Looking for file {$moduleFile}");

            if (is_file($moduleFile)) {
                require_once $moduleFile;
                log_message('debug', "PackageInstaller: File {$moduleFile} required.");

                if (class_exists($moduleClass)) {
                    log_message('debug', "PackageInstaller: Class {$moduleClass} found. Instantiating...");

                    // Manually register namespace for Autoloader and Migrations
                    $autoloader = \Config\Services::autoloader();
                    $namespace = "App\\Modules\\{$moduleName}";
                    $autoloader->addNamespace($namespace, $targetDir);
                    log_message('debug', "PackageInstaller: Namespace {$namespace} manually registered for this request.");

                    $instance = new $moduleClass();

                    // --- Automatic Dependency Resolution ---
                    // $require is associative: ['depSlug' => 'versionConstraint']
                    $dependencies = $instance->require ?? [];
                    if (!empty($dependencies)) {
                        $depList = implode(', ', array_keys($dependencies));
                        log_message('info', "PackageInstaller: Module '{$slug}' requires: [{$depList}]. Resolving...");

                        foreach ($dependencies as $depSlug => $versionConstraint) {
                            $depFolder = ucfirst($depSlug);
                            $depTarget = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . $depFolder;

                            if (is_dir($depTarget)) {
                                log_message('debug', "PackageInstaller: Dependency '{$depSlug}' already installed. Skipping.");
                                continue;
                            }

                            log_message('info', "PackageInstaller: Installing dependency '{$depSlug}' (constraint: {$versionConstraint}) for '{$slug}'.");

                            $depInstalled = self::installFromLocal($depSlug);

                            if ($depInstalled) {
                                log_message('info', "PackageInstaller: Dependency '{$depSlug}' installed successfully.");
                                // Activate the dependency in modules.json
                                try {
                                    $registry = service('modules');
                                    $registry->activate($depSlug);
                                    log_message('info', "PackageInstaller: Dependency '{$depSlug}' activated in modules.json.");
                                } catch (\Throwable $e) {
                                    log_message('warning', "PackageInstaller: Could not activate dependency '{$depSlug}': " . $e->getMessage());
                                }
                            } else {
                                log_message('error', "PackageInstaller: FAILED to install dependency '{$depSlug}' for '{$slug}'. Source not found in local repository.");
                            }
                        }
                    }

                    if (method_exists($instance, 'install')) {
                        try {
                            log_message('debug', "PackageInstaller: Calling install() hook for {$slug}");
                            $instance->install();
                            log_message('debug', "PackageInstaller: install() hook completed for {$slug}");
                        } catch (\Exception $e) {
                            log_message('error', "PackageInstaller: Failed to run install() for {$slug}: " . $e->getMessage());
                        }
                    } else {
                        log_message('debug', "PackageInstaller: Method install() not found in {$moduleClass}");
                    }

                    // Automatically run migrations if they exist
                    log_message('debug', "PackageInstaller: Running migrations for {$namespace}");
                    ModuleMigrationHelper::runMigrations($namespace);
                } else {
                    log_message('error', "PackageInstaller: Class {$moduleClass} NOT found even after requiring file.");
                }
            } else {
                log_message('error', "PackageInstaller: Module config file NOT found at {$moduleFile}");
            }
        }

        return $result;
    }

    /**
     * Installs a module from a remote ZIP URL using an isolated staging boundary.
     * Flow: download -> writable/modules/staging/<uuid> -> validate security -> verify structure -> install -> cleanup
     */
    public static function installFromUrl(string $url): bool
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

        try {
            if (!is_dir($stagingDir)) {
                mkdir($stagingDir, 0755, true);
            }

            // 2. Download package to staging
            $content = file_get_contents($url);
            if (!$content) {
                log_message('error', "PackageInstaller: Failed to download content from URL: {$url}");
                return false;
            }
            file_put_contents($tempZip, $content);

            // 3. Security scan of ZIP archive (bombs, path traversal, symlinks, ratios)
            $validator->validateZipFile($tempZip);

            // 4. Extract inside isolated staging
            $zip = new \ZipArchive();
            if ($zip->open($tempZip) !== true) {
                throw new \RuntimeException('Failed to open verified ZIP archive in staging.');
            }
            $zip->extractTo($extractDir);
            $zip->close();
            @unlink($tempZip);

            // 5. Detect module root directory in staging
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

            // 6. Move from staging to production APPPATH/Modules
            if (!self::copyRecursive($moduleSourceDir, $targetDir)) {
                throw new \RuntimeException("Failed to move module from staging to destination: {$targetDir}");
            }

            // 7. Register Autoloader, run hooks, and execute migrations
            $moduleClass = "App\\Modules\\{$moduleName}\\Config\\Module";
            $moduleFile = $targetDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Module.php';

            if (is_file($moduleFile)) {
                require_once $moduleFile;

                $autoloader = \Config\Services::autoloader();
                $namespace = "App\\Modules\\{$moduleName}";
                $autoloader->addNamespace($namespace, $targetDir);

                if (class_exists($moduleClass)) {
                    $instance = new $moduleClass();
                    if (method_exists($instance, 'install')) {
                        try {
                            $instance->install();
                        } catch (\Throwable $e) {
                            log_message('error', "PackageInstaller: Failed to run install() hook for {$slug}: " . $e->getMessage());
                        }
                    }

                    ModuleMigrationHelper::runMigrations($namespace);
                }
            }

            log_message('info', "PackageInstaller: Module '{$slug}' installed successfully from staging.");
            return true;

        } catch (\Throwable $e) {
            log_message('error', 'PackageInstaller staging error: ' . $e->getMessage());
            return false;
        } finally {
            // Clean up staging directory
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
}
