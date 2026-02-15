<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleEnable extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:enable';
    protected $description = 'Enables a module.';
    protected $usage = 'module:enable <ModuleName>';

    public function run(array $params)
    {
        if (empty($params)) {
            CLI::error("Module name is required.");
            return;
        }

        $name = $params[0]; // Keep original case for slug check or use ucfirst if that's the standard
        $registry = service('modules');
        $available = $registry->getAvailableModules();
        
        // Find by slug, name or folder name
        $moduleKey = null;
        foreach($available as $slug => $meta) {
            $folder = basename($meta['path'] ?? '');
            $nameProperty = $meta['name'] ?? '';
            
            if (
                strtolower($slug) === strtolower($name) || 
                strtolower($nameProperty) === strtolower($name) ||
                strtolower($folder) === strtolower($name)
            ) {
                $moduleKey = $slug;
                break;
            }
        }

        if (!$moduleKey) {
            CLI::error("Module '{$name}' not found. Tried matching slug, name, or folder.");
            CLI::write("Available modules: " . implode(', ', array_keys($available)), 'yellow');
            return;
        }

        $registry->activate($moduleKey);
        CLI::write("✔ Module {$moduleKey} enabled.", 'green');
    }
}
