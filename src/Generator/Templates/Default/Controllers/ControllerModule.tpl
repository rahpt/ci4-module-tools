<?php

namespace App\Modules\__Module__\Controllers;

use CodeIgniter\Controller;

class __Module__Controller extends Controller
{
    public function index()
    {
        set_breadcrumb('Home', '/');
        set_breadcrumb('__Module__');

        return view('App\Modules\__Module__\Views\dashboard', [
            'title' => '__Module__ Module'
        ]);
    }
}
