<?php
class IndexController extends BaseController {
    /**
     * indexAction 这就是一个测试
     * @author yangbiao 243506597@qq.com
     * @copyright (c) 2016
     * @version 1.0
     * @access public
     * @license /index/index/ POST
     * @param string platform R|平台
     * @param string version R|版本号
     * @return json
     * @field int status 消息代码
     * @field string msg 消息说明
     * @field json data 返回数据
     * @fieldinfo string date 当日中文日期
     * @fieldinfo int is_read 1.无红点2.显示红点
     * @fieldinfo array status_data 状态值
     * @throws 9999 If 获取成功
     * @jsondata {"status":1,"msg":"请求成功","data":{"date":"2016年01月07日","is_read":0,"status_data":{"success":"成功"}}}
     */
    public function indexAction() {//默认Action
        core::test();
        die;
        $res = (new yafussModel())->getlist();
        $this->getView()->assign("res", $res);
        $this->getView()->assign("content", "Hello World");
        $this->getView()->display('index/index.phtml');
    }

    public function uploadAction(){
        echo 'index-index-upload';
        die;
        $name='file';
        $mod = 'test';
        if(empty($_FILES) || empty($_FILES[$name]['tmp_name'])){
            $this->message('上传文件有误');
        }
        // 执行上传
        $upload = new Uploader();
        $upload->maxsize = 2000000;
        // 设置允许类型
        $upload->allowtype = ["binary/octet-stream", "image/png", "image/gif", "image/jpeg", "image/jpg", "image/bmp"];
        // 指定文件保存的相对路径,那么该文件的绝对路径就是 APP_PATH . $upload_path
        $upload->path = $mod.'/'.date('Ymd').'/';

        //执行上传并判断
        if(!$upload->upload($_FILES[$name])){
            $this->message('上传失败');
        }
        $url=IMGURL.$upload->filePath;

        //end 因需要插入ID 所以修正替换此处代码


    }

    public function goodsImageAction()
    {
        error_reporting(E_ALL | ~E_NOTICE);

        //查询当前的商品sku和本地的id以及状态
        $_id = (int)$this->getRequest()->getRequest('id', '1');
        $_new_id = $_id+1;

        echo $_id;

        if($_id > 12332 ){
            echo '跑完收工';
            die;
        }
        echo '<hr>';
        $db_insert = $this->db;
        $_get_columns = [
            'id',
            'bn',
            'status',
        ];
        $_get_where = ['id' => $_id];
        $_get_table = 'goods';
        $_get_desc = $db_insert->get($_get_table, $_get_columns, $_get_where);
        $sku = $_get_desc['bn'];
        $sku_id = $_get_desc['id'];
        if (empty($sku)) {
            $_new_id = $_id+1;
            echo '<script language="javascript" type="text/javascript">window.location.href="http://test.yaf.com/index/goodsImage?id='.$_new_id.'";</script> ';
            die;
        }
        $db_read = $this->db2;

        $columns = [
            'goods_id',
            'image_default_id',
            'tv_pic',
            'thumbnail_pic',
        ];
        $where = ['bn' => $sku];
        $table = 'sdb_b2c_goods';
        $_goods_desc = $db_read->get($table, $columns, $where);

        $_image_attach_where = [
            'AND' => [
                'target_type' => 'goods',
                'target_id'   => $_goods_desc['goods_id'],
            ],
        ];

        $_image_attach_table = 'sdb_image_image_attach';
        $_image_attach_columns = ['image_id'];
        $_image_id_array = $db_read->select($_image_attach_table, $_image_attach_columns, $_image_attach_where);


        foreach ($_image_id_array as $key => &$_value) {
            $_value['is_default'] = 0;
            $_value['is_thumbnail_pic'] = 0;
            if ($_goods_desc['image_default_id'] == $_value['image_id']) {
                $_value['is_default'] = 1;
            }
        }
        $_image_id_array_add['image_id'] = $_goods_desc['thumbnail_pic'];
        $_image_id_array_add['is_default'] = 0;
        $_image_id_array_add['is_thumbnail_pic'] = 1;

        array_push($_image_id_array, $_image_id_array_add);


        foreach ($_image_id_array as $_key => &$_val) {
            $_images_array[] = $_val['image_id'];
        }

        $_image_where = [
            'image_id' => $_images_array,
        ];

        $_image_table = 'sdb_image_image';
        $_image_columns = ['image_id', 'url', 'l_url', 'm_url', 's_url'];
        $_image_array = $db_read->select($_image_table, $_image_columns, $_image_where);

        foreach($_image_array as &$_image_value){
            $_image_value['sku'] = $sku;
            $_image_value['goods_id'] = $sku_id;
            $_image_value['url_has'] = $this->is_404($_image_value['url']);
            $_image_value['l_url_has'] = $this->is_404($_image_value['l_url']);
            $_image_value['m_url_has'] = $this->is_404($_image_value['m_url']);
            $_image_value['s_url_has'] = $this->is_404($_image_value['s_url']);
        }
        echo '<pre>';
        print_r($_image_array);
        $res = $db_insert->insert('goods_image',$_image_array);
        echo '<hr>';
        echo $db_insert->last_query();

        if($res){
            $_up_data = [
                'status'=>1
            ];
            $_up_where = [
                'id'=>$_id
            ];
            $res = $db_insert->update('goods',$_up_data,$_up_where);
        }
        $_new_id = $_id+1;
        echo '<script language="javascript" type="text/javascript">window.location.href="http://test.yaf.com/index/goodsImage?id='.$_new_id.'";</script> ';
        die;
    }
    //1丢失2存在
    private function next($id){
        $nextId=$id+1;
    }
    private function is_404($_url)
    {
        if(empty($_url) || is_null($_url)){
            return 3;
        }
        $_base_url = 'http://123.57.78.61/';
        $url = $_base_url.$_url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($status == 404) ? 1 : 2;
    }


}

