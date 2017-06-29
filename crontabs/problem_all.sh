#!/bin/bash
#问题bash脚本,每隔1小时杀掉进程，重新启动
#author liujianghuai time:2016/01/14
starttime=`date '+%Y-%m-%d %H:%M:%S'`
echo 'start time:'$starttime>>/Data/webapps/userapi/shell/problemservice.log
######problem.php#######
problemnum=`ps aux | grep '/Data/webapps/userapi/shell/problem.php' | wc -l`
if [ "$problemnum" -lt "2" ]; then
 echo 'no problem process exist'>>/Data/webapps/userapi/shell/problemservice.log
else
  ps aux|grep '/Data/webapps/userapi/shell/problem.php'|awk '{print $2}'|while read pid
    do
      kill -9 $pid
    done
fi

echo 'restart problem......'>>/Data/webapps/userapi/shell/problemservice.log

#重新执行脚本
unset problemnum
nohup /Data/apps/php/bin/php /Data/webapps/userapi/shell/problem.php &
problemnum=`ps aux | grep '/Data/webapps/userapi/shell/problem.php' | wc -l`
echo '获取到的problem.php进程数：'$problemnum>>/Data/webapps/userapi/shell/problemservice.log
if [ "$problemnum" -eq "2" ]; then
 ps aux|grep '/Data/webapps/userapi/shell/problem.php'|awk '{print $2}'|while read pid
  do
    echo 'restart problem.php succ,进程id号：'$pid>>/Data/webapps/userapi/shell/problemservice.log
    break
  done
fi
unset problemnum

######problem_firstreplytime.php#######
problem_firstreplytime_num=`ps aux | grep '/Data/webapps/userapi/shell/problem_firstreplytime.php' | wc -l`
if [ "$problem_firstreplytime_num" -lt "2" ]; then
 echo 'no problem_firstreplytime process exist'>>/Data/webapps/userapi/shell/problemservice.log
else
  ps aux|grep '/Data/webapps/userapi/shell/problem_firstreplytime.php'|awk '{print $2}'|while read pid
    do
      kill -9 $pid
    done
fi

echo 'restart problem_firstreplytime......'>>/Data/webapps/userapi/shell/problemservice.log

#重新执行脚本
unset problem_firstreplytime_num
nohup /Data/apps/php/bin/php /Data/webapps/userapi/shell/problem_firstreplytime.php &
problem_firstreplytime_num=`ps aux | grep '/Data/webapps/userapi/shell/problem_firstreplytime.php' | wc -l`
echo '获取到的problem_firstreplytime.php进程数：'$problem_firstreplytime_num>>/Data/webapps/userapi/shell/problemservice.log
if [ "$problem_firstreplytime_num" -eq "2" ]; then
 ps aux|grep '/Data/webapps/userapi/shell/problem_firstreplytime.php'|awk '{print $2}'|while read pid
  do
    echo 'restart problem_firstreplytime.php succ,进程id号：'$pid>>/Data/webapps/userapi/shell/problemservice.log
    break
  done
fi
unset problem_firstreplytime_num

######problem_issimple.php#######
problem_issimple_num=`ps aux | grep '/Data/webapps/userapi/shell/problem_issimple.php' | wc -l`
if [ "$problem_issimple_num" -lt "2" ]; then
 echo 'no problem_issimple process exist'>>/Data/webapps/userapi/shell/problemservice.log
else
  ps aux|grep '/Data/webapps/userapi/shell/problem_issimple.php'|awk '{print $2}'|while read pid
    do
      kill -9 $pid
    done
fi

echo 'restart problem_issimple......'>>/Data/webapps/userapi/shell/problemservice.log

#重新执行脚本
unset problem_issimple_num
nohup /Data/apps/php/bin/php /Data/webapps/userapi/shell/problem_issimple.php &
problem_issimple_num=`ps aux | grep '/Data/webapps/userapi/shell/problem_issimple.php' | wc -l`
echo '获取到的problem_issimple.php进程数：'$problem_issimple_num>>/Data/webapps/userapi/shell/problemservice.log
if [ "$problem_issimple_num" -eq "2" ]; then
 ps aux|grep '/Data/webapps/userapi/shell/problem_issimple.php'|awk '{print $2}'|while read pid
  do
    echo 'restart problem_issimple.php succ,进程id号：'$pid>>/Data/webapps/userapi/shell/problemservice.log
    break
  done
fi
unset problem_issimple_num

echo '--------------------------------------------------------------------'>>/Data/webapps/userapi/shell/problemservice.log
echo -e "\n">>/Data/webapps/userapi/shell/problemservice.log
exit
