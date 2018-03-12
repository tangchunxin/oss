<?php
/**
 * @ file Common.php 包含常用的方法
 * @ 引入Config类
 * @ 引入autoload类
 * @ 引入客户端的核心类
 */

use OSS\OssClient;
use OSS\Core\OssException;   //引入核心类

require_once __DIR__.DIRECTORY_SEPARATOR.'Config.php';
//var_dump(__DIR__.DIRECTORY_SEPARATOR.'Config.php');exit();

class Common{
    const endpoint = Config::OSS_ENDPOINT;
    const accessKeyId = Config::OSS_ACCESS_ID;
    const accessKeySecret = Config::OSS_ACCESS_KEY;
    const bucket = Config::OSS_TEST_BUCKET;

    /**
     * @ input $config params
     * @ return $ossClient
     */
    public static function getOssClient(){
        try{
            $ossClient = new OssClient(self::accessKeyId, self::accessKeySecret, self::endpoint, false);
        }catch (OssException $e){
            printf(__FUNCTION__.'creating ssclient');
            printf($e->getMessage()."\n");
            return null;
        }
        return $ossClient;
    }

    /**
     * @return string bucketname
     * @ self::getBucketName
     * @ Common::getBucketName
     */
    public static function getBucketName(){
        return self::bucket;
    }

    /**
     * @ func make BKT
     * @ input null
     * @ output
     */
    public static function createBucket(){
        $ossClient = self::getOssClient();
        if (is_null($ossClient)) exit(1);
        $bucket = self::getBucketName();
        $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ;
        try{
            $ossClient->createBucket($bucket,$acl);
        }catch (OssException $e){
            $message = $e->getMessage();
            if(\OSS\Core\startsWith($message, 'http status: 403')){
                echo "Please Check your AccessKeyId and AccessKeySecret" . "\n";
                exit(0);
            }elseif(strpos($message, "BucketAlreadyExists") !== false){
                echo "Bucket already exists. Please check whether the bucket belongs to you, or it was visited with correct endpoint. " . "\n";
                exit(0);
            }
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }

    /**
     * @ param $message not class not array
     * @ return type string
     */
    public static function println($message){
        if(is_array($message) || is_object($message)){
            echo 'can not be array or object';
        }
        if(!empty($message)){
            echo strval($message);
        }
    }
}

//make a BKT or not