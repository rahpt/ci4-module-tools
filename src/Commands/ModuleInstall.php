<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleInstall extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:install';
    protected $description = 'Runs the installation logic for a specific module.';
    protected $usage = 'module:install <ModuleName>';

    public function run(array $params)
    {
        if (empty($params)) {
            CLI::error("Module name is required.");
            return;
        }

        $module = ucfirst($params[0]);
        $registry = service('modules');
        $availableModules = $registry->getAvailableModules();
        
        if (!isset($availableModules[$module])) {
            CLI::error("Module {$module} not found.");
            return;
        }

        $moduleInfo = $availableModules[$module];

        // Validate dependencies
        if (isset($moduleInfo['require']) && is_array($moduleInfo['require'])) {
            foreach ($moduleInfo['require'] as $requiredModule) {
                if (!isset($availableModules[$requiredModule]) || !($availableModules[$requiredModule]['active'] ?? false)) {
                    CLI::error("Dependency error: Module '{$requiredModule}' is required by '{$module}' and must be active.");
                    return;
                }
            }
        }

        $config = config(\Rahpt\Ci4Module\Config\Modules::class);
        $class = $config->baseNamespace . "\\{$module}\\Config\\Module";

        if (!class_exists($class)) {
            CLI::error("Module Config class not found: {$class}");
            return;
        }

        CLI::write("🚀 Installing module {$module}...", 'blue');

        try {
            $instance = new $class();
            if (method_exists($instance, 'install')) {
                $instance->install();
                CLI::write("✔ Module {$module} installed successfully.", 'green');
            } else {
                CLI::write("ℹ No install method found for module {$module}.", 'yellow');
            }
        } catch (\Exception $e) {
            CLI::error("Error installing module {$module}: " . $e->getMessage());
        }
    }
}
