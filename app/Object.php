<?php

/**
 * @ file Object.php
 * @ func object增删改查复制批量上传等
 */
require_once __DIR__ . '/Common.php';
use OSS\OssClient;
use OSS\Core\OssException;

$bucket = Common::getBucketName();
$ossClient = Common::getOssClient();

if(is_null($ossClient)) exit(1);

//uploadDir('/data/www/html/c_in/images','caridhash1/caridhash2');
//listObjects($ossClient,$bucket,'samples/codes/');
//listObjects($ossClient,$bucket,'caridhash1/caridhash2/',null);
//exit();
/**
 * 按照目录上传文件
 *
 * @param OssClient $ossClient OssClient
 * @param string $bucket 存储空间名称
 * @param string $localDirectory 本地目录
 * @param string $prefix 上传到服务器的目录
 * @return string OK
 */
function uploadDir($localDirectory,$prefix)
{
    global $ossClient;
    global $bucket;
    try {
        $retArray = $ossClient->uploadDir($bucket, $prefix, $localDirectory);
    } catch (OssException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        echo $bucket.'||'.$prefix.'||'.$localDirectory;
        return;
    }
    //unset($ossClient);
    printf(__FUNCTION__ .date('Y-m-d H:i:s'). ": completeMultipartUpload OK||".$localDirectory.'||'.$prefix."||".var_export($retArray)."\n");
}



//$dir='lyx/lyx1';
//createObjectDir($ossClient,$bucket,$dir);
//listObjects($ossClient,$bucket,'lyx/lyx1');
//$res = $ossClient->getPrefixList();
//var_dump($res);exit();
/**
 * 创建虚拟目录
 *
 * @param OssClient $ossClient OssClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function createObjectDir($ossClient, $bucket,$dir)
{
    try {
        $ossClient->createObjectDir($bucket,$dir);
    } catch (OssException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}
//这个调用出现问题,应该是我的本地有问题， 返回值是超时
//listAllObjects($ossClient,$bucket);exit();

/**
 * 列出Bucket内所有目录和文件， 根据返回的nextMarker循环得到所有Objects
 *
 * @param OssClient $ossClient OssClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function listAllObjects($ossClient, $bucket)
{
    //构造dir下的文件和虚拟目录
    for ($i = 0; $i < 100; $i += 1) {
        $ossClient->putObject($bucket, "dir/obj" . strval($i), "hi");
        $ossClient->createObjectDir($bucket, "dir/obj" . strval($i));
    }

    $prefix = 'dir/';
    $delimiter = '/';
    $nextMarker = '';
    $maxkeys = 30;

    while (true) {
        $options = array(
            'delimiter' => $delimiter,
            'prefix' => $prefix,
            'max-keys' => $maxkeys,
            'marker' => $nextMarker,
        );
        try {
            $listObjectInfo = $ossClient->listObjects($bucket, $options);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
        $nextMarker = $listObjectInfo->getNextMarker();
        $listObject = $listObjectInfo->getObjectList();
        $listPrefix = $listObjectInfo->getPrefixList();
        if ($nextMarker === '') {
            break;
        }
    }
}


//listObjects($ossClient,$bucket,'caridhash1/caridhash2','/');exit();


/**
 * 列出Bucket内所有目录和文件, 注意如果符合条件的文件数目超过设置的max-keys， 用户需要使用返回的nextMarker作为入参，通过
 * 循环调用ListObjects得到所有的文件，具体操作见下面的 listAllObjects 示例
 *
 * @param OssClient $ossClient OssClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function listObjects($ossClient, $bucket,$prefix=null,$delimiter=null)
{
    $nextMarker = '';
    $maxkeys = 1000;
    $options = array(
        'delimiter' => $delimiter,
        'prefix' => $prefix,
        'max-keys' => $maxkeys,
        'marker' => $nextMarker,
    );
    try {
        $listObjectInfo = $ossClient->listObjects($bucket, $options);
    } catch (OssException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
    $objectList = $listObjectInfo->getObjectList(); // 文件列表
    $prefixList = $listObjectInfo->getPrefixList(); // 目录列表
    if (!empty($objectList)) {
        print("objectList:\n");
        foreach ($objectList as $objectInfo) {
            print($objectInfo->getKey() . "\n");
        }
    }
    if (!empty($prefixList)) {
        print("prefixList: \n");
        foreach ($prefixList as $prefixInfo) {
            print($prefixInfo->getPrefix() . "\n");
        }
    }
}
//listBuckets($ossClient);exit();

/**
 * 列出用户所有的Bucket
 *
 * @param OssClient $ossClient OssClient实例
 * @return null
 */
function listBuckets($ossClient)
{
    $bucketList = null;
    try {
        $bucketListInfo = $ossClient->listBuckets();
    } catch (OssException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
    $bucketList = $bucketListInfo->getBucketList();
    foreach ($bucketList as $bucket) {
        print($bucket->getLocation() . "\t" . $bucket->getName() . "\t" . $bucket->getCreatedate() . "\n");
    }
}
//doesBucketExist($ossClient,'tocartest');
//exit();
/**
 *  判断Bucket是否存在
 *
 * @param OssClient $ossClient OssClient实例
 * @param string $bucket 存储空间名称
 */
function doesBucketExist($ossClient, $bucket)
{
    try {
        $res = $ossClient->doesBucketExist($bucket);
    } catch (OssException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    if ($res === true) {
        print(__FUNCTION__ . ": OK" . "\n");
    } else {
        print(__FUNCTION__ . ": FAILED" . "\n");
    }
}