<?php
/**
 * 用户提问的问题 48小时自动关闭
 * Created by PhpStorm.
 * User: oofl@163.com
 * Date: 14-8-25
 * Time: 下午2:00
 */

require 'conf.php';
define('DEBUG',true);
define('SSDBPREFIX', 'ybtest:im:');
echo 123123123;
return;
while (true) {
    $dateline = time();
    //48小时前
    $dateline_delay_48h = $dateline - 172800;
    //医生回复超过48小时未结束问题
    $data = $db->select('problem', ['problem_id', 'uid', 'content',  'doctoruid', 'isassign', 'firstover_time'], ['AND' => ['isover' => 0, 'status' => [1,2], 'firstreply_time[<>]' => [1, $dateline_delay_48h]], 'ORDER' => 'problem_id ASC', 'LIMIT' => [0, 1000]]);
    if (!empty($data)) {
        foreach ($data as $v) {
            if (!$v['firstover_time']) { //从来未结束过
                $db->update('problem', ['isover' => 1, 'firstover_time' => $dateline], ['problem_id' => $v['problem_id']]);
                echo '医生主动发送的problem_id is' . $v['problem_id'] . '的问题关闭成功' . PHP_EOL;
                //医生回答问题数
                $db->update('doctor_user_count', ['answers[+]' => 1], ['doctoruid' => $v['doctoruid']]);
            } else {
                $db->update('problem', ['isover' => 1], ['problem_id' => $v['problem_id']]);
            }
            //存储该问题已被关闭
            $ssdb->set('consult:problem:problem_id:'.$v['problem_id'].':isover', 1);
            $im_redis->SREM('doctor.type.id:2'.$v['doctoruid'].':record.wait.reply.question:id:set',$v['problem_id']);

            //新增逻辑关闭问题后 专职医生缓存列表处理 by cjw 2016-4-6
            over_from_fulldoctor_list($im_redis,(1==$v['isassign']) ? 1 : 2,$v['doctoruid'],$v['problem_id'],$dateline_delay_48h);

            //关闭问题通知患者 和专职医生
            $doctor_info=$db->get('doctor_user',['is_community_doctor'],['doctoruid'=>$v['doctoruid']]);
            if(0===(int)$doctor_info['is_community_doctor']){
            ProblemCloseNotice::pushProblemClose($im_redis,$v['uid'], 1, $v['problem_id'], $v['isassign'] ? 1 : 2 );//通知患者
            ProblemCloseNotice::pushProblemClose($im_redis,$v['doctoruid'], 2, $v['problem_id'], $v['isassign'] ? 1 : 2);//通知医生
            }

        }
    }

    //处理已抢答未回复的问题
    $dateline_delay_5m = $dateline - 300;
    $data = $db->select('problem', ['problem_id', 'uid', 'content', 'doctoruid', 'doctor_level', 'firstreply_time', 'race_time'], ['AND' => ['firstreply_time' => 0, 'isassign' => 0, 'status' => [1,2], 'race_time[<>]' => [1, $dateline_delay_5m],'isover'=>0], 'ORDER' => 'problem_id ASC', 'LIMIT' => [0, 1000]]);
    echo "处理开放待回复数量:".count($data).PHP_EOL;
    if (!empty($data)) {
        foreach ($data as $v) {
            echo "待回复".$v['problem_id']."时间".date('Y-m-d',$v['race_time']).PHP_EOL;
            if ($dateline - $v['race_time'] < 600) {
                //5分钟到10分钟之间
                $message = '您还有一个问题没回复，15分钟内不回复系统将转给其他医生，请尽快作答';
                if ($ssdb->hexists(SSDBPREFIX . 'systerm:doctoruid_' . $v['doctoruid'], $v['problem_id']) === false) {
                    //存放ssdb
                    $ssdb->hset(SSDBPREFIX . 'systerm:doctoruid_' . $v['doctoruid'], $v['problem_id'], 1);
                    //入库通知表
                    $db->insert('pm_notification', ['people'=>2, 'uid' => $v['doctoruid'], 'content' => $message, 'dateline' => $dateline]);
                }
            }
            if ($dateline - $v['race_time'] > 900) {//900
                $im_redis->SREM('doctor.type.id:2'.$v['doctoruid'].':record.wait.reply.question:id:set',$v['problem_id']);
                ///超过十五分钟不回答的自动转诊
                $db->update('problem', ['doctoruid' => 0, 'doctorname'=> '', 'firstreply_time' => 0, 'race_time' => 0,  'effect_time' => $dateline, 'referral_doctoruid' => $v['doctoruid'] ,'doctor_level'=>0,'isreferral'=>1], ['problem_id' => $v['problem_id']]);
                //处理如果问题专职医生抢答  从专职医生指定列表和全部列表中删除 by cjw 2016-4-6
                $doctor_info=$db->get('doctor_user',['is_community_doctor'],['doctoruid'=>$v['doctoruid']]);
                echo "待回复问题转诊了".$v['problem_id'].PHP_EOL;
                if(0===(int)$doctor_info['is_community_doctor']){
                    delete_from_fulldoctor_list($im_redis,$v['doctoruid'],$v['problem_id'],$v['race_time']);

                    ProblemCloseNotice::pushProblemClose($im_redis,$v['uid'], 1, $v['problem_id'],5 );//通知患者
                    ProblemCloseNotice::pushProblemClose($im_redis,$v['doctoruid'], 2, $v['problem_id'],5);//通知医生

                }

                //记录该医生延时未回答一次
                $db->insert('problem_delay_log', ['doctoruid' => $v['doctoruid'], 'problem_id' => $v['problem_id'], 'dateline' => $dateline]);
                $message = '问题："' . $v['content'] . '"在您抢答后15分钟内没有做出回答，记“延时未回答”一次，我们已将该问题移交给其他医生。';
                $platform = $redis->get('doctor_login_platform_' . $v['doctoruid']);
                //转诊记录
                $ssdb->set(SSDBPREFIX . 'problem:referral_' . $v['problem_id'] . '_' . $v['doctoruid'], 1);
                //入库通知表
                $db->insert('pm_notification', ['people'=>2, 'uid' => $v['doctoruid'], 'content' => $message, 'dateline' => $dateline]);
                //发通知
                sendMessage($platform, 2, $v['doctoruid'], $message);
                echo $message;
            }
        }
    }
    //处理指定问题24小时不回答自动转诊 加入判断是否是疑似问题判断 by cjw
    $dateline_delay_23h = $dateline - 82800;
    //$dateline_delay_23h = $dateline - 86400;
    $data = $db->select('problem', ['problem_id', 'uid', 'content','picture','doctoruid', 'doctorname',  'firstreply_time', 'effect_time'], ['AND' => ['type' => 1, 'firstreply_time' => 0, 'isassign' => 1, 'status' => [1,2], 'effect_time[<>]' => [1, $dateline_delay_23h],'isover'=>0], 'ORDER' => 'problem_id ASC', 'LIMIT' => [0, 50]]);

    //echo str_replace('"', '', $db->last_query()).PHP_EOL;
    $num = count($data);
    echo '指定问题剩余数量:'.$num.PHP_EOL;
    if (!empty($data)) {
        foreach($data as $v) {
            echo '问题id:'.$v['problem_id'].PHP_EOL;
            if ($dateline - $v['effect_time'] > 86400) {
                //判断问题字数是否小于20字小于20字转入疑似问题 by cjw 2016-4-14
                $issimple=0;
                $issimple=(''==$v['picture'])&&(mb_strlen($v['content'],'UTF-8') <= 20) ? 1 :0;
                ///超过24小时不回答的自动转诊
                $db->update('problem', ['doctoruid' => 0, 'doctorname'=> '', 'isassign' => 0, 'race_time' => 0, 'isreferral' => 1, 'effect_time' => $dateline, 'referral_doctoruid' => $v['doctoruid'],'issimple'=> $issimple,'doctor_level'=>0], ['problem_id' => $v['problem_id']]);
                //处理如果问题指定给专职医生  从专职医生指定列表和全部列表中删除 by cjw
                $doctor_info=$db->get('doctor_user',['is_community_doctor'],['doctoruid'=>$v['doctoruid']]);
                if(0===(int)$doctor_info['is_community_doctor']){
                    delete_from_fulldoctor_list($im_redis,$v['doctoruid'],$v['problem_id'],$v['effect_time']);
                    ProblemCloseNotice::pushProblemClose($im_redis,$v['uid'], 1, $v['problem_id'],6);//通知患者
                    ProblemCloseNotice::pushProblemClose($im_redis,$v['doctoruid'], 2, $v['problem_id'],6);//通知医生
                }
                $im_redis->SREM('doctor.type.id:2'.$v['doctoruid'].':record.wait.reply.question:id:set',$v['problem_id']);
                //转诊记录
                $ssdb->set(SSDBPREFIX . 'problem:referral_' . $v['problem_id'] . '_' . $v['doctoruid'], 1);

                //清空ssdb列表相关数据
                //删除该条咨询排序
                $del_status = $ssdb->zdel('consult_history:problem_id:orderby:lastreply:uid:' . $v['uid'] . 'doctoruid:' . $v['doctoruid'], $v['problem_id']);
                if ($del_status) {
                    //删除存放该咨询最后一条回复记录
                    //$ssdb->del('consult_history:lastreply:problem_id:' . $v['problem_id']);
                    $content = $ssdb->multi_hdel('consult_history:lastreply:problem_id:content' . $v['problem_id'], array('last_content','last_time'));
                    //读取用户对应医生最后一条记录
                    $uid_doctoruid_lastreply = json_decode($ssdb->hget('consult_history:lastreply:uid:' . $v['uid'], 'doctoruid:' . $v['problem_id']), true);
                    if ($uid_doctoruid_lastreply['count'] > 1) {
                        //删除的为最近回复一条并且只有一条则更新存放用户对应医生最后一条记录
                        if ($uid_doctoruid_lastreply['problem_id'] == $v['problem_id']) {
                            //删除后的最新咨询id
                            $new_problem_id = $ssdb->zRevRange('consult_history:problem_id:orderby:lastreply:uid:' . $v['uid'] . 'doctoruid:' . $v['doctoruid'], 0, 1);
                            //读取最新的咨询内容
                            //  $problem_id_lastreply = json_decode($ssdb->get('consult_history:lastreply:problem_id:' . $new_problem_id));
                            $newcontent = $ssdb->multi_hget('consult_history:lastreply:problem_id:content' .$new_problem_id, array('last_content','last_time'));
                            //更新医生最后聊天记录
                            // $uid_doctoruid_lastreply['last_content'] = $problem_id_lastreply->last_content;
                            //$uid_doctoruid_lastreply['last_time'] = $problem_id_lastreply->last_time;
                            $uid_doctoruid_lastreply['last_content'] = $newcontent['last_content'];
                            $uid_doctoruid_lastreply['last_time'] = $newcontent['last_time'];
                        }
                        //更新对该医生咨询次数
                        $uid_doctoruid_lastreply['count'] = $uid_doctoruid_lastreply['count'] - 1;
                        //重新存储
                        $ssdb->hset('consult_history:lastreply:uid:' . $v['uid'], 'doctoruid:' . $v['doctoruid'], json_encode($uid_doctoruid_lastreply));
                        //只有一条,直接将医生咨询记录全部删除
                    } else {
                        //删除用户对应医生最后一条记录
                        $ssdb->hdel('consult_history:lastreply:uid:' . $v['uid'], 'doctoruid:' . $v['doctoruid']);
                        //删除医生列表按最后回复排序
                        $ssdb->zdel('consult_history:doctor:orderby:lastreply:uid:' . $v['uid'], $v['doctoruid']);
                    }
                }

                $message = '您指定'.$v['doctorname'].'医生的问题:' .$v['problem_id'] .'--'. $v['content'] . '已被转诊，请耐心等待回复';
                 $userInfo = $db->get('user_status', ['platform'], ['uid' => $v['uid']]);
                 if (isset($userInfo['platform']) && $userInfo['platform']) {
                     //发通知
                     sendMessage($userInfo['platform'], 1, $v['uid'], $message);
                 }
                echo  $message . PHP_EOL;
            }
        }
    }

    unset($data);
    //休眠1分钟
    sleep(60);
}
//发通知
function sendMessage($platform, $type, $uid, $message)
{
    if (in_array($platform, array('ios', 'android'))) {

        $url = BASEURL . 'xinge/send/';
        $data = array(
            'followid' => $uid,
            'message' => $message,
            'type' => $type,
            'platform' => $platform
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); //5秒超时时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回内容不是输出
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
        curl_close($ch);
    }
}

//问题关闭通知
class ProblemCloseNotice{

    const USER_TYPE_ANHAO = 1;  // 安好用户
    const USER_TYPE_FULL_TIME_DOCTOR = 2;  // 全职医生
    const USER_TYPE_COMMUNITY_DOCTOR = 3;  // 社区医生

    const MODULE_ANHAO_USER_Q_A_PROBLEM_CLOSE = 'anhao.user:q.a:problem:close';          // 安好用户端: 问答里面的问题关闭推送模块
    const MODULE_FULLTIME_Q_A_PROBLEM_CLOSE = 'full.time.doctor:q.a:problem:close';           // 全职医生端: 问答里面的问题关闭推送模块
    const MODULE_COMMUNITY_DOCTOR_Q_A_PROBLEM_CLOSE = 'community.doctor:q.a:problem:close'; // 社区医生端: 问答里面的问题关闭推送模块


    /**
     * pushProblemClose 推送三端问题关闭通知
     *
     * @param int $user_id 推送接受用户的ID
     * @param int $user_type 推送接受的用户类型[1:安好用户,2:全职医生,3:社区医生]
     * @param int $question_id 问题ID
     * @param int $close_type 关闭类型
     *
     * @return bool|mixed
     * @author wangnan
     * @date 2016-04-18 17:28:47
     */
    static public function pushProblemClose($im_redis,$user_id, $user_type, $question_id, $close_type)
    {
        $user_id = (int)$user_id;
        $user_type = (int)$user_type;
        $question_id = (int)$question_id;
        $close_type = (int)$close_type;
        $close_types = [
            1 => 1, // 指定问题已有回答超时48小时自动关闭
            2 => 2, // 抢答问题已有回答超时48小时自动关闭
            3 => 3, // 问题大厅手动关闭操作
            4 => 4, // 指定问题待回复 超时24h转诊
            5 => 5, // 抢答问题待回复 超时15min转诊
            6 => 6, // 指定问题待回复手动关闭
            7 => 7, // 抢答问题待回复手动关闭
            8 => 8, // 指定问题已回复手动关闭
            9 => 9, // 抢答问题已回复手动关闭
        ];
        if (0 >= $user_id || 0 >= $question_id || ! in_array($user_type, [1, 2, 3]) || ! in_array($close_type, $close_types)) {
            return false;
        }
        $user_types = [
            1 => self::USER_TYPE_ANHAO,
            2 => self::USER_TYPE_FULL_TIME_DOCTOR,
            3 => self::USER_TYPE_COMMUNITY_DOCTOR
        ];
        $modules = [
            1 => self::MODULE_ANHAO_USER_Q_A_PROBLEM_CLOSE,
            2 => self::MODULE_FULLTIME_Q_A_PROBLEM_CLOSE,
            3 => self::MODULE_COMMUNITY_DOCTOR_Q_A_PROBLEM_CLOSE,
        ];
        $user_type     = $user_types[$user_type];
        $module        = $modules[$user_type];
        $is_page       = 0;
        $is_send_xinge = 0;
        $data = [
            'qid'        => $question_id,
            'close_type' => $close_type,
            'time'       => time(),
        ];

        return self::addModuleSocketData($im_redis,$user_id, $user_type, $module, $data, $is_page, $is_send_xinge);
    }


    /**
     * addModuleSocketData
     * 添加数据到推送列表 私有方法,没对数据进行验证,在调用处应验证
     *
     * @param int $user_id
     * @param int $user_type
     * @param string $module
     * @param array $data
     * @param int $is_page
     * @param int $page_sort 分页排序值
     * @param int $is_send_xinge
     * @param array $xinge_data ['detail' => '具体文案详情', 'no_detail' => '不显示详情时的文案']
     *
     * @return mixed
     * @author
     * @date   2016-03-02 13:39:39
     */
    static public function addModuleSocketData($redis,$user_id, $user_type, $module, $data, $is_page, $is_send_xinge, $xinge_data = [], $page_sort = 0)
    {
        // 插入的数据
        $module_global_id = $redis->incr("module:type:global.increment:string");
        $page_sort = empty($page_sort) ? $module_global_id : (int)$page_sort;
        $insert_data = json_encode([
            'id'         => $module_global_id,
            'module'     => $module,
            'to_user'    => $user_id,
            'to_type'    => $user_type,
            'send_xinge' => $is_send_xinge,
            'xinge_data' => $xinge_data,
            'is_page'    => $is_page,
            'page_sort'  => $page_sort,
            'add_time'   => time(),
            'data'       => $data,
        ], JSON_UNESCAPED_UNICODE);

        return $redis->rpush("module:type:xinge.send.data.process:list", $insert_data);
    }

}

/**
 * 删除全职医生列表中的问题
 * delete_from_fulldoctor_list
 * @param $redis
 * @param $doctoruid
 * @param $problem_id
 * @param $time
 * @author:cajianwei
 * @date 2016-4-5
 */
function delete_from_fulldoctor_list($redis,$doctoruid,$problem_id,$time){
    $zset_wait_redis_key="module:full.time.doctor:q.a:chat:list:wait.reply:page:user.type.id:2{$doctoruid}:zset";//全职医生待回复列表key
    $zset_full_redis_key="module:full.time.doctor:q.a:chat:list:full:page:user.type.id:2{$doctoruid}:zset";//全职医生临时列表
    $hash_dot_redis_key="module:full.time.doctor:q.a:chat:red.number.dot:description:user.type.id:2{$doctoruid}:hash";//小红点hash

    $hash_qid_timestamp="full.time.doctor:q.a:chat:qid.time:hash";//存储qid=>timestamp映射

    //看看hash中是不是有时间
    $hash_time=$redis->hGet($hash_qid_timestamp,$problem_id);
    //更改时间
    $time= empty($hash_time) ? $time : (int)$hash_time;

    //删除医生临时列表
    $fdata=zset_find_problem_recursive($redis,$zset_full_redis_key,$problem_id,$time);
    if($fdata) $redis->zRem($zset_full_redis_key,json_encode($fdata,JSON_UNESCAPED_UNICODE));

    //删除医生的待回复列表
    $wdata=zset_find_problem_recursive($redis,$zset_wait_redis_key,$problem_id,$time);
    if($wdata) $redis->zRem($zset_wait_redis_key,json_encode($wdata,JSON_UNESCAPED_UNICODE));

    //hash中待回复减 1
    if($fdata || $wdata ){
        $wait_reply_number=(int)$redis->hGet($hash_dot_redis_key,'wait_reply_number');
        if($wait_reply_number > 0) $redis->hIncrBy($hash_dot_redis_key,'wait_reply_number',-1);
    }

    return true;
}

/**
 * 结束问题处理全职医生指定列表或者开放列表缓存集合处理
 * over_from_fulldoctor_list
 * @param $redis redis句柄
 * @param $type  [1:指定, 2:开放抢答]
 * @param $doctoruid  医生id
 * @param $problem_id 问题id
 * @param $time  结束时间
 * @return bool
 * @author:cajianwei
 */
function over_from_fulldoctor_list($redis,$type,$doctoruid,$problem_id,$time){
    if(empty($problem_id)) return false;
    $zset_full_redis_key="module:full.time.doctor:q.a:chat:list:full:page:user.type.id:2{$doctoruid}:zset";//医生临时列表
    $zset_full_assign_redis_key="module:full.time.doctor:q.a:chat:list:appoint.already.answered:page:user.type.id:2{$doctoruid}:zset";//指定已答列表
    $zset_full_open_redis_key="module:full.time.doctor:q.a:chat:list:open.already.answered:page:user.type.id:2{$doctoruid}:zset";//开放已答列表
    $hash_dot_redis_key="module:full.time.doctor:q.a:chat:red.number.dot:description:user.type.id:2{$doctoruid}:hash";//小红点hash
    $hash_qid_timestamp="full.time.doctor:q.a:chat:qid.time:hash";//存储qid=>timestamp映射
    //推送module列表
    $module_list=[
        'full_list'=>'full.time.doctor:q.a:chat:list:full',//临时列表
        'wait_list'=>'full.time.doctor:q.a:chat:list:wait.reply',//待回复
        'already_list'=>'full.time.doctor:q.a:chat:list:appoint.already.answered',//指定已答
        'open_list'=>'full.time.doctor:q.a:chat:list:open.already.answered',//开放已答
    ];

    //获取时间时间分数
    $get_time=$redis->hget($hash_qid_timestamp,$problem_id);
    $time= empty($get_time) ? $time : $get_time;//替换时间
    $red_dot_sign=false;//小红点处理标示
    //处理的键值 判断是指定问题还是开放问题
    $handle_key = (1==$type) ? $zset_full_assign_redis_key : $zset_full_open_redis_key;
    //获取记录
    $adata=zset_find_problem_recursive($redis,$handle_key,$problem_id,$time);
    if($adata){
        //获取分数
        $score=$redis->zScore($handle_key,json_encode($adata,JSON_UNESCAPED_UNICODE));
        //删除这个记录
        $redis->zDelete($handle_key,json_encode($adata,JSON_UNESCAPED_UNICODE));
        //删除临时集合中元素
        $redis->zDelete($zset_full_redis_key,json_encode($adata,JSON_UNESCAPED_UNICODE));
        //修改操作
        $send_data=$adata;
        //判断是否有回复 处理小红点
        if(1==$send_data['red_dot']){
            $red_dot_sign=true;
            $send_data['red_dot']=0;
        }
        //处理看是那个集合
        $module=(1==$type) ? $module_list['already_list'] :$module_list['open_list'];
        //重新推送指定列表或者开放列表
        addModuleSocketData($redis,$doctoruid,2,$module,$score,$send_data);
        //临时列表重新推送
        addModuleSocketData($redis,$doctoruid,2,$module_list['full_list'],$score,$send_data);
        //如果小红点不需要处理直接返回
        if(!$red_dot_sign) return true;

        //处理小红点问题 指定还是 开放
        $dot_hash_sub_key=(1==$type) ? 'appoint_already_answered_number' :'open_already_answered_number';
        $num_question=(int)$redis->hget($hash_dot_redis_key,$dot_hash_sub_key);
        //小红点数量减1
        if($num_question > 0)  $redis->hIncrBy($hash_dot_redis_key,$dot_hash_sub_key,-1);
    }
    return true;

}


/**
 * 根据集合分数来查找key
 * zset_find_problem_recursive
 * @param $redis
 * @param $key
 * @param $problem_id
 * @param $time
 * @author:cajianwei
 * @date 2016-4-5
 */
function zset_find_problem_recursive($redis,$key,$problem_id,$time){
    if(empty($problem_id)) return false;
    $time_sign=600;//寻找时间间隔10min内
    $list=$redis->ZRANGEBYSCORE($key,$time-$time_sign,$time+$time_sign);
    $result=[];//返回结果
    foreach($list as $lv){
        $arr=json_decode($lv,true);
        if($problem_id == $arr['q_id']){
            //找到问题id就直接返回
            $result=$arr;
            return $result;
        }
    }

    //如果没有找到就继续查找 查找当前右侧的
    $rlist=$redis->ZRANGEBYSCORE($key,$time+$time_sign,time());
    foreach($rlist as $lv){
        $arr=json_decode($lv,true);
        if($problem_id == $arr['q_id']){
            //找到问题id就直接返回
            $result=$arr;
            return $result;
        }
    }

    //如果当前医生右侧还没有找到，就去左侧查找
    $llist=$redis->ZRANGEBYSCORE($key,0,$time-$time_sign);

    foreach($llist as $lv){
        $arr=json_decode($lv,true);
        if($problem_id == $arr['q_id']){
            //找到问题id就直接返回
            $result=$arr;
            return $result;
        }
    }

    return false;
}



/**
 * addModuleSocketData
 * 添加数据到推送列表 私有方法,没对数据进行验证,在调用处应验证
 * @param int $user_id  doctoruid
 * @param int $user_type 2
 * @param string $module
 * @param array $data
 * @return mixed
 * @author
 * @date   2016-03-02 13:39:39
 */
function addModuleSocketData($redis,$user_id, $user_type, $module,$time,$data)
{
    // 插入的数据
    $module_global_id = $redis->incr("module:type:global.increment:string");
    $insert_data = json_encode([
        'id'         => $module_global_id,
        'module'     => $module,
        'to_user'    => $user_id,
        'to_type'    => $user_type,
        'send_xinge' => 0,
        'xinge_data' => [],
        'is_page'    => 1,
        'page_sort'  => $time,
        'add_time'   => time(),
        'data'       => $data,
    ], JSON_UNESCAPED_UNICODE);

    return $redis->rpush("module:type:xinge.send.data.process:list", $insert_data);
}


/***************************
$data = [
    'q_id'         => $q_id,
    'q_answered'   => 0, // 0:从未回复过, 1:有过回复
    'q_type'       => $q_type, // 1:指定, 2:开放抢答
    'q_cont'       => $q_cont,
    'q_chat'       => '待回复',
    'q_chat_state' => 0, // 0:只有提问,1:患者发送消息,2:医生发送消息
    'time'         => $now_timestamp,
    'age'          => (int)$age,
    'sex'          => (int)$sex,
    'avatar'       => (string)$avatar,
    'real_name'    => (string)$real_name,
    'nickname'     => (string)$nickname,
    'phone'        => (string)$phone,
    'red_dot'      => '1',
]

*******************************************/



