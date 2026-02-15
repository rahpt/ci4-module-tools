<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleImportManager extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:import-manager';
    protected $description = 'Imports the ModuleManager module into your application.';

    public function run(array $params)
    {
        $sourceDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Generator' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'ModuleManager';
        $targetDir = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . 'ModuleManager';

        if (is_dir($targetDir)) {
            if (!CLI::prompt("ModuleManager already exists. Overwrite?", ['y', 'n']) === 'y') {
                return;
            }
        }

        $this->copyRecursive($sourceDir, $targetDir);

        CLI::write("✔ ModuleManager imported to app/Modules/ModuleManager", 'green');
        CLI::write("👉 Remember to activate it via CLI if needed: php spark module:enable ModuleManager", 'yellow');
    }

    protected function copyRecursive($src, $dst)
    {
        @mkdir($dst, 0755, true);
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    $this->copyRecursive($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                } else {
                    copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        closedir($dir);
    }
}
