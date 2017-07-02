<?php

class core
{
    public static function test(){
        echo 123123;die;
    }
    public static function gpc($k, $var = 'G')
    {
        switch ($var) {
            case 'G':
                $var = &$_GET;
                break;
            case 'P':
                $var = &$_POST;
                break;
            case 'C':
                $var = &$_COOKIE;
                break;
            case 'R':
                $var = isset($_GET[$k]) ? $_GET : (isset($_POST[$k]) ? $_POST : $_COOKIE);
                break;
            case 'S':
                $var = &$_SERVER;
                break;
        }

        return isset($var[$k]) ? $var[$k] : null;
    }

    public static function addslashes(&$var)
    {
        if (is_array($var)) {
            foreach ($var as $k => &$v) {
                self::addslashes($v);
            }
        } else {
            $var = addslashes($var);
        }

        return $var;
    }

    public static function stripslashes(&$var)
    {
        if (is_array($var)) {
            foreach ($var as $k => &$v) {
                self::stripslashes($v);
            }
        } else {
            $var = stripslashes($var);
        }

        return $var;
    }

    public static function htmlspecialchars(&$var)
    {
        if (is_array($var)) {
            foreach ($var as $k => &$v) {
                self::htmlspecialchars($v);
            }
        } else {
            $var = str_replace(['&', '"', '<', '>'], ['&amp;', '&quot;', '&lt;', '&gt;'], $var);
        }

        return $var;
    }

    public static function urlencode($s)
    {
        $s = urlencode($s);

        return str_replace('-', '%2D', $s);
    }

    /**
     * 编码解析防止+号丢失
     */
    public static function urldecode($s)
    {
        if (preg_match('#%[0-9A-Z]{2}#isU', $s) > 0) {
            $s = urldecode($s);
        }

        return $s;
    }

    public static function json_decode($s)
    {
        return $s === false ? false : json_decode($s, 1);
    }

    // 替代 json_encode
    public static function json_encode($data)
    {
        if (is_array($data) || is_object($data)) {
            $islist = is_array($data) && (empty($data) || array_keys($data) === range(0, count($data) - 1));
            if ($islist) {
                $json = '[' . implode(',', array_map(['core', 'json_encode'], $data)) . ']';
            } else {
                $items = [];
                foreach ($data as $key => $value) {
                    $items[] = self::json_encode("$key") . ':' . self::json_encode($value);
                }
                $json = '{' . implode(',', $items) . '}';
            }
        } elseif (is_string($data)) {
            $string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
            $json = '';
            $len = strlen($string);
            for ($i = 0;$i < $len;$i++) {
                $char = $string[$i];
                $c1 = ord($char);
                if ($c1 < 128) {
                    $json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
                    continue;
                }
                $c2 = ord($string[++$i]);
                if (($c1 & 32) === 0) {
                    $json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
                    continue;
                }
                $c3 = ord($string[++$i]);
                if (($c1 & 16) === 0) {
                    $json .= sprintf("\\u%04x", (($c1 - 224) << 12) + (($c2 - 128) << 6) + ($c3 - 128));
                    continue;
                }
                $c4 = ord($string[++$i]);
                if (($c1 & 8) === 0) {
                    $u = (($c1 & 15) << 2) + (($c2 >> 4) & 3) - 1;
                    $w1 = (54 << 10) + ($u << 6) + (($c2 & 15) << 2) + (($c3 >> 4) & 3);
                    $w2 = (55 << 10) + (($c3 & 15) << 6) + ($c4 - 128);
                    $json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
                }
            }
        } else {
            $json = strtolower(var_export($data, true));
        }

        return $json;
    }

    // 是否为命令行模式
    public static function is_cmd()
    {
        return ! isset($_SERVER['REMOTE_ADDR']);
    }

    //setcookie($this->conf['cookie_pre'].'auth', '', 0, $this->conf['cookie_path'], $this->conf['cookie_domain']);
    public static function setcookie($key, $value, $time = 0, $path = '', $domain = '', $httponly = true)
    {
        // 计算时差','服务器时间和客户端时间不一致的时候','最好由客户端写入。
        $_COOKIE[$key] = $value;
        if ($value != null) {
            if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
                setcookie($key, $value, $time, $path, $domain, false, $httponly);
            } else {
                setcookie($key, $value, $time, $path, $domain, false);
            }
        } else {
            if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
                setcookie($key, '', $time, $path, $domain, false, $httponly);
            } else {
                setcookie($key, '', $time, $path, $domain, false);
            }
        }
    }

    public static function form_hash($auth_key)
    {
        return substr(md5(substr($_SERVER['time'], 0, -5) . $auth_key), 16);
    }

    public static function humandate($timestamp)
    {
        $seconds = $_SERVER['time'] - $timestamp;
        if ($seconds > 31536000) {
            return date('Y-n-j', $timestamp);
        } elseif ($seconds > 2592000) {
            return ceil($seconds / 2592000) . '月前';
        } elseif ($seconds > 86400) {
            return ceil($seconds / 86400) . '天前';
        } elseif ($seconds > 3600) {
            return ceil($seconds / 3600) . '小时前';
        } elseif ($seconds > 60) {
            return ceil($seconds / 60) . '分钟前';
        } else {
            return $seconds . '秒前';
        }
    }

    /**
     * 转换字节数为其他单位
     *
     *
     * @param    string $filesize 字节大小
     *
     * @return    string    返回大小
     */
    public static function humansize($num)
    {
        if ($num > 1073741824) {
            return number_format($num / 1073741824, 2, '.', '') . 'GB';
        } elseif ($num > 1048576) {
            return number_format($num / 1048576, 2, '.', '') . 'MB';
        } elseif ($num > 1024) {
            return number_format($num / 1024, 2, '.', '') . 'KB';
        } else {
            return $num . 'Bytes';
        }
    }

    // 安全过滤','过滤掉所有特殊字符','仅保留英文下划线','中文。其他语言需要修改U的范围
    public static function safe_str($s, $ext = '')
    {
        $ext = preg_quote($ext);
        $s = preg_replace('#[^' . $ext . '\w\x{4e00}-\x{9fa5}]+#u', '', $s);

        return $s;
    }

    /**
     * xss过滤函数
     *
     * @param $string
     *
     * @return string
     */
    public static function remove_xss($string)
    {
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

        $parm1 = [
            'javascript',
            'vbscript',
            'expression',
            'applet',
            'meta',
            'xml',
            'blink',
            'link',
            'script',
            'embed',
            'object',
            'iframe',
            'frame',
            'frameset',
            'ilayer',
            'layer',
            'bgsound',
            'title',
            'base',
        ];

        $parm2 = [
            'onabort',
            'onactivate',
            'onafterprint',
            'onafterupdate',
            'onbeforeactivate',
            'onbeforecopy',
            'onbeforecut',
            'onbeforedeactivate',
            'onbeforeeditfocus',
            'onbeforepaste',
            'onbeforeprint',
            'onbeforeunload',
            'onbeforeupdate',
            'onblur',
            'onbounce',
            'oncellchange',
            'onchange',
            'onclick',
            'oncontextmenu',
            'oncontrolselect',
            'oncopy',
            'oncut',
            'ondataavailable',
            'ondatasetchanged',
            'ondatasetcomplete',
            'ondblclick',
            'ondeactivate',
            'ondrag',
            'ondragend',
            'ondragenter',
            'ondragleave',
            'ondragover',
            'ondragstart',
            'ondrop',
            'onerror',
            'onerrorupdate',
            'onfilterchange',
            'onfinish',
            'onfocus',
            'onfocusin',
            'onfocusout',
            'onhelp',
            'onkeydown',
            'onkeypress',
            'onkeyup',
            'onlayoutcomplete',
            'onload',
            'onlosecapture',
            'onmousedown',
            'onmouseenter',
            'onmouseleave',
            'onmousemove',
            'onmouseout',
            'onmouseover',
            'onmouseup',
            'onmousewheel',
            'onmove',
            'onmoveend',
            'onmovestart',
            'onpaste',
            'onpropertychange',
            'onreadystatechange',
            'onreset',
            'onresize',
            'onresizeend',
            'onresizestart',
            'onrowenter',
            'onrowexit',
            'onrowsdelete',
            'onrowsinserted',
            'onscroll',
            'onselect',
            'onselectionchange',
            'onselectstart',
            'onstart',
            'onstop',
            'onsubmit',
            'onunload',
        ];

        $parm = array_merge($parm1, $parm2);

        for ($i = 0;$i < sizeof($parm);$i++) {
            $pattern = '/';
            for ($j = 0;$j < strlen($parm[$i]);$j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                    $pattern .= '|(&#0([9][10][13]);?)?';
                    $pattern .= ')?';
                }
                $pattern .= $parm[$i][$j];
            }
            $pattern .= '/i';
            $string = preg_replace($pattern, '', $string);
        }

        return $string;
    }

    /**
     * 格式化文本域内容
     *
     * @param $string 文本域内容
     *
     * @return string
     */
    public static function trim_textarea($string)
    {
        $string = nl2br(str_replace(' ', '&nbsp;', $string));

        return $string;
    }

    /**
     * 获取当前页面完整URL地址
     */
    public static function get_url()
    {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? safe_replace($_SERVER['PHP_SELF']) : safe_replace($_SERVER['SCRIPT_NAME']);
        $path_info = isset($_SERVER['PATH_INFO']) ? safe_replace($_SERVER['PATH_INFO']) : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? safe_replace($_SERVER['REQUEST_URI']) : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . safe_replace($_SERVER['QUERY_STRING']) : $path_info);

        return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
    }

    /**
     * 获取客户端ip
     *
     * @return ip地址
     */
    public static function ip()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (! preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }

        return $ip;
    }

    /**
     * 产生随机字符串
     *
     * @param    int    $length 输出长度
     * @param    string $chars  可选的
     *
     * @return   string     字符串
     */
    public static function random($length, $chars = '0123456789qwertyuiopasdfghjklzxcvbnm')
    {
        $hash = '';
        $max = strlen($chars) - 1;
        for ($i = 0;$i < $length;$i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }

        return $hash;
    }

    /**
     * 将字符串转换为数组
     *
     * @param    string $data 字符串
     *
     * @return    array    返回数组格式','如果','data为空','则返回空数组
     */
    public static function string2array($data)
    {
        if ($data == '') {
            return [];
        }
        @eval("\$array = $data;");

        return $array;
    }

    /**
     * 将数组转换为字符串
     *
     * @param    array $data       数组
     * @param    bool  $isformdata 如果为0','则不使用new_stripslashes处理','可选参数','默认为1
     *
     * @return    string    返回字符串','如果','data为空','则返回空
     */
    public static function array2string($data, $isformdata = 1)
    {
        if ($data == '') {
            return '';
        }
        if ($isformdata) {
            $data = new_stripslashes($data);
        }

        return addslashes(var_export($data, true));
    }


    /**
     * 取得文件扩展
     *
     * @param $filename 文件名
     *
     * @return 扩展名
     */
    public static function fileext($filename)
    {
        return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
    }

    /* 对多维数组排序
		$data = array();
		$data[] = array('volume' => 67, 'edition' => 2);
		$data[] = array('volume' => 86, 'edition' => 1);
		$data[] = array('volume' => 85, 'edition' => 6);
		$data[] = array('volume' => 98, 'edition' => 2);
		$data[] = array('volume' => 86, 'edition' => 6);
		$data[] = array('volume' => 67, 'edition' => 7);
		arrlist_multisort($data, 'edition', TRUE);
	*/
    public static function arrlist_multisort(&$arrlist, $col, $asc = true)
    {
        $colarr = [];
        foreach ($arrlist as $k => $arr) {
            $colarr[$k] = $arr[$col];
        }
        $asc = $asc ? SORT_ASC : SORT_DESC;
        array_multisort($colarr, $asc, $arrlist);

        return $arrlist;
    }

    /**
     * 判断email格式是否正确
     *
     * @param $email
     */
    public static function is_email($email)
    {
        return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
    }

    /**
     * IE浏览器判断
     */

    public static function is_ie()
    {
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if ((strpos($useragent, 'opera') !== false) || (strpos($useragent, 'konqueror') !== false)) {
            return false;
        }
        if (strpos($useragent, 'msie ') !== false) {
            return true;
        }

        return false;
    }

    public static function is_robot()
    {
        $robots = ['robot', 'spider', 'slurp'];
        foreach ($robots as $robot) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], $robot) !== false) {
                return true;
            }
        }

        return false;
    }


    /**
     * 文件下载
     *
     * @param $filepath 文件路径
     * @param $filename 文件名称
     */

    public static function file_down($filepath, $filename = '')
    {
        if (! $filename) {
            $filename = basename($filepath);
        }
        if (self::is_ie()) {
            $filename = rawurlencode($filename);
        }
        $filetype = self::fileext($filename);
        $filesize = sprintf("%u", filesize($filepath));
        if (ob_get_length() !== false) {
            @ob_end_clean();
        }
        header('Pragma: public');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: none');
        header('Content-type: ' . $filetype);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-length: ' . $filesize);
        readfile($filepath);
        exit;
    }

    /**
     * 判断字符串是否为utf8编码','英文和半角字符返回ture
     *
     * @param $string
     *
     * @return bool
     */
    public static function is_utf8($string)
    {
        return preg_match('%^(?:
					[\x09\x0A\x0D\x20-\x7E] # ASCII
					| [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
					| \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
					| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
					| \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
					| \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
					| [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
					| \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
					)*$%xs', $string);
    }

    /**
     * 生成随机字符串
     *
     * @param string $lenth 长度
     *
     * @return string 字符串
     */
    public static function create_randomstr($length = 6)
    {
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $strlen = 62;
        while ($length > $strlen) {
            $str .= $str;
            $strlen += 60;
        }

        $str = str_shuffle($str);

        return substr($str, 0, $length);
    }

    /**
     * 优雅输出print_r()函数所要输出的内容
     *
     * 用于程序调试时,完美输出调试数据,功能相当于print_r().当第二参数为true时(默认为:false),功能相当于var_dump()。
     * 注:本方法一般用于程序调试
     * @access public
     *
     * @param array   $data   所要输出的数据
     * @param boolean $option 选项:true或 false
     *
     * @return array            所要输出的数组内容
     */
    public static function dump($data, $option = false)
    {

        //当输出print_r()内容时
        if (! $option) {
            echo '<pre>';
            print_r($data);
            echo '</pre>';
        } else {
            ob_start();
            var_dump($data);
            $output = ob_get_clean();

            $output = str_replace('"', '', $output);
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

            echo '<pre>', $output, '</pre>';
        }

        exit;
    }

    public static function _xss_check()
    { //检测xss漏洞,UBB
        $temp = strtoupper(urldecode(urldecode($_SERVER['REQUEST_URI'])));
        if (strpos($temp, '<') !== false || strpos($temp, '"') !== false || strpos($temp, 'CONTENT-TRANSFER-ENCODING') !== false) {
            exit('xss');
        }

        return true;
    }

    /**
     * 对字符串进行加密和解密
     *
     * @param  <string> $string
     * @param  <string> $operation  DECODE 解密 | ENCODE  加密
     * @param  <string> $key 当为空的时候,取全局密钥
     * @param  <int> $expiry 有效期,单位秒
     *
     * @return <string>
     */
    public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;
        $key = md5($key != '' ? $key : AUTHKEY);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = [];
        for ($i = 0;$i <= 255;$i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0;$i < 256;$i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0;$i < $string_length;$i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }

    }

    public static function ob_handle($s)
    {
        if (! empty($_SERVER['ob_stack'])) {
            $gzipon = array_pop($_SERVER['ob_stack']);
        } else {
            // throw new Exception('');
            $gzipon = 0;
        }
        $isfirst = count($_SERVER['ob_stack']) == 0;
        if ($gzipon && ! ini_get('zlib.output_compression') && function_exists('gzencode') && strpos(core::gpc('HTTP_ACCEPT_ENCODING', 'S'), 'gzip') !== false) {
            $s = gzencode($s, 5); // 0 - 9 级别, 9 最小','最耗费 CPU
            $isfirst && header("Content-Encoding: gzip");
            //$isfirst && header("Vary: Accept-Encoding");	// 下载的时候','IE 6 会直接输出脚本名','而不是文件名！非常诡异！估计是压缩标志混乱。
            $isfirst && header("Content-Length: " . strlen($s));
        } else {
            // PHP 强制发送的 gzip 头
            if (ini_get('zlib.output_compression')) {
                $isfirst && header("Content-Encoding: gzip");
            } else {
                $isfirst && header("Content-Encoding: none");
                $isfirst && header("Content-Length: " . strlen($s));
            }
        }

        return $s;
    }

    public static function ob_start($gzip = true)
    {
        ! isset($_SERVER['ob_stack']) && $_SERVER['ob_stack'] = [];
        array_push($_SERVER['ob_stack'], $gzip);
        ob_start(['core', 'ob_handle']);
    }

    public static function ob_end_clean()
    {
        ! empty($_SERVER['ob_stack']) && count($_SERVER['ob_stack']) > 0 && ob_end_clean();
    }

    public static function ob_clean()
    {
        ! empty($_SERVER['ob_stack']) && count($_SERVER['ob_stack']) > 0 && ob_clean();
    }

    /**
     * 生成随机密码
     *
     * @param integer $length Desired length (optional)
     * @param string  $flag   Output type (NUMERIC, ALPHANUMERIC, NO_NUMERIC)
     *
     * @return string Password
     */
    public static function passwdGen($length = 8, $flag = self::FLAG_NO_NUMERIC)
    {
        switch ($flag) {
            case self::FLAG_NUMERIC:
                $str = '0123456789';
                break;
            case self::FLAG_NO_NUMERIC:
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case self::FLAG_ALPHANUMERIC:
            default:
                $str = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
        }

        for ($i = 0, $passwd = '';$i < $length;$i++) {
            $passwd .= Tools::substr($str, mt_rand(0, Tools::strlen($str) - 1), 1);
        }

        return $passwd;
    }

    /**
     * 判断是否使用了HTTPS
     *
     * @return bool
     */
    public static function usingSecureMode()
    {
        if (isset($_SERVER['HTTPS'])) {
            return ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on');
        }
        if (isset($_SERVER['SSL'])) {
            return ($_SERVER['SSL'] == 1 || strtolower($_SERVER['SSL']) == 'on');
        }

        return false;
    }

    /**
     * 转换成小写字符','支持中文
     *
     * @param $str
     *
     * @return bool|string
     */
    public static function strtolower($str)
    {
        if (is_array($str)) {
            return false;
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($str, 'utf-8');
        }

        return strtolower($str);
    }

    /**
     * 转换成大写字符串
     *
     * @param $str
     *
     * @return bool|string
     */
    public static function strtoupper($str)
    {
        if (is_array($str)) {
            return false;
        }
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($str, 'utf-8');
        }

        return strtoupper($str);
    }

    //迅雷加密
    public static function ThunderEncode($url)
    {
        $thunderPrefix = "AA";
        $thunderPosix = "ZZ";
        $thunderTitle = "thunder://";
        $thunderUrl = $thunderTitle . base64_encode($thunderPrefix . $url . $thunderPosix);

        return $thunderUrl;
    }

    /**
     * 清除登录cookie
     */
    public function unset_login_cookie()
    {
        core::setcookie($this->cookie['cookie_pre'] . 'user_auth', '', 0, $this->cookie['cookie_path'], $this->cookie['cookie_domain']);
        unset($_COOKIE);
    }

    public static function randomcode($length = 6, $numeric = 0)
    {
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0;$i < $length;$i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }

        return $hash;
    }

    public static function xml_to_array($xml)
    {
        $arr = [];
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0;$i < $count;$i++) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = self::xml_to_array($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }

        return $arr;
    }
}