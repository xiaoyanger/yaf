<?php

class IndexController extends Admin\AdminCommonController {

    public function indexAction() {
//        RoutePlugin::testtt();
//        $model = new Testas;
//        $model->test();
//        die;
        $this->getView()->display('index/index.phtml');
    }

    public function tableAction() {

        $this->getView()->display('index/table.phtml');
    }

    public function loginAction(){
        $this->getView()->display('login/login.phtml');
    }



}

