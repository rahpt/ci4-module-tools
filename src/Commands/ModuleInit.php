<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Rahpt\Ci4ModuleTools\Support\TemplateHelper;
use Rahpt\Ci4Module\ModuleRegistry;
use Rahpt\Ci4ModuleTools\Support\ModuleHelper;
use Rahpt\Ci4Module\Support\ModuleSetupHelper;

class ModuleInit extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:init';
    protected $description = 'Creates a complete module with CRUD structure, migration, seeder, and routes.';
    protected $usage = 'module:init <ModuleName> [Label] [Layout]';

    public function run(array $params)
    {
        if (count($params) === 0) {
            CLI::write("🔧 Starting module environment configuration...", 'blue');
            if (!ModuleSetupHelper::isPatched()) {
                ModuleSetupHelper::setup();
            } else {
                CLI::write("✔ Environment already configured.", 'green');
            }
            return;
        }
        
        $module = $params[0];
        $label  = $params[1] ?? $module;
        $layout = $params[2] ?? 'Theme\layouts\adminlte';

        if (count($params) >= 1 && count($params) <= 3) {
            $this->createModule($module, $label, $layout);
        } else {
            CLI::error("Invalid number of parameters.");
            CLI::write($this->usage, 'yellow');
        }
    }

    public function createModule($module, $label, $layout = 'Theme\layouts\adminlte'): void
    {
        if (!ModuleSetupHelper::isPatched()) {
            CLI::write("📦 Applying CI4 Modules Patch...", 'blue');
            ModuleSetupHelper::setup();
        }

        CLI::write("📦 Creating module '{$label} ({$module})'...", 'blue');
        $modulePath = ucfirst($module);
        $tableName = strtolower($module);
        $controllersPath = TemplateHelper::ModuleCreateFolder($modulePath, 'Controllers');

        TemplateHelper::ModuleCreateFolder($module, 'Models');
        TemplateHelper::ModuleCreateFolder($module, 'Database/Migrations');
        TemplateHelper::ModuleCreateFolder($module, 'Database/Seeds');
        $viewsPath = TemplateHelper::ModuleCreateFolder($modulePath, 'Views');

        // 1. Create Module Config
        ModuleHelper::CreateModuleConfig($module, $label, $layout);

        // 2. Generate files using other commands
        ModuleHelper::CreateRoute($module);
        ModuleHelper::CreateController($module, $controllersPath);
        ModuleHelper::CreateViewDashboard($module, $viewsPath, $layout);
        $tableExists = ModuleHelper::CreateMigration($module, $tableName);
        ModuleHelper::CreateSeeder($module, $tableName, $tableExists);
        
        ModuleHelper::CreateModel($module, $tableName, $tableExists);

        // 3. Update central modules.json Status
        $data = [
            'active' => true,
            'createdAt' => date(DATE_ATOM),
        ];

        service('modules')->put(strtolower($module), $data);

        CLI::write("✔ Module {$module} created successfully!", 'green');
        CLI::write("👉 Run 'php spark module:install {$module}' to complete installation if needed.", 'yellow');
    }

    protected function createSubModule($module, $subModule, $label): void
    {
        CLI::write("📦 Creating submodule '{$label}' in module '{$module}'...", 'blue');
        
        $modulePath = ucfirst($module);
        $subModulePath = ucfirst($subModule);
        
        TemplateHelper::ModuleCreateFolder($modulePath, "Controllers/{$subModulePath}");
        TemplateHelper::ModuleCreateFolder($modulePath, "Views/{$subModulePath}");
        
        CLI::write("✔ Submodule {$subModule} created successfully!", 'green');
    }
}
