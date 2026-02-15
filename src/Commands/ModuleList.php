<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleList extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:list';
    protected $description = 'Lists all registered modules and their status.';

    public function run(array $params)
    {
        $registry = service('modules');
        $modules = $registry->getAvailableModules();

        if (empty($modules)) {
            CLI::write("No modules found.", 'yellow');
            return;
        }

        $thead = ['Name', 'Label', 'Version', 'Status', 'Route Prefix'];
        $tbody = [];

        foreach ($modules as $name => $data) {
            $tbody[] = [
                $name,
                $data['label'] ?? '-',
                $data['version'] ?? '-',
                ($data['active'] ?? false) ? CLI::color('Active', 'green') : CLI::color('Inactive', 'red'),
                $data['routePrefix'] ?? '-'
            ];
        }

        CLI::table($tbody, $thead);
    }
}
