<?php

class DocumentController extends BaseController
{
    /**
     * indexAction
     * 接口文档地址
     * @author yangbiao<yanger_biao@163.com>
     * @date
     */
    public function indexAction()
    {
        $path = substr(__FILE__, 0, strrpos(__FILE__, '/'));
        $controllersPath = $path;//控制器所在文件夹
        //排除的文件
        $arrayControllers = [
            'Base.php',
            'ControllerTrait.php',
            'Document.php',
        ];

        $top = [
            'title'      => "默认模块[{$this->_module_name}]接口文档",
            'auther'     => 'yb',
            'lastchange' => date('Y-m-d', time()),
        ];
        $doc_result = DocumentScript::showActionListDoc($controllersPath, $arrayControllers);

        $this->getView()
             ->assign('top', $top);
        $this->getView()
             ->assign('apiroot', BASEURL);

        foreach ($doc_result as $k => $v) {
            $data[$k] = $v;
        }

        $this->getView()
             ->assign('data', $data);
        $this->getView()
             ->display('document/index.phtml');
    }

}