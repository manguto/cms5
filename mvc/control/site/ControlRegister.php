<?php
namespace manguto\cms5\mvc\control\site;

use manguto\cms5\mvc\model\User;
use manguto\cms5\lib\ProcessResult;
use manguto\cms5\mvc\view\site\ViewRegister;
use manguto\cms5\lib\Exception; 
use manguto\cms5\lib\Sessions;
use manguto\cms5\mvc\control\ControlSite;
use manguto\cms5\mvc\control\admin\ControlProfile;

class ControlRegister extends ControlSite
{ 

    static function RunRouteAnalisys($app)
    {
        $app->get('/register', function () {
            ControlRegister::get_register();
        });
        $app->post('/register', function () {
            ControlRegister::post_register();
        });
    }

    static function get_register()
    {
        if (Sessions::isset(ControlProfile::key)) {
            $registerFormValues = Sessions::get(ControlProfile::key);
            Sessions::unset(ControlProfile::key);
        } else {
            $registerFormValues = [
                'name' => '',
                'email' => '',
                'phone' => ''
            ];
        }
        ViewRegister::get_register($registerFormValues);
    }

    static function post_register()
    {
        throw new Exception("A criação de novos usuários está desabilitada até segunda ordem. Obrigado!");

        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        // -------------montagem do usuario
        $user = new User();

        $user->SET_DATA([
            'adminzoneaccess' => 0,
            'name' => $_POST['name'],
            'login' => $_POST['email'],
            'email' => $_POST['email'],
            'password' => User::password_crypt($_POST['password']),
            'phone' => $_POST['phone']
        ]);
        // deb($user,0);
        // ------------- verificacao de parametros enviados
        try {
            $user->verifyFieldsToCreateUpdate();
            $user->save();
            ProcessResult::setSuccess("Cadastro realizado com sucesso!<br/>Seja bem vindo(a) à nossa plataforma!!");
            User::login($_POST['email'], $_POST['password']);
            headerLocation('/');
            exit();
        } catch (Exception $e) {
            ProcessResult::setError($e);
            headerLocation('/login');
            exit();
        }
    }
}

?>