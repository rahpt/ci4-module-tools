<?php

namespace App\Modules\Modules\Config;

use Rahpt\Ci4Module\BaseModule;

class Module extends BaseModule
{
    public string $name = 'Modules';
    public string $label = 'Gerenciador de Módulos';
    public string $slug = 'modules';
    public string $version = '1.0.0';
    public string $theme = 'adminlte';
    public string $routePrefix = 'system/modules';

    public function menu(): array
    {
        return [
            [
                'label' => 'Sistema',
                'icon' => 'fas fa-cogs',
                'route' => '#',
                'items' => [
                    [
                        'label' => 'Gerenciar Módulos',
                        'icon' => 'far fa-circle',
                        'route' => 'system/modules'
                    ],
                    [
                        'label' => 'Instalar Novo',
                        'icon' => 'far fa-circle',
                        'route' => 'system/modules/install'
                    ],
                    [
                        'label' => 'Configurações',
                        'icon' => 'fas fa-cog',
                        'route' => 'system/settings'
                    ],
                ]
            ]
        ];
    }
}
