<?php
//获取请求过来的附件数据
$attachData = file_get_contents('php://input');
$attachData = json_decode($attachData, true, JSON_BIGINT_AS_STRING);
//附件名
$fileName = $attachData['file_name'];
//附件数据
$fileData = base64_decode($attachData['data']);
//保存路径
$path = '/mnt/data/www/crm/web/attachments/';
//判断路径是否存在
if (!file_exists($path)) {
    @mkdir($path, 0777, true);
    @chmod($path, 0777);
}
//保存附件
$ack = file_put_contents($path . $fileName, $fileData);
if ($ack !== false) {
    $result = ['ack' => true];
} else {
    $result = ['ack' => false];
}
die(json_encode($result));
?>