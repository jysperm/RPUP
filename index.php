<?php
/*
精英王子, m@jybox.net
http://jyprince.me/
2013.7.28

https://github.com/jybox/RPUP
GPLv3

同时支持SAE和普通PHP环境，最低要求PHP5.2.
请重写所有请求至该文件
*/

define("ROOT", dirname(__FILE__));

$SaeStorageDomain = "rpup";
$NonSaeFilesDir = ROOT . "/files";

ob_start();

if(class_exists("SaeStorage"))
{
    $ss = new SaeStorage();
    $serverInfo["diskUsed"] = $ss->getDomainCapacity($SaeStorageDomain);
    $serverInfo["fileNum"] = $ss->getFilesNum($SaeStorageDomain);
}
else
{
    $serverInfo["diskFree"] = disk_free_space(ROOT);
}

$htmlFileNotExists = <<< HTML
<section>
  <div class="alert alert-block alert-error fade in">
    <header><h3 class="alert-heading">没有找到这个文件</h3></header>
    <p>
      可能是：
    </p>
    <ul style="list-style-type:none; margin-top: 12px;">
      <li><i class="icon-tags"></i> 你输入的URL地址有误</li>
      <li><i class="icon-tags"></i> 这个文件不符合该网盘相关规定而被删除</li>
      <li><i class="icon-tags"></i> 这个文件体积过大而且较长时间没有被下载</li>
    </ul>
  </div>
</section>
HTML;

$htmlNeedMoreBit = <<< HTML
<section>
  <div class="alert alert-block alert-error fade in">
    <header><h3 class="alert-heading">该地址对应多个文件</h3></header>
    <p>
      你可能需要再多输入几位散列值
    </p>
  </div>
</section>
HTML;

$htmlFileTableHead = <<< HTML
<section class='box well'>
  <header><h2>已上传文件列表</h2></header>
  <table class="table table-striped table-bordered table-condensed">
HTML;

$htmlFileTableFooter = <<< HTML
  </table>
</section>
HTML;

switch($_SERVER["REQUEST_METHOD"])
{
  case "GET":
      preg_match("%^/([a-z0-9]{0,32})/?$%", rawurldecode($_SERVER["REQUEST_URI"]), $r);

      if(!empty($r[1]))
      {
          $fileMD5 = $r[1];

          if(SAE_ACCESSKEY)
              $files = $ss->getList($SaeStorageDomain, $fileMD5);
          else
              $files = glob("{$NonSaeFilesDir}/{$fileMD5}*");

          if(!$files)
          {
              echo $htmlFileNotExists;
          }
          else if(count($files) > 1)
          {
              echo $htmlNeedMoreBit;
          }
          else
          {
              $file = $files[0];
              if(SAE_ACCESSKEY)
                  $fileName = substr($file, 32);
              else
                  $fileName = substr($file, 32 + strlen($NonSaeFilesDir) + 1);
              $fileName = urlencode($fileName);

              header("Content-Type: application/force-download");
              header("Content-Disposition: attachment; filename={$fileName}");

              if(SAE_ACCESSKEY)
                  echo $ss->read($SaeStorageDomain, $file);
              else
                  readfile($file);

              exit();
          }

      }
  break;
  case "POST":
      if(isset($_FILES["files"]))
      {
          echo $htmlFileTableHead;
          foreach($_FILES["files"]["error"] as $key => $error)
          {
              if($error == UPLOAD_ERR_OK)
              {
                  $tmpName = $_FILES["files"]["tmp_name"][$key];
                  $fileName = $_FILES["files"]["name"][$key];
                  $fileMD5 = md5_file($_FILES["files"]["tmp_name"][$key]);
                  $objName = $fileMD5 . $fileName;

                  $fileSize = ceil($_FILES["files"]["size"][$key] / 1024) . "KB";
                  $fileType = $_FILES["files"]["type"][$key];
                  $fileURL = "http://{$_SERVER["SERVER_NAME"]}/{$fileMD5}";
                  $fileURL = "<a href='{$fileURL}' target='_blank'>{$fileURL}</a>";

                  if(SAE_ACCESSKEY)
                      $files = $ss->getList($SaeStorageDomain, $fileMD5);
                  else
                      $files = glob("{$NonSaeFilesDir}/{$fileMD5}*");

                  if(!$files)
                      if(SAE_ACCESSKEY)
                          file_put_contents("saestor://{$SaeStorageDomain}/{$objName}", file_get_contents($tmpName));
                      else
                          move_uploaded_file($tmpName, "{$NonSaeFilesDir}/{$objName}");
                  else
                      $fileURL .= " (已有其他人上传)";

                  echo <<< HTML
                  <tr>
                    <td>{$fileName}</td>
                    <td>{$fileType}</td>
                    <td>{$fileSize}</td>
                    <td>{$fileURL}</td>
                  </tr>
HTML;
              }
          }
          echo $htmlFileTableFooter;
      }
  break;
}

$htmlOut = ob_get_clean();

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>RP UP</title>
    <link href="http://lib.sinaapp.com/js/bootstrap/latest/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <link href="http://lib.sinaapp.com/js/bootstrap/latest/css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css"/>
  </head>
  <body style='font-family: "WenQuanYi Micro Hei", "WenQuanYi Zen Hei", "Microsoft YaHei", arial, sans-serif; font-size: 16px;'>
    <div class="container">
      <ul class="breadcrumb" style="margin-top: 12px;">
        <li class="active"><i class="icon-comment"></i> http://<?= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]; ?></li>
      </ul>
      <div class="row">
        <div class="span12">
          <header class="box well">
            <header><h2>RP UP</h2></header>
            <hr />
            <ul style="list-style-type: none;">
              <li><i class="icon-ok"></i> 不必注册帐号，不必登录</li>
              <li><i class="icon-ok"></i> 直链下载，无广告，无需等待</li>
              <li><i class="icon-ok"></i> 清爽无图界面，无须Flash，同样适合手机访问</li>
            </ul>
          </header>
          <?php echo $htmlOut;?>
          <section class='box well'>
            <form id="fileForm" method="post" enctype="multipart/form-data">
              <input type="file" name="files[]" /><br />
            </form>
            <hr />
            <button id='addFile' class='btn btn-info'><i class="icon-plus icon-white"></i> 添加新的上传框</button>
            <button id='start' class='btn btn-success'><i class="icon-play icon-white"></i> 开始上传</button>
          </section>
          <section class='box well'>
            <ul style="list-style-type:none;">
              <li>作者 <a href="http://jyprince.me/" target="_brank">精英王子</a>(<i class="icon-envelope"></i>m@jybox.net)</li>
              <li>源代码托管于 <a href="https://github.com/jybox/RPUP" target="_brank">Github/RPUP</a> GPLv3</li>
              <?php if(SAE_ACCESSKEY):?>
                <li><a href="http://sae.sina.com.cn" target="_blank"><img src="http://static.sae.sina.com.cn/image/poweredby/117X12px.gif" title="Powered by Sina App Engine"></a></li>
              <?php endif;?>
              <li><i class="icon-random"></i> <script src="//static2.jybox.net/my-website/analyzer.js" type="text/javascript"></script></li>
              <?php if(SAE_ACCESSKEY):?>
                <li>已用：<?php echo ceil($serverInfo["diskUsed"] / 1024 / 1024);?>MiB, 共<?php echo $serverInfo["fileNum"];?>个文件</li>
              <?php else:?>
                <li>可用空间：<?php echo ceil($serverInfo["diskFree"] / 1024 / 1024);?> MiB</li>
              <?php endif;?>
            </ul>
          </section>
        </div>
      </div>
    </div>
    <script type='text/javascript' src='http://lib.sinaapp.com/js/jquery/1.9.1/jquery-1.9.1.min.js'></script>
    <script type='text/javascript' src='http://lib.sinaapp.com/js/bootstrap/latest/js/bootstrap.min.js'></script>
    <!--[if lte IE 8]>
      <script type='text/javascript' src='//static2.jybox.net/tools/kill-ie6.js'></script>
    <![endif]-->
    <script type="text/javascript">
      $("#addFile").click(function(){
        $("#fileForm").append('<input type="file" name="files[]" /><br />');
      });
      $("#start").click(function(){
        $("#fileForm").submit();
      });
    </script>
  </body>
</html>