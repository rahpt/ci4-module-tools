<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Rahpt\Ci4ModuleTools\Support\TemplateHelper;

class ModuleAssets extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:assets';
    protected $description = 'Links or copies module assets to the public folder.';
    protected $usage = 'module:assets [ModuleName]';

    public function run(array $params)
    {
        $registry = service('modules');
        $modules = empty($params) ? $registry->getAvailableModules() : [$params[0] => $registry->all($params[0])];

        if (empty($modules)) {
            CLI::write("No modules found.", 'yellow');
            return;
        }

        $publicPath = FCPATH . 'modules' . DIRECTORY_SEPARATOR;
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        foreach ($modules as $name => $data) {
            $name = ucfirst($name);
            $moduleAssetsPath = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'Assets';
            
            if (!is_dir($moduleAssetsPath)) {
                continue;
            }

            $targetPath = $publicPath . strtolower($name);
            
            if (is_link($targetPath) || is_dir($targetPath)) {
                @unlink($targetPath);
            }

            if (function_exists('symlink')) {
                try {
                    if (symlink($moduleAssetsPath, $targetPath)) {
                        CLI::write("✔ Linked: {$name} assets -> public/modules/" . strtolower($name), 'green');
                    } else {
                        $this->copyDirectory($moduleAssetsPath, $targetPath);
                        CLI::write("✔ Copied: {$name} assets -> public/modules/" . strtolower($name), 'green');
                    }
                } catch (\Exception $e) {
                    $this->copyDirectory($moduleAssetsPath, $targetPath);
                    CLI::write("✔ Copied: {$name} assets -> public/modules/" . strtolower($name), 'green');
                }
            } else {
                $this->copyDirectory($moduleAssetsPath, $targetPath);
                CLI::write("✔ Copied: {$name} assets -> public/modules/" . strtolower($name), 'green');
            }
        }
    }

    protected function copyDirectory($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->copyDirectory($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
