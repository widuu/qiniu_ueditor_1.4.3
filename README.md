qiniu_ueditor_1.4.3
===================

>UPDATE

 - 添加了单图上传功能
 - 可以自己设置文件名保存名称，date为unix时间戳，留空为文件名保存
 - 支持截图上传功能
 - 修复了以前的小BUG

>UEditor1.4.3版本-直接上传视频、附件、图片到七牛云存储，并且支持图片在线管理功能

###配置

`./php/conf.php`

	//配置$QINIU_ACCESS_KEY和$QINIU_SECRET_KEY 为你自己的key
	$QINIU_ACCESS_KEY	= 'your akey';
	$QINIU_SECRET_KEY	= 'your skey';
	
	//配置bucket为你的bucket
	$BUCKET = "your bucket";
	
	//配置你的域名访问地址
	$HOST  = "your qiniu domain";

	//上传超时时间
	$TIMEOUT = "3600";
	
	//保存规则
	$SAVETYPE = "date"; //现在支持unix时间戳，unix时间戳写date,如果文件名上传就留空

`./php/config.json`

	"imageSaveType"  : "date", 默认date为unix时间戳，留空则文件名方式上传


然后就可以了!

###上传演示

![./upload.png](./upload.png)

###图片在线管理

![](./manage.png)

###博客支持技术支持http://www.widuu.com