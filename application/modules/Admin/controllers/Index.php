<?php

class IndexController extends Admin\AdminCommonController {

    public function indexAction() {
        $this->getView()->display('index/index.phtml');
    }

    public function tableAction() {

        $this->getView()->display('index/table.phtml');
    }

    public function loginAction(){
        $this->getView()->display('login/login.phtml');
    }



}

