<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Rahpt\Ci4Module\Support\ModuleSetupHelper;

class ModuleInitCore extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:init-core';
    protected $description = 'Initializes core modules (Dashboard and ModuleManager) into the application.';
    protected $usage = 'module:init-core';

    public function run(array $params)
    {
        CLI::write("🚀 Initializing core modular environment...", 'blue');

        if (!ModuleSetupHelper::isPatched()) {
            CLI::write("📦 Applying CI4 Modules Patch...", 'yellow');
            ModuleSetupHelper::setup();
        }

        $corePath = dirname(__DIR__) . '/Generator/Core/';
        $namespace = ModuleSetupHelper::getNamespace();
        $baseFolder = str_replace(['App\\', '\\'], ['', DIRECTORY_SEPARATOR], $namespace);
        $appModulesPath = APPPATH . $baseFolder . DIRECTORY_SEPARATOR;

        if (!is_dir($appModulesPath)) {
            mkdir($appModulesPath, 0755, true);
        }

        $coreModules = ['Dashboard', 'Modules'];

        foreach ($coreModules as $module) {
            $src = $corePath . $module;
            $dst = $appModulesPath . $module;

            if (is_dir($dst)) {
                $overwrite = CLI::prompt("Module '{$module}' already exists in {$dst}. Overwrite?", ['y', 'n'], 'n');
                if ($overwrite !== 'y') {
                    CLI::write("⏭️ Skipping {$module}...", 'yellow');
                    continue;
                }
                $this->deleteDirectory($dst);
            }

            CLI::write("📦 Installing {$module}...", 'blue');
            $this->copyDirectory($src, $dst);
            
            // Activate in modules.json
            $registry = service('modules');
            $registry->activate(strtolower($module));
            
            CLI::write("✔ {$module} installed and activated!", 'green');
        }

        CLI::write("✨ Core modules initialization complete!", 'green');
    }

    private function copyDirectory($src, $dst)
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
}
