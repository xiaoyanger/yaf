<?php

/**
 *
//执行图片上传示例：
//1.创建对象
$upload = new FileUpload();
//2.设置允许类型
$upload->allowtype=array("image/png","image/gif","image/jpeg","image/pjpeg");
//3. 指定文件保存的相对路径,那么该文件的绝对路径就是 APP_PATH."education/"
$upload->path="education/";
//4. 执行上传并判断
if(!$upload->upload($_FILES["logo"])){
die("图片上传失败！原因：".$upload->errorMess);
}
//5. 获取并保存上传后的文件路径
$filePath= $upload->filePath;//新文件相对地址
 */
class Uploader {

    private $allowtype = array(); //允许上传文件的类型,可以使用set()设置，使用小字母
    private $maxsize = 1000000;  //限制文件上传大小，单位是字节,可以使用set()设置
    private $israndname = true;   //设置是否随机重命名 false为不随机,可以使用set()设置
    private $originName;         //源文件名
    private $tmpFileName;        //临时文件名
    private $fileType;        //文件类型
    private $fileSize;          //文件大小
    private $newFileName;      //新文件名
    private $errorNum = 0;      //错误号
    private $errorMess = "";      //错误报告消息
    private $path;         //上传文件的相对路径
    private $savePath;      //新文件保存路径
    private $filePath;      //新文件相对地址


    public function __set($key, $val) {
        $this->$key = $val;
    }

    public function __get($key) {
        return $this->$key;
    }

    //初始化上传文件信息
    private function upinit($upfile) {
        //1. 获取上传文件的名字
        //$upfile = $_FILES[$fileField];
        //初始化信息
        $this->originName = $upfile["name"];   //源文件名
        $this->tmpFileName = $upfile["tmp_name"]; //临时上传文件名
        $this->fileSize = $upfile["size"];   //上传文件的大小
        $this->fileType = $upfile["type"];    //上传文件类型
        $this->errorNum = $upfile["error"];   //错误号
        $this->savePath = UPLOADPATH.$this->path;
        $this->makeDir($this->savePath);//创建文件目录
    }

    private function makeDir($path)
    {
        if (!file_exists($path))
        {
            mkdir($path,0777,true);
        }
    }



    //过滤上传文件错误(已知错误、大小、类型、上传目录等)
    private function upfilter() {
        //判断上传的1--7的错误号
        if ($this->errorNum > 0) {
            return false;
        }
        //上传文件大小的过滤
        if ($this->fileSize > $this->maxsize) {
            $this->errorNum = -2;
            return false;
        }
        //文件上传类型的过滤
        if (count($this->allowtype) > 0 && !in_array($this->fileType, $this->allowtype)) {
            $this->errorNum = -1;
            return false;
        }

        //上传文件的目录
        if (!file_exists($this->savePath) || !is_dir($this->savePath) || !is_writable($this->savePath)) {
            $this->errorNum = -5;
            return false;
        }
        return true;
    }

    //随机一个上传文件的名称
    private function randName() {
        //判断是否需要产生随机名
        if ($this->israndname)
            $fileinfo = pathinfo($this->originName);
        do {
            $this->newFileName = date("YmdHis") . rand(1000, 9999) . "." . $fileinfo["extension"]; //随机产生一个的文件名
        } while (file_exists($this->savePath . "/" . $this->newFileName));
    }

    /**
     * 调用该方法上传文件
     * @param	string	$fileFile	上传文件的表单名称 例如：<input type="file" name="myfile"> 参数则为myfile
     * @return	bool			 如果上传成功返回数true
     */
    function upload($fileField) {

        //初始化上传信息
        $this->upinit($fileField);

        //过滤上传文件
        if ($this->upfilter() === false) {
            $this->errorMess = $this->getError(); //根据错误号获取对应错误信息
            return false;
        }

        //随机一个上传文件的名称
        $this->randName();

        //6. 执行上传处理
        if (is_uploaded_file($this->tmpFileName)) {
            if (move_uploaded_file($this->tmpFileName, $this->savePath . "/" . $this->newFileName)) {
                //将上传成功后的文件路径
                $this->filePath = $this->path.$this->newFileName;

                return true;
            } else {
                $this->errorNum = -3;
                $this->errorMess = "上传文件失败！";
            }
        } else {
            $this->errorNum = -6;
            $this->errorMess = "不是一个上传的文件！";
        }
        return false;
    }

    //设置上传出错信息
    private function getError() {
        $str = "上传文件{$this->originName}时出错 : ";
        switch ($this->errorNum) {
            case 7: $str .= "TMP临时文件写入失败";
                break;
            case 6: $str .= "找不到临时文件夹。";
                break;
            case 4: $str .= "没有文件被上传";
                break;
            case 3: $str .= "文件只有部分被上传";
                break;
            case 2: $str .= "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                break;
            case 1: $str .= "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                break;
            case -1: $str .= "未允许类型";
                break;
            case -2: $str .= "文件过大,上传的文件不能超过{$this->maxsize}个字节";
                break;
            case -3: $str .= "上传失败";
                break;
            case -4: $str .= "建立存放上传文件目录失败，请重新指定上传目录";
                break;
            case -5: $str .= "必须指定上传文件的路径";
                break;
            default: $str .= "未知错误";
        }
        return $str;
    }

}
