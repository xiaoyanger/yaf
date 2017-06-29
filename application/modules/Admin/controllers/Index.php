<?php

class IndexController extends BaseController {
    /**
     * indexAction
     *
     * @date
     */
    public function indexAction() {

        $this->getView()->display('index/index.phtml');
    }



}

