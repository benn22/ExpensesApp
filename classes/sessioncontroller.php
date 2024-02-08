<?php

require_once 'classes/session.php';
require_once 'models/userModel.php';

class SessionController extends Controller{
    //private $defaultSites;

    private $userSession;
    private $userName;
    private $userId;

    private $session;
    private $sites;

    private $user;

    function __construct()
    {
        parent::__construct();
        $this->init();
    }

    function init(){
        $this->session = new Session();
        $json = $this->getJSONFileConfig();

        $this->sites = $json['sites'];
        $this->defaultSites = $json['default-sites'];
        $this->validateSession();
    }

    private function getJSONFileConfig(){
        $string = file_get_contents("config/access.json");
        $json = json_decode($string, true);

        return $json;
    }

    public function validateSession(){
        error_log('SESSIONCONTROLLER::validateSession()');
        //Si existe la sesion
        if($this->existsSession()){
            $role = $this->getUserSessionData()->getRole();
            //Validar si el acceso es publico
            if($this->isPublic()){
                $this->redirectDefaultSiteByRole($role);
            }
            else{
                if($this->isAuthorized($role)){
                    //Se permite el acceso
                }
                else{
                    $this->redirectDefaultSiteByRole($role);
                }
            }
        }
        else{
            //No existe la sesion
            if($this->isPublic()){
                //No pasa nada, se deja ingresar
            }
            else{
                header('location: ' . constant('URL') . '');
            }
        }
    }

    function existsSession(){
        if(!$this->session->exists()) return false;
        if($this->session->getCurrentUser() == NULL) return false;

        $userid = $this->session->getCurrentUser();
        if($userid) return true;
        
        return false;
    }

    function getUserSessionData(){
        $id = $this->session->getCurrentUser();
        $this->user = new UserModel();
        $this->user->get($id);
        error_log('SESSIONCONTROLLER::getUserSessionData -> ' . $this->user->getUsername());
        return $this->user;
    }

    function isPublic(){
        $currentURL = $this->getCurrentPage();        
        $currentURL = preg_replace("/\?.*/", "", $currentURL);
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['access'] === 'public'){
                return true;
            }
        }

        return false;
    }

    function getCurrentPage(){
        $actualLink = trim("$_SERVER[REQUEST_URI]");
        $url = explode('/', $actualLink);
        error_log("SESSIONCONTROLLER::getCurrentPage(): actualLink =>" . $actualLink . ", url => " . $url[2]);
        return $url[2];
    }

    private function redirectDefaultSiteByRole($role){
        $url = '';
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($this->sites[$i]['role'] == $role){
                error_log('SESSIONCONTROLLER::redirectDefaultSiteByRole-> ');
                $url = 'expenses/' . $this->sites[$i]['site'];
            break;
            }
        }
        header('location:' . constant('URL') . $url);
    }

    private function isAuthorized($role){
        $currentURL = $this->getCurrentPage();        
        $currentURL = preg_replace("/\?.*/", "", $currentURL);

        for($i = 0; $i < sizeof($this->sites); $i++){
            if($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['role'] === $role){
                return true;
            }
        }

        return false;
    }

    function initialize($user){
        $this->session->setCurrentUser($user->getId());
        $this->authorizeAccess($user->getRole());
    }

    function authorizeAccess($role){
        switch($role){
            case 'user':
                $this->redirect($this->defaultSites['user'], []);
            break;
            case 'admin':
                $this->redirect($this->defaultSites['admin'], []);
            break;
        }
    }

    function logout(){
        $this->session->closeSession();
    }
}
?>