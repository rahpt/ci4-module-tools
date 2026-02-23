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

        $module = $params[0];
        CLI::write("🚀 Attempting to install module: $module", 'blue');

        // Use the centralized PackageInstaller which handles dependencies automatically
        if (\Rahpt\Ci4ModuleTools\Support\PackageInstaller::install($module)) {
            CLI::write("✔ Module $module and its dependencies installed successfully.", 'green');
        } else {
            CLI::error("❌ Failed to install module $module.");
        }
    }
}
