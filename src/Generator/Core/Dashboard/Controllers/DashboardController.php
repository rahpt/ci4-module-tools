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
            $user = auth()->user();

            // Ensure UID exists to avoid broken redirect
            if (empty($user->uid)) {
                $uid = $this->ensureUserUid($user);
            } else {
                $uid = $user->uid;
            }

            return redirect()->to("/{$uid}/panel");
        }

        set_breadcrumb('Home', '/');
        set_breadcrumb('Dashboard');

        return view('App\Modules\Dashboard\Views\index', [
            'title' => 'Painel Principal',
            'layout' => ThemeManager::getModuleLayout('Dashboard')
        ]);
    }

    /**
     * Ensures the user has a UID and returns it
     */
    private function ensureUserUid($user): string
    {
        $db = \Config\Database::connect();

        do {
            $uid = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $exists = $db->table('users')->where('uid', $uid)->countAllResults();
        } while ($exists > 0);

        $db->table('users')->where('id', $user->id)->update(['uid' => $uid]);
        $user->uid = $uid;

        return $uid;
    }
}
