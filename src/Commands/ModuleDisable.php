<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleDisable extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:disable';
    protected $description = 'Disables a module.';
    protected $usage = 'module:disable <ModuleName>';

    public function run(array $params)
    {
        if (empty($params)) {
            CLI::error("Module name is required.");
            return;
        }

        $name = $params[0];
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

        $registry->deactivate($moduleKey);
        CLI::write("✔ Module {$moduleKey} disabled.", 'green');
    }
}
