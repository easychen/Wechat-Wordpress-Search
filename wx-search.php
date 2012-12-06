<?php
   /*
   Plugin Name: 微信公众平台搜索插件
   Plugin URI: http://ftqq.com/
   Description: 微信公众平台插件，允许用户通过微信关键字获取相关的文章内容。假设当前wordpress的地址为http://ftqq.com，那么安装完插件后，在微信中需要填写的接口地址为http://ftqq.com/wp-content/plugins/wx-search/wx-search.php 
   Version: 1.0
   Author: easychen@qq.com
   Author URI: http://ftqq.com
   License: BSD
   */
 

// 此token必须和微信公众平台中的设置保持一致
// 设置页面 http://mp.weixin.qq.com/cgi-bin/callbackprofile?t=wxm-callbackapi&type=info&lang=zh_CN
define("TOKEN", "weixintouken");


// 此图片用于搜索出来的文章不包含图片时的默认图片，直接从wordpress的媒体库中挑一张即可。
// 采用相对路径 
define("DEFAULT_COVER", "/wp-content/uploads/2012/12/search_cover.png");

define("WELCOME" , "欢迎关注方糖气球，我们是一个专注于技术和产品融合区域的博客，一般每周更新一次文章，您可以发送关键字获取以往的相关文章");

// 假设当前wordpress的地址为http://ftqq.com，
// 那么安装完插件后，在微信中需要填写的接口地址为http://ftqq.com/wp-content/plugins/wx-search/wx-search.php


// 以下内容不需要改动

$wechatObj = new wechatCallbackapiTest();


if( isset($_REQUEST['echostr']) )
  $wechatObj->valid();
elseif( isset( $_REQUEST['signature'] ) )
{
  chdir('../../../');
  include( 'wp-load.php' );
  $wechatObj->responseMsg();
}
  


class wechatCallbackapiTest
{
  public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
          echo $echoStr;
          exit;
        }
    }

    public function responseMsg()
    {
    //get post data, May be due to the different environments
    $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
    if (!empty($postStr)){
                
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                       
        if(!empty( $keyword ))
                {
                  //file_put_contents( 'keyword.txt' , $keyword );
                  
                  if($articles = ws_get_article( $keyword  ))
{
                   ob_start(); 
                  ?><xml>
<ToUserName><![CDATA[<?=$fromUsername?>]]></ToUserName>
<FromUserName><![CDATA[<?=$toUsername?>]]></FromUserName>
<CreateTime><?=$time?></CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<Content><![CDATA[搜索结果]]></Content>
<ArticleCount><?=count($articles)?></ArticleCount>
<Articles><?php foreach( $articles as $item ): ?>
<item> 
  <Title><![CDATA[<?=$item['title']?>]]></Title>
  <Description><![CDATA[<?=$item['content']?>]]></Description>
  <PicUrl><![CDATA[<?=$item['pic']?>]]></PicUrl>
  <Url><![CDATA[<?=$item['url']?>]]></Url>
</item>
<?php endforeach; ?></Articles>
<FuncFlag>0</FuncFlag>
</xml><?php
$xml = ob_get_contents();
//file_put_contents('xml.txt', $xml);
header('Content-Type: text/xml');
echo trim($xml); 

 }else
 {
   if( $keyword == 'Hello2BizUser' )
 {?>
<xml>
<ToUserName><![CDATA[<?=$fromUsername?>]]></ToUserName>
<FromUserName><![CDATA[<?=$toUsername?>]]></FromUserName>
<CreateTime><?=time()?></CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[<?=WELCOME?>]]></Content>
</xml> 
<?php }
else{
?>
<xml>
<ToUserName><![CDATA[<?=$fromUsername?>]]></ToUserName>
<FromUserName><![CDATA[<?=$toUsername?>]]></FromUserName>
<CreateTime><?=time()?></CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[没有找到包含关键字的文章，试试其他关键字？]]></Content>
</xml> 
<?php
 }  }              
                }else{
                  echo "请输入关键字，我们将返回对应的文章...";
                }

        }else {
          echo "";
          exit;
        }
    }
    
  private function checkSignature()
  {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];  
            
    $token = TOKEN;
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );
    
    if( $tmpStr == $signature ){
      return true;
    }else{
      return false;
    }
  }
}

  function ws_get_article( $keyword , $limit = 10 )
{
  query_posts( array( 's' => $keyword ) );
  $i = 0;
  $results = array();
  while( have_posts() && ($i < 10) )
  {
    the_post();
    $result['title'] = get_the_title();
    $result['content'] = mb_strimwidth(get_the_content() , 0 , 200 , '...' , 'UTF-8' );
    $result['url'] = get_site_url().'/?p='.get_the_id();
    $result['pic'] = thumbnail_url(get_the_post_thumbnail());
    if( !$result['pic'] ) $result['pic'] = get_site_url(). DEFAULT_COVER;



    $results[] = $result;
    $i++;
  }

  if( count( $results ) > 0 ) return $results ; 
  else return false;

}

function thumbnail_url( $html )
{
  $reg = '/src="(.+?)"/is';
  if(preg_match( $reg , $html , $out ))
  {
    return $out[1];
  }

  return false;
}


