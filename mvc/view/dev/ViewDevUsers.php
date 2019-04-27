<?php
namespace manguto\cms5\mvc\view\dev;


class ViewDevUsers extends ViewDev
{

    static function get_dev_users($users)
    {   
        self::PageDev("users", [
            'users' => $users
        ]);
    }
    
    static function get_dev_users_create()
    {
        self::PageDev("users-create", [
            'temp' => 'usuario' . date("is")
        ]);
    }
    static function get_dev_user($user)
    {   
        self::PageDev("users-view", [
            'user' => $user->GetData($extraIncluded = true, $ctrlParametersIncluded = false, $referencesIncluded = true, $singleLevelArray = false)
        ]);        
    }
    
    static function get_dev_user_edit($user)
    {   
        self::PageDev("users-update", [
            'user' => $user->GetData($extraIncluded = true, $ctrlParametersIncluded = false, $referencesIncluded = true, $singleLevelArray = false)
        ]);
    }
}