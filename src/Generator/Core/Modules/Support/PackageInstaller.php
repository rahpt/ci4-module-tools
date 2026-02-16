<?php

namespace App\Modules\Modules\Support;

/**
 * PackageInstaller - Handles downloading and extracting modules from remote repositories.
 */
class PackageInstaller
{
    /**
     * Installs a module from a remote ZIP URL.
     */
    public static function installFromUrl(string $url): bool
    {
        $tempFile = WRITEPATH . 'temp_module_' . time() . '.zip';
        
        // 1. Download the package
        $content = file_get_contents($url);
        if (!$content) {
            return false;
        }
        file_put_contents($tempFile, $content);

        // 2. Extract the package
        $zip = new \ZipArchive();
        if ($zip->open($tempFile) === TRUE) {
            $extractPath = APPPATH . 'Modules' . DIRECTORY_SEPARATOR;
            $zip->extractTo($extractPath);
            $zip->close();
            
            @unlink($tempFile);
            
            // 3. Trigger Assets Link & Install (Simulated via command call)
            // in a real app: command('module:assets'); command('module:install ' . $moduleName);
            
            return true;
        }

        @unlink($tempFile);
        return false;
    }
}
