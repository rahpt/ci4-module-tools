<?php

namespace Rahpt\Ci4ModuleTools\Config;

/**
 * Registrar for Module Tools
 * 
 * Registers commands and services with CodeIgniter 4
 */
class Registrar
{
    /**
     * Register CLI commands
     *
     * @return array
     */
    public static function Commands(): array
    {
        return [
            'Rahpt\Ci4ModuleTools\Commands\ModuleInit',
            'Rahpt\Ci4ModuleTools\Commands\ModuleInitCore',
            'Rahpt\Ci4ModuleTools\Commands\ModuleList',
            'Rahpt\Ci4ModuleTools\Commands\ModuleEnable',
            'Rahpt\Ci4ModuleTools\Commands\ModuleDisable',
            'Rahpt\Ci4ModuleTools\Commands\ModuleInstall',
            'Rahpt\Ci4ModuleTools\Commands\ModuleAssets',
            'Rahpt\Ci4ModuleTools\Commands\ModulePublish',
        ];
    }
}
