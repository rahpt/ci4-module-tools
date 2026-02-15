<?php

namespace App\Modules\ModuleManager\Config;

use Rahpt\Ci4Module\BaseModule;

class Module extends BaseModule
{
    public string $name         = 'ModuleManager';
    public string $label        = 'System Manager';
    public string $slug         = 'module-manager';
    public string $version      = '1.0.0';
    public string $theme        = 'adminlte';
    public string $routePrefix  = 'system/modules';

    public function menu(): array
    {
        return [
            [
                'label' => 'System',
                'icon'  => 'fas fa-shield-alt',
                'route' => '#',
                'items' => [
                    [
                        'label' => 'Modules Manager',
                        'icon'  => 'fas fa-cubes',
                        'route' => 'system/modules'
                    ],
                    [
                        'label' => 'Cloud Install',
                        'icon'  => 'fas fa-cloud-download-alt',
                        'route' => 'system/modules/marketplace'
                    ],
                ]
            ]
        ];
    }
}
