<?php
class Errores extends Controller{
    //private $view;

    function __construct(){
        parent::__construct();
        error_log('Errores::construct -> Inicio de errores');
    }

    function render(){        
        $this -> view -> render('errores/index');
    }
}
?>