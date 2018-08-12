<?php
define("TOKEN", "zengjinzhe");
$wechatObj = new wechatCallbackapi();
//$wechatObj->valid();
$wechatObj->responseMsg();

class wechatCallbackapi
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }
    public function responseMsg()
    {
		$postStr = file_get_contents("php://input");
		if (!empty($postStr)){
                libxml_disable_entity_loader(true);
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
				$ev = $postObj->Event;
                $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							</xml>";             
				if ($ev == "subscribe"){
					$msgType = "text";
					require_once('ChemicalTools.php');
					$tools=new ChemicalTools;
					$contentStr = $tools->welcome();
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}
				if(!empty( $keyword ))
                {
					$msgType = "text";
					require_once('ChemicalTools.php');
					$tools=new ChemicalTools;
					$contentStr=$tools->processinput($keyword);
                	$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                	echo $resultStr;
                }else{
                	echo "Input something...";
                }
        }else {
        	echo "";
        	exit;
        }
    }
	
	private function getTimestamp($digits = false) {
        $digits = $digits > 10 ? $digits : 10;
        $digits = $digits - 10;
        if ((!$digits) || ($digits == 10)){
            return time();
        }else{
            return number_format(microtime(true),$digits,'','');
        }
    }
	private function checkSignature()
	{
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}
?>