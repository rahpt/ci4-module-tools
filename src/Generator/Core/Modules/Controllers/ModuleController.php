<?php

namespace App\Modules\Modules\Controllers;

use App\Controllers\BaseController;
use Rahpt\Ci4Module\ModuleRegistry;
use Rahpt\Ci4ModuleTools\Support\PackageInstaller;
use Rahpt\Ci4ModuleTheme\ThemeManager;

class ModuleController extends BaseController
{
    /**
     * Lists all modules and their status.
     */
    public function index()
    {
        $registry = service('modules');
        $marketplaceModules = [];

        // 1. Check for dedicated Marketplace Module (Database driven)
        $marketplaceModelClass = 'App\Modules\Marketplace\Models\MarketplaceModuleModel';
        if (class_exists($marketplaceModelClass)) {
            $marketplaceModel = new $marketplaceModelClass();
            $marketplaceModules = $marketplaceModel->getActiveModules();
        }

        // 2. If empty, fallback to Local Repository Scanner (File system driven)
        if (empty($marketplaceModules)) {
            $marketplaceModules = \Rahpt\Ci4ModuleTools\Support\LocalMarketplaceScanner::scan();
        }

        return view('App\Modules\Modules\Views\index', [
            'modules' => $registry->getAvailableModules(),
            'marketplaceModules' => $marketplaceModules,
            'layout' => ThemeManager::getModuleLayout('modules')
        ]);
    }

    /**
     * Activates a module.
     */
    public function activate(string $slug)
    {
        service('modules')->activate($slug);
        return redirect()->back()->with('message', "Module {$slug} activated.");
    }

    /**
     * Deactivates a module.
     */
    public function deactivate(string $slug)
    {
        service('modules')->deactivate($slug);
        return redirect()->back()->with('message', "Module {$slug} deactivated.");
    }

    /**
     * Install view.
     */
    public function install()
    {
        return view('App\Modules\Modules\Views\install', [
            'layout' => ThemeManager::getModuleLayout('Modules')
        ]);
    }

    /**
     * Handles remote installation.
     */
    public function processInstall()
    {
        $url = $this->request->getPost('url');

        if (PackageInstaller::install($url)) {
            return redirect()->to(base_url('system/modules'))->with('message', 'Module installed successfully.');
        }

        return redirect()->back()->with('error', 'Failed to install module.');
    }

    /**
     * Uninstalls/Deletes a module.
     */
    public function delete(string $slug)
    {
        $registry = service('modules');
        $modules = $registry->getAvailableModules();

        if (!isset($modules[$slug])) {
            return redirect()->back()->with('error', "Module {$slug} not found.");
        }

        if ($modules[$slug]['active']) {
            return redirect()->back()->with('error', "Cannot uninstall an active module. Deactivate it first.");
        }

        $modulePath = FCPATH . '../app/Modules/' . ucfirst($slug);
        $moduleClass = "App\\Modules\\" . ucfirst($slug) . "\\Config\\Module";

        if (is_dir($modulePath)) {
            // Run uninstall hook if class exists
            if (class_exists($moduleClass)) {
                $instance = new $moduleClass();
                if (method_exists($instance, 'uninstall')) {
                    try {
                        $instance->uninstall();
                    } catch (\Exception $e) {
                        log_message('error', "Failed to run uninstall() for {$slug}: " . $e->getMessage());
                    }
                }
            }

            $this->deleteDirectory($modulePath);
            return redirect()->to(base_url('system/modules'))->with('message', "Module {$slug} uninstalled successfully (including database cleanup).");
        }

        return redirect()->back()->with('error', "Failed to find module directory for {$slug}.");
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir))
            return true;
        if (!is_dir($dir))
            return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..')
                continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item))
                return false;
        }
        return rmdir($dir);
    }
}
