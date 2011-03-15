== 安装 == 

== 配置 == 

== hack php 文件 == 

打开 include/newthread.inc.php
翻到文件尾部,找到
<pre>
showmessage('post_newthread_succeed', "viewthread.php?tid=$tid&extra=$extra");
</pre>
在这一行之前加入下面的代码
<pre>
//start ezengage hack
if(function_exists('eze_trigger')){
    eze_trigger('newthread');
}   
//end ezengage hack
</pre>

打开 include/newreply.inc.php
翻到文件尾部,找到
<pre>
showmessage($replymessage, "viewthread.php?tid=$tid&pid=$pid&page=$page&extra=$extra#pid$pid");
</pre>
在这一行之前加入下面的代码
<pre>
//start ezengage hack
if(function_exists('eze_trigger')){
    eze_trigger('newreply');
}   
//end ezengage hack
</pre>

== hack theme 文件 (可选) ==
ezEngage 插件在默认情况下会利用discuz 的页面嵌入功能给页面加入
通过第三方帐号登录所需要的一些元素。
但是如果你的主题是经过修改的，或者你觉得默认的样式不好看。你可以在配置中关闭这个功能。
然后手工修改模版。

常用的代码片段

到使用第三方帐号登录页面的链接
<pre>
<a onlick="showWindow('eze-login', this.href);return false;" href="plugin.php?id=ezengage:login">使用第三方帐号登录</a>
</pre>

发表帖子时同步到新浪微博，腾讯微博等的勾选框:
<pre>
{eval eze_sync_checkbox_output($discuz_uid); }
<pre>
