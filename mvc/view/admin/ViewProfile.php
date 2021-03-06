<?php
namespace manguto\cms5\mvc\view\admin;

use manguto\cms5\mvc\view\ViewAdmin;

class ViewProfile extends ViewAdmin
{

    static function get_admin_profile($user)
    {
        self::PageSite("profile", [
            'user' => $user->GET_DATA($extraIncluded = true, $ctrlParametersIncluded = false, $referencesIncluded = true, $singleLevelArray = false),
            'form_action' => '/admin/profile',
            'link_change_password' => '/admin/profile/change-password'
        ]);
    }

    static function get_admin_profile_change_password()
    {   
        self::PageSite("profile-change-password", [
            'form_action' => '/admin/profile/change-password'
        ]);        
    }
}