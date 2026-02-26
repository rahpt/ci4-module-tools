<?php

namespace App\Modules\Dashboard\Controllers;

use App\Controllers\BaseController;
use Rahpt\Ci4ModuleTheme\ThemeManager;

class DashboardController extends BaseController
{
    public function index()
    {
        // Disambiguation: Redirect non-admin users to their personal panel
        if (!auth()->user()->can('admin.access')) {
            $uid = auth()->user()->uid;
            return redirect()->to("/{$uid}/panel");
        }

        set_breadcrumb('Home', '/');
        set_breadcrumb('Dashboard');

        return view('App\Modules\Dashboard\Views\index', [
            'title' => 'Painel Principal',
            'layout' => ThemeManager::getModuleLayout('Dashboard')
        ]);
    }
}
