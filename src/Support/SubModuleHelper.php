<?php

namespace Rahpt\Ci4ModuleTools\Support;

/**
 * SubModuleHelper - Utilitários para criação de submódulos
 */
class SubModuleHelper {
    public static function create(string $module, string $subModule, string $label) {
        $modulePath = ucfirst($module);
        $subModulePath = ucfirst($subModule);

        TemplateHelper::ModuleCreateFolder($modulePath, "Controllers/{$subModulePath}");
        TemplateHelper::ModuleCreateFolder($modulePath, "Views/{$subModulePath}");

        // Gera controller de submódulo
        $content = TemplateHelper::generateContentFromTemplate(
            TemplateHelper::getTemplatePath('Controllers/ControllerModule.tpl'),
            ['Module' => $modulePath . '\\' . $subModulePath]
        );
        
        $filePath = TemplateHelper::ModuleCreateFolder($modulePath, "Controllers/{$subModulePath}");
        file_put_contents($filePath . "{$subModulePath}Controller.php", $content);
    }
}
