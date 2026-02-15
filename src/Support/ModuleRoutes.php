<?php

namespace Rahpt\Ci4ModuleTools\Support;

use CodeIgniter\CLI\CLI;

class ModuleRoutes
{
    public static function CreateRoute(string $module)
    {
        $templateFile = TemplateHelper::getTemplatePath('Routes/Routes.tpl');

        $content = TemplateHelper::generateContentFromTemplate($templateFile, [
            'Module' => ucfirst($module),
            'module' => strtolower($module),
        ]);

        $filePath = TemplateHelper::ModuleCreateFolder($module, 'Config');
        file_put_contents($filePath . 'Routes.php', $content);
        
        CLI::write("✔ Rota principal criada em Modules/{$module}/Config/Routes.php", 'green');
    }
}
