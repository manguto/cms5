<?php
namespace manguto\cms5\mvc\control\dev;

use manguto\cms5\mvc\model\User;
use manguto\cms5\lib\ProcessResult;
use manguto\cms5\lib\Exception;
use manguto\cms5\mvc\control\ControlDev;
use manguto\cms5\mvc\view\dev\ViewUsers;

class ControlUsers extends ControlDev
{

    static function RunRouteAnalisys($app)
    {
        $app->get('/dev/users', function () {
            self::PrivativeDevZone();
            $users = (new User())->search();
            ViewUsers::get_dev_users($users);
        });

        $app->get('/dev/users/create', function () {
            self::PrivativeDevZone();
            ViewUsers::get_dev_users_create();
        });

        $app->post('/dev/users/create', function () {
            self::PrivativeDevZone();
            ControlUsers::post_dev_users_create();
        });

        $app->get('/dev/users/:id', function ($id) {
            self::PrivativeDevZone();
            $user = new User($id);
            ViewUsers::get_dev_user($user);  
        }); 

        $app->get('/dev/users/:id/delete', function ($id) {
            self::PrivativeDevZone();
            ControlUsers::get_dev_user_delete($id);
        });

        $app->get('/dev/users/:id/edit', function ($id) {
            self::PrivativeDevZone();
            $user = new User($id);
            ViewUsers::get_dev_user_edit($user);
        });

        $app->post('/dev/users/:id/edit', function ($id) {
            self::PrivativeDevZone();
            ControlUsers::post_dev_user_edit($id);
        });
    }

   

    static function post_dev_users_create()
    {
        // deb($_POST,0);
        $_POST['password'] = User::password_crypt($_POST['password']);
        // deb($_POST);

        try {
            $user = new User();
            $user->SET_DATA($_POST);
            $user->verifyFieldsToCreateUpdate();
            // deb($user);
            $user->save();
            ProcessResult::setSuccess("Usuário salvo com sucesso!");
            headerLocation("/dev/users");
            exit();
        } catch (Exception $e) {
            ProcessResult::setError($e);
            headerLocation("/dev/users/create");
            exit();
        }
    }

    static function post_dev_user_edit($id)
    {
                
        //deb($_POST);
        try {
            $user = new User($id);
            //deb("$user",0);
            //deb($_POST,0);
            $user->SET_DATA($_POST);
            //deb("$user");
            //deb($user);
            
            $user->verifyFieldsToCreateUpdate();            
            $user->save();
            //deb("$user");
            
            ProcessResult::setSuccess("Usuário atualizado com sucesso!");
            headerLocation("/dev/users");
            exit();
        } catch (Exception $e) {
            ProcessResult::setError($e);
            headerLocation("/dev/users/create");
            exit();
        }
    }

    static function get_dev_user_delete($id)
    {
        $user = new User($id);
        $user->delete();
        ProcessResult::setSuccess("Usuário removido com sucesso!");
        headerLocation("/dev/users");
        exit();
    }
}

?>