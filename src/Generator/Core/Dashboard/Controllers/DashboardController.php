<?php

namespace App\Modules\Dashboard\Controllers;

use App\Controllers\BaseController;
use Rahpt\Ci4ModuleTheme\ThemeManager;

class DashboardController extends BaseController
{
    public function index()
    {
        set_breadcrumb('Home', '/');
        set_breadcrumb('Dashboard');

        return view('App\Modules\Dashboard\Views\index', [
            'title'  => 'Painel Principal',
            'layout' => ThemeManager::getModuleLayout('Dashboard')
        ]);
    }
}
