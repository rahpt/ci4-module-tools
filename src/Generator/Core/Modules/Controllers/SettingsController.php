<?php

namespace App\Modules\Modules\Controllers;

use App\Controllers\BaseController;
use Rahpt\Ci4ModuleTheme\ThemeManager;

class SettingsController extends BaseController
{
    public function index()
    {
        $registry = service('modules');
        $modules = $registry->getAvailableModules();
        $allSettings = [];

        foreach ($modules as $slug => $data) {
            if (!$data['active'])
                continue;

            $class = "App\\Modules\\" . ucfirst($slug) . "\\Config\\Module";
            if (!class_exists($class))
                continue;

            $instance = new $class();
            if (method_exists($instance, 'settings')) {
                $moduleSettings = $instance->settings();
                if (!empty($moduleSettings)) {
                    foreach ($moduleSettings as $group => $config) {
                        $allSettings[$group] = $config;
                        $allSettings[$group]['module_slug'] = $slug;
                    }
                }
            }
        }

        return view('App\Modules\Modules\Views\settings', [
            'settings' => $allSettings,
            'layout' => ThemeManager::getModuleLayout('modules')
        ]);
    }

    public function save()
    {
        $post = $this->request->getPost();

        if (!function_exists('setting')) {
            return redirect()->back()->with('error', 'Package codeigniter4/settings is not installed.');
        }

        foreach ($post as $key => $value) {
            // Keys are expected in format: Group_Field
            if (strpos($key, '_') !== false) {
                list($group, $field) = explode('_', $key, 2);
                setting("{$group}.{$field}", $value);
            }
        }

        return redirect()->back()->with('message', 'Configurações salvas com sucesso.');
    }
}
