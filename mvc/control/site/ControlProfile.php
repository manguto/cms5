<?php
namespace manguto\cms5\mvc\control\site;

use manguto\cms5\mvc\model\User;
use manguto\cms5\lib\ProcessResult;
use manguto\cms5\mvc\view\site\ViewProfile; 
use manguto\cms5\lib\Exception;
use manguto\cms5\lib\Sessions;
use manguto\cms5\mvc\control\ControlSite;

class ControlProfile extends ControlSite
{

    static function RunRouteAnalisys($app)
    {
        $app->get('/profile', function () {
            self::PrivativeZone();
            ControlProfile::get_profile();
        });
        $app->post('/profile', function () {
            self::PrivativeZone();
            ControlProfile::post_profile();
        });
        $app->get('/profile/change-password', function () {
            self::PrivativeZone();
            ControlProfile::get_profile_change_password();
        });
        $app->post('/profile/change-password', function () {
            self::PrivativeZone();
            ControlProfile::post_profile_change_password();
        });
    }

    static function get_profile()
    {
        
        $user = User::getSessionUser();
        ViewProfile::get_profile($user);
    }

    static function post_profile()
    {   
        $user = User::getSessionUser();
        { // === PARAMETERS VERIFICATION & CERTIFICATION
            $_POST['adminzoneaccess'] = $user->getAdminzoneaccess();
            $_POST['password'] = $user->getPassword();
            $_POST['login'] = $user->getLogin();
            /*if (checkUserLoggedAdmin() === false) {
                $_POST['login'] = $_POST['email'];
            }*/
        }
        $user->SET_DATA($_POST);
        try {
            $user->verifyFieldsToCreateUpdate();
            $user->save();
            ProcessResult::setSuccess('Usuário salvo com sucesso!');
            headerLocation('/profile');
            exit();
        } catch (Exception $e) {
            ProcessResult::setError($e);
            Sessions::set(\manguto\cms5\mvc\control\admin\ControlProfile::key,$_POST);  
            headerLocation('/profile');
            exit();
        }
    }

    static function get_profile_change_password()
    {
        ViewProfile::get_profile_change_password();
    }

    static function post_profile_change_password()
    {
        $user = User::getSessionUser();
        try {
            $current_pass = isset($_POST['current_pass']) ? $_POST['current_pass'] : '';
            $new_pass = isset($_POST['new_pass']) ? $_POST['new_pass'] : '';
            $new_pass_confirm = isset($_POST['new_pass_confirm']) ? $_POST['new_pass_confirm'] : '';
            $user->verifyPasswordUpdate($current_pass, $new_pass, $new_pass_confirm);
            $user->setPassword(User::password_crypt($new_pass));
            $user->save();
            ProcessResult::setSuccess('Senha alterada com sucesso!!!');
            headerLocation('/');
            exit();
        } catch (Exception $e) {
            ProcessResult::setError($e);
            headerLocation('/profile/change-password');
            exit();
        }
    }
}

?>