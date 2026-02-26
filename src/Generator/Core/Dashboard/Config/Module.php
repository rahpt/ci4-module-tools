<?php

namespace App\Modules\Dashboard\Config;

use Rahpt\Ci4Module\BaseModule;

class Module extends BaseModule
{
    public string $name = 'Dashboard';
    public string $label = 'Painel Principal';
    public string $slug = 'dashboard';
    public string $version = '1.0.0';
    public string $theme = 'adminlte';
    public string $routePrefix = 'dashboard';

    public function menu(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'route' => 'dashboard',
                'permission' => 'admin.access'
            ]
        ];
    }
}
