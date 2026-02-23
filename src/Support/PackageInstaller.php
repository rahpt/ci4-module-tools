<?php

namespace Rahpt\Ci4ModuleTools\Support;

/**
 * PackageInstaller - Handles downloading and extracting modules from remote repositories or local dirs.
 */
class PackageInstaller
{
    /**
     * Installs a module from a local directory or ZIP.
     */
    public static function install(string $source): bool
    {
        // 1. If it's a URL, use existing logic
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
     * Installs a module from a remote ZIP URL.
     */
    public static function installFromUrl(string $url): bool
    {
        $config = config('ModuleTools');
        if (!$config->isRemoteInstallAllowed()) {
            log_message('error', 'PackageInstaller: Remote installation is disabled in configuration.');
            return false;
        }

        $tempFile = WRITEPATH . 'temp_module_' . time() . '.zip';

        try {
            $content = file_get_contents($url);
            if (!$content) {
                log_message('error', "PackageInstaller: Failed to download content from URL: {$url}");
                return false;
            }
            file_put_contents($tempFile, $content);

            $zip = new \ZipArchive();
            if ($zip->open($tempFile) === TRUE) {
                $extractPath = APPPATH . 'Modules' . DIRECTORY_SEPARATOR;
                $zip->extractTo($extractPath);
                $zip->close();
                @unlink($tempFile);

                // Try to trigger install() for the newly installed module
                // We'll scan for the most recently modified directory in App/Modules
                $dirs = array_filter(glob($extractPath . '*'), 'is_dir');
                if (!empty($dirs)) {
                    array_multisort(array_map('filemtime', $dirs), SORT_DESC, $dirs);
                    $newestDir = $dirs[0];
                    $slug = basename($newestDir);

                    // Check if module is already installed (e.g., if a dependency was already installed)
                    if (is_dir(APPPATH . 'Modules' . DIRECTORY_SEPARATOR . ucfirst($slug))) {
                        log_message('debug', "PackageInstaller: Module {$slug} already installed remotely (likely as a dependency).");
                        return true;
                    }

                    $moduleName = ucfirst($slug);
                    $moduleClass = "App\\Modules\\{$moduleName}\\Config\\Module";
                    $moduleFile = $newestDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Module.php';

                    if (is_file($moduleFile)) {
                        require_once $moduleFile;

                        // Manually register namespace for Autoloader and Migrations
                        $autoloader = \Config\Services::autoloader();
                        $namespace = "App\\Modules\\{$moduleName}";
                        $autoloader->addNamespace($namespace, $newestDir);

                        if (class_exists($moduleClass)) {
                            $instance = new $moduleClass();
                            if (method_exists($instance, 'install')) {
                                try {
                                    $instance->install();
                                } catch (\Exception $e) {
                                    log_message('error', "PackageInstaller: Failed to run install() for {$slug}: " . $e->getMessage());
                                }
                            }

                            // Automatically run migrations if they exist
                            ModuleMigrationHelper::runMigrations($namespace);
                        }
                    }
                }

                return true;
            }
        } catch (\Exception $e) {
            @unlink($tempFile);
            log_message('error', 'PackageInstaller: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    private static function copyRecursive(string $src, string $dst): bool
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    self::copyRecursive($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                } else {
                    copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        closedir($dir);
        return true;
    }
}
