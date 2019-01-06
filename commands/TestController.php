<?php
namespace app\commands;
use yii\console\Controller;
class TestController extends Controller
{
    public function actionTest($test)
    {
        echo $test;
    }
    
    public function actionSendattachment()
    {
        $path = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'attachments';
        $copyPath = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'attachments_copy';
        //$endpoint = 'http://local.crm.com/attachment_api.php';
        $endpoint = 'http://kefu.yibainetwork.com/attachment_api.php';
        $dir = @opendir($path) or die('ERROR');
        $count = 0;
        $max = 100;
        while ($filename = readdir($dir))
        {
            if ($filename == '.' || $filename == '..') continue;
            $filePath = $path . DIRECTORY_SEPARATOR . $filename;
            if (!is_file($filePath)) continue;
            $data = file_get_contents($filePath, 1024);
            $dataBase64 = base64_encode($data);
            $sendData = ['file_name' => $filename, 'data' => $dataBase64];
            $curl = new \app\components\Curl;
            try {
                $sendData = json_encode($sendData);
                $result = $curl->post($endpoint, $sendData);
                if (empty($result)) continue;
                $result = json_decode($result);
                var_dump($result);
                
                if ($result->ack)
                {
                    if (!is_dir($copyPath))
                        mkdir($copyPath, 777);
                    $copyFilePath = $copyPath . DIRECTORY_SEPARATOR . $filename;
                    var_dump($copyFilePath);
                    if (copy($filePath, $copyFilePath ))
                        unlink($filePath);
                }
            }
            catch (\Exception $e)
            {
                
            }
            exit;
        }
        exit('DONE');
    }
}