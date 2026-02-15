<?php

namespace Rahpt\Ci4ModuleTools\Support;

use CodeIgniter\CLI\CLI;

class ModuleHelper
{
    public static function CreateRoute(string $module) {
        return ModuleRoutes::CreateRoute($module);
    }

    public static function CreateMigration(string $module, string $tableName) {
        return ModuleMigrationHelper::CreateMigration($module, $tableName);
    }

    public static function CreateSeeder(string $module, string $tableName, bool $tableExists) {
        return ModuleSeederHelper::CreateSeeder($module, $tableName, $tableExists);
    }

    public static function CreateModel(string $module, string $tableName, bool $tableExists) {
        return ModuleModelHelper::CreateModel($module, $tableName, $tableExists);
    }

    public static function CreateController(string $module, string $filePath): void {
        $Module = ucfirst($module);
        $className = $Module . 'Controller';

        $content = TemplateHelper::generateContentFromTemplate(
            TemplateHelper::getTemplatePath('Controllers/ControllerModule.tpl'),
            ['Module' => $Module]
        );

        file_put_contents($filePath . "{$className}.php", $content);
        CLI::write("✔ Controller criado: Modules/{$Module}/Controllers/{$className}.php", 'green');
    }

    public static function CreateViewDashboard(string $module, string $viewsPath, string $layout = 'Theme\layouts\adminlte') {
        $Module = ucfirst($module);
        $fileName = 'dashboard.php';

        $content = TemplateHelper::generateContentFromTemplate(
            TemplateHelper::getTemplatePath('Views/dashboard.tpl'),
            ['Module' => $Module, 'Layout' => $layout]
        );

        file_put_contents($viewsPath . $fileName, $content);
        CLI::write("✔ Dashboard View created: Modules/{$Module}/Views/{$fileName}", 'green');
    }

    public static function CreateModuleConfig(string $module, string $label, string $theme = 'adminlte') {
        $Module = ucfirst($module);
        $config = config(\Rahpt\Ci4Module\Config\Modules::class);
        $namespace = $config->baseNamespace;

        $content = TemplateHelper::generateContentFromTemplate(
            TemplateHelper::getTemplatePath('Module.tpl'),
            [
                'Namespace' => $namespace,
                'Module' => $Module,
                'Label' => $label,
                'ModuleSlug' => strtolower($module),
                'RoutePrefix' => strtolower($module),
                'Theme' => str_replace('layouts/', '', $theme)
            ]
        );

        $dir = TemplateHelper::ModuleCreateFolder($Module, 'Config');
        file_put_contents($dir . 'Module.php', $content);
        CLI::write("✔ Module Config created: Modules/{$Module}/Config/Module.php", 'green');
    }
}
