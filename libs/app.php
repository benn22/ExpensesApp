<?php

require_once 'controllers/errores.php';


class App
{
    function __construct()
    {
        $url = isset($_GET['url']) ? $_GET['url'] : null;
        $url = rtrim($url, '/');
        $url = explode('/', $url);
        //user/updatePhoto

        if (empty($url[0])) {
            error_log('APP::construct -> No hay controlador especificado');
            $archivoController = 'controllers/login.php';
            require_once $archivoController;
            $controller = new Login();
            $controller->loadModel('login');
            $controller->render();
            return false;
        }

        $archivoController = 'controllers/' . $url[0] . '.php';

        if (file_exists($archivoController)) {
            require_once $archivoController;
            $controller = new $url[0];
            $controller->loadModel($url[0]);

            if (isset($url[1])) {
                if (method_exists($controller, $url[1])) {
                    if (isset($url[2])) {
                        $nparam = count($url) - 2;
                        $params = [];
                        for ($i = 0; $i < $nparam; $i++) {
                            array_push($params, $url[$i + 2]);
                        }

                        $controller->{$url[1]}($params);
                    } else {
                        //No tiene parametros, se manda a llamar el metodo tal cual       
                        $controller->{$url[1]}();
                    }
                } else {
                    //Error, no existe el metodo
                    $controller = new Errores();
                }
            } else {
                //No hay metodo a cargar, se carga el metodo por default
                $controller->render();
            }
        } else {
            //No existe el archivo, manda error
            $controller = new Errores();
        }
    }
}
?>