<?php

namespace __Namespace__\__Module__\Config;

<?php

namespace __Namespace__\__Module__\Config;

use Rahpt\Ci4Module\BaseModule;

class Module extends BaseModule
{
    public string $name = '__Module__';
    public string $label = '__Label__';
    public string $slug = '__ModuleSlug__';
    public string $version = '1.0.0';
    public string $theme = '__Theme__';
    public string $routePrefix = '__RoutePrefix__';
    public array $require = [];

    /**
     * Returns the module menu items.
     */
    public function menu(): array
    {
        return [
            [
                'title' => '__Label__',
                'icon'  => 'fas fa-cube',
                'route' => '__RoutePrefix__',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'route' => '__RoutePrefix__',
                        'icon'  => 'fas fa-tachometer-alt'
                    ],
                ]
            ],
        ];
    }

    /**
     * Method executed during module installation.
     */
    public function install(): void
    {
        // Add your installation logic here
    }
}
