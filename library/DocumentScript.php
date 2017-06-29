<?php

class DocumentScript
{

    /**
     * [showActionDoc 获取指定action的文档信息]
     *
     * @param  string $controllerPath 控制器路径
     * @param  string $actionName     方法名
     *
     * @return html
     */
    public static function showActionDoc($controllerPath, $controllerName, $actionName)
    {
        if (! file_exists($controllerPath)) {
            return array();
        }
        $handle = fopen($controllerPath, "r");
        //当前文件的指定接口文档信息
        $actionDocument = [];
        //是否触发文档标记
        $tagStart = false;
        //是否触发指定的文档标记
        $tagNameStart = false;
        if ($handle) {
            while (! feof($handle)) {
                $buffer = fgets($handle, 4096);
                if (strpos($buffer, '/**') != false) {
                    $tagStart = true;
                }
                if ($tagStart && stripos($buffer, '* ' . $actionName) != false) {
                    $tagNameStart = true;
                }
                if ($tagNameStart) {
                    $actionDocument[] = $buffer;
                }
                if ($tagNameStart && strpos($buffer, '*/') != false) {
                    break;
                }
            }
            fclose($handle);
        }
        //解析数据
        $result = self::parseDoc($actionDocument);
        $result['controller'] = $controllerName;
        $result['fileinfo'] = filemtime($controllerPath);

        return $result;
    }

    /**
     * [showActionListDoc 获取所有接口的文档信息]
     *
     * @param  string $controllerPath   控制器路径
     * @param  string $arrayControllers 要排除的控制器
     *
     * @return html
     */
    public static function showActionListDoc($controllersPath, $arrayControllers)
    {
        if (! is_dir($controllersPath)) {
            return array();
        }
        $controllerPathList = [];
        $files = [];
        if ($dh = opendir($controllersPath)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != "." && $file != ".." && ! is_dir($controllersPath . "/" . $file)) {
                    if ('.' == substr($file, 0, 1) || in_array($file, $arrayControllers)) {
                        continue;
                    }
                    $controllerPathList[] = $controllersPath . '/' . $file;
                    $files[] = $file;
                }
            }
            closedir($dh);
        }
        if (empty($controllerPathList)) {
            return array();
        }

        //用于存储所有的文档列表信息
        $actionDocumentList = [];
        $tagnum = 1;//标记数

        foreach ($controllerPathList as $pathKey => $controllerPath) {
            if (! file_exists($controllerPath)) {
                return array();
            }
            $handle = @fopen($controllerPath, "r");
            $currentDocument = [];//当前的解析包
            $tagStart = false;//是否触发文档标记

            if ($handle) {
                while (! feof($handle)) {
                    $buffer = fgets($handle, 4096);
                    if (strpos($buffer, '/**') != false) {
                        $tagStart = true;
                    }
                    if ($tagStart) {
                        $currentDocument[] = $buffer;
                    }
                    if ($tagStart && strpos($buffer, '*/') != false) {
                        list($controllerName, $filetag) = explode('.', $files[$pathKey]);
                        $actionDocumentList[$tagnum] = self::parseDoc($currentDocument);
                        $actionDocumentList[$tagnum]['controller'] = $controllerName;
                        $actionDocumentList[$tagnum]['fileinfo'] = filemtime($controllerPath);
                        $tagStart = false;
                        $currentDocument = [];
                        $tagnum++;
                    }
                }
                fclose($handle);
            }
        }

        return $actionDocumentList;
    }

    /**
     * [parseDoc 解析文档数据包]
     *
     * @param  array $docStream 要解析的数据包
     *
     * @return array
     */
    public static function parseDoc($docStream)
    {
        $result = [];
        $result['docAction'] = '';
        $result['docTitle'] = '';
        $result['docAuthName'] = '';
        $result['docAuthContact'] = '';
        $result['docCopyright'] = '';
        $result['docVersion'] = '';
        $result['docAccess'] = '';
        $result['docLicense'] = '';
        $result['docRequestType'] = '';
        $result['docParam'] = [];
        $result['docReturn'] = '';
        $result['docField'] = [];
        $result['docThrows'] = [];
        $result['jsonData'] = '';
        $result['docTodo'] = '';
        foreach ($docStream as $key => $value) {
            if ($value == "/**" || $value == "*/") {
                continue;
            }

            $newValue = explode(' ', trim($value));

            $xing = isset($newValue[0]) ? $newValue[0] : '';
            $tag = isset($newValue[1]) ? $newValue[1] : '';
            $con1 = isset($newValue[2]) ? $newValue[2] : '';
            $con2 = isset($newValue[3]) ? $newValue[3] : '';
            $con3 = isset($newValue[4]) ? $newValue[4] : '';


            $actionName = '';
            if (false != strpos($tag, 'Action')) {
                $actionName = $tag;
                $tag = 'Action';
            }
            if ('*' == $xing) {
                switch ($tag) {
                    case 'Action':
                        $result['docAction'] = $actionName;
                        $result['docTitle'] = $con1;
                        break;
                    case '@author':
                        $result['docAuthName'] = $con1;
                        $result['docAuthContact'] = $con2;
                        break;
                    case '@copyright':
                        $result['docCopyright'] = $con1 . ' ' . $con2 . ' ' . $con3;
                        break;
                    case '@version':
                        $result['docVersion'] = $con1;
                        break;
                    case '@access':
                        $result['docAccess'] = $con1;
                        break;
                    case '@license':
                        $result['docLicense'] = strtolower($con1);
                        $result['docRequestType'] = $con2;
                        break;
                    case '@param':
                        $result['docParam'][$key]['type'] = $con1;
                        $result['docParam'][$key]['name'] = $con2;
                        list($result['docParam'][$key]['isneed'], $result['docParam'][$key]['description']) = explode('|', $con3);
                        break;
                    case '@return':
                        $result['docReturn'] = $con1;
                        break;
                    case '@field':
                        $result['docField'][$key]['type'] = $con1;
                        $result['docField'][$key]['name'] = $con2;
                        $result['docField'][$key]['description'] = $con3;
                        break;
                    case '@fieldinfo':
                        $result['docFieldinfo'][$key]['type'] = $con1;
                        $result['docFieldinfo'][$key]['name'] = $con2;
                        $result['docFieldinfo'][$key]['description'] = $con3;
                        break;
                    case '@throws':
                        $result['docThrows'][$key]['e'] = $con1;
                        $result['docThrows'][$key]['t'] = $con2;
                        $result['docThrows'][$key]['m'] = $con3;
                        break;
                    case '@jsondata':
                        $result['jsonData'] = $con1;
                        break;
                    case '@todo':
                        $result['docTodo'] = $con1;
                        break;
                    default:
                        break;
                }
            }
        }

        return $result;
    }
}