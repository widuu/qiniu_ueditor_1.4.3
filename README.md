qiniu_ueditor_1.4.3
===================

>UPDATE

 - 添加了单图上传功能
 - 可以自己设置文件名保存名称，date为unix时间戳，留空为文件名保存
 - 支持截图上传功能
 - 修复了以前的小BUG
 - 图片水印支持

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

###水印

`./php/conf.php`

	//开启水印,不开启为false
	$USEWATER = true;
    //水印图片的七牛地址
	$WATERIMAGEURL = "http://gitwiduu.u.qiniudn.com/ueditor-bg.png"; //七牛上的图片地址
	//水印透明度
	$DISSOLVE = 50;
	//水印位置
	$GRAVITY = "SouthEast";
	//边距横向位置
	$DX  = 10;
	//边距纵向位置
	$DY  = 10;
    
    //水印具体位置分布如下

	NorthWest     |     North      |     NorthEast
	              |                |    
	              |                |    
	--------------+----------------+--------------
	              |                |    
	West          |     Center     |          East 
	              |                |    
	--------------+----------------+--------------
	              |                |    
	              |                |    
	SouthWest     |     South      |     SouthEast


然后就可以了!

###上传演示

![./upload.png](https://coding.net/u/widuu/p/qiniu_ueditor_1.4.3/git/raw/master/upload.png)

###图片在线管理

![](https://coding.net/u/widuu/p/qiniu_ueditor_1.4.3/git/raw/master/manage.png)

###博客支持技术支持http://www.widuu.com