## RPUP

A simple online drive with single page.

一个只有单个页面的简单网盘。

## 运行环境

可运行于 SAE 和普通的 PHP 虚拟主机，前者会使用Storage(默认Domian`rpup`)来储存，后者默认储存于 `files` 文件夹。  
你需要配置 Web 服务器重写所有请求到 `index.php`, 上面已经包括了常见 Web 服务器的重写规则。
