<?php
class TestController extends BaseController {
    public function indexAction(){
        echo 'api-test-index';
        die;
    }

    public function testAction(){
        echo 'api-test-test';
        die;
    }

}