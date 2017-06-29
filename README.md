```
==========version==========
1.0.0
===========================


==========目录结构==========
public
  |- index.php //入口文件
  |- .htaccess //重写规则    
  |+ css
  |+ img
  |+ js
conf
  |- application.ini //配置文件   
application
  |+ controllers
     |- Index.php //默认控制器
  |+ views    
     |+ index   //控制器
        |- index.phtml //默认视图
  |+ modules //其他模块
  |+ library //本地类库
  |+ models  //model目录
  |+ plugins //插件目录
===========================

=================Nginx配置(路由)==============
官方文档存在错误
if (!-e $request_filename) {
    rewrite ^/(.*)  /index.php?$1 last;
}
============================================
```