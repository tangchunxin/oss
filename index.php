<?php
/**
 * @ file 安卓图片上传后台部分
 */

date_default_timezone_set('Asia/Shanghai');
require_once __DIR__ . '/src/taobao-sms-sdk-auto/TopSdk.php';
require_once __DIR__ . '/autoload.php';
defined('APP_PATH') or define('APP_PATH',__DIR__.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR);
defined('EXT') or define('EXT','.php');

if(file_exists(APP_PATH.'Object'.EXT))
require(APP_PATH.'Object'.EXT);
else
exit('file not exists');


/**
 * @ gearman 部分
 */

// >=php 5.5
if (function_exists('cli_set_process_title'))
{
	@cli_set_process_title('GearManWorker_OSS');
}
require_once('./GuoJi.php');//国际短信

////////////////////////////////////////////////
$worker = new GearmanWorker();
$worker->addServer('127.0.0.1','4730');
$worker->addFunction('upload','myuploadDir');
$worker->addFunction('sms_cz','sms_cz');
$worker->addFunction('sms_cz_guoji','sms_cz_guoji');
while(1){
	$worker->work();  //等10s
	if($worker->returnCode()!=GEARMAN_SUCCESS){
		error_log(date('Y-m-d H:i:s').'|'.$work->returnCode(),3,'/data/www/html/oss/log/fail.log');
	}
};
function myuploadDir($job){
	$myargs = $job->workload();

	$myargs = json_decode($myargs,1);//转换成数组
	$up_load_re = uploadDir($myargs['localDirectory'],$myargs['prefix']);
	error_log(date('Y-m-d H:i:s').var_export($up_load_re, true)."\n",'3','/data/www/html/oss/log/oss.log');
	return;

}
function sms_cz($job){
	$myargs = $job->workload();
	$myargs = json_decode($myargs);


	$c = new TopClient;
	$c->appkey = Config::SMS_ALIDAYU_APPKEY;
	$c->secretKey = Config::SMS_ALIDAYU_SECRETKEY;
	$req = new AlibabaAliqinFcSmsNumSendRequest;
	$req->setExtend('');
	$req->setSmsType("normal");

	if(substr($myargs->phone,0,2) == 86)
	{
		$myargs->phone = substr($myargs->phone,2,11);
	}
	if(isset($myargs->template_code) && $myargs->template_code
	&& isset($myargs->sms_param) && $myargs->sms_param
	&& isset($myargs->signname) && $myargs->signname
	&& isset($myargs->phone) && $myargs->phone
	)
	{

		$req->setSmsParam($myargs->sms_param);
		$req->setSmsFreeSignName($myargs->signname);
		$req->setRecNum($myargs->phone);
		$req->setSmsTemplateCode($myargs->template_code);
		$resp = $c->execute($req);

		if(isset($resp->result->err_code) && ($resp->result->err_code == 0)){
			error_log(date('Y-m-d H:i:s').__FUNCTION__."操作成功！参数：".$job->workload()."\n",'3','/data/www/html/oss/log/sms_ok.log');

		}else{
			error_log(date('Y-m-d H:i:s').__FUNCTION__."操作失败！参数：".$job->workload()." 返回结果:".json_encode($resp)."\n",'3','/data/www/html/oss/log/sms_fail.log');
		}
	}

	unset($myargs);
	unset($req);
	unset($c);
	return ;
}

function sms_cz_guoji($job){
	$myargs = $job->workload();
	$myargs = json_decode($myargs);

/////////////////////////////////////////////////		
	//请使用您自己的开发者KEY
	$accesskey = Config::SMS_TIANRUIYUN_APPKEY;
	$accessScrect = Config::SMS_TIANRUIYUN_SECRETKEY;

	//设置公共参数
	$randomStr = randStr(10);
	$timestamp = time() * 1000;
	$token = md5($accessScrect.$randomStr.$timestamp);


	if(isset($myargs->template_code) && $myargs->template_code
	&& isset($myargs->signname) && $myargs->signname
	&& isset($myargs->phone) && $myargs->phone
	)
	{
	//发送请求

		$result = HttpClient::quickPost('http://api.1cloudsp.com/intl/api/send', array(    
		    'token' => $token,    
		    'accesskey' => $accesskey,
		    'timestamp' => $timestamp,
		    'random' => $randomStr,
		    'mobile' =>$myargs->phone,
		    'content' => '验证码'.$myargs->template_code.',您正在登录'. $myargs->signname,
		    'sign' => '【灵飞棋牌】',
		    'scheduleSendTime'=>''
		    
		));

		error_log(date('Y-m-d H:i:s').__FUNCTION__."操作成功11！参数：".$result."\n",'3','/data/www/html/oss/log/sms_ok.log');
		
		if(($result->code == 0))
		{
			error_log(date('Y-m-d H:i:s').__FUNCTION__."操作成功！参数：".$job->workload()."\n",'3','/data/www/html/oss/log/sms_ok.log');

		}
		else
		{
			error_log(date('Y-m-d H:i:s').__FUNCTION__."操作失败！参数：".$job->workload()." 返回结果:".json_encode($result)."\n",'3','/data/www/html/oss/log/sms_fail.log');
		}
	}

	unset($myargs);

	return ;
}

/**
 * 格式localDirectory
 *    和prefix
 */
