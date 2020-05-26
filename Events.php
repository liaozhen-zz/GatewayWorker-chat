<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, json_encode(['type'=>'init','client_id'=>$client_id]));
        echo $client_id.PHP_EOL;
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {
        $message_data = json_decode($message,true);

        if (!$message_data) {
            return;
        }

        $fromid = $message_data['fromid'];
        

        switch ($message_data['type']) {
          //绑定用户业务ID
          case 'bind':

            Gateway::bindUid($client_id,$fromid);
            $_SESSION['uid'] = $fromid;
            // 向所有人发送
            Gateway::sendToAll(json_encode(['type'=>'online','toid'=>$fromid,'data'=>1]));
            return;

          //判断用户是否在线
          case 'online':

            $toid = $message_data['toid'];
            $date = ['type'=>'online',
                     'fromid'=>$fromid,
                     'toid'=>$toid
            ];
            
            if (Gateway::isUidOnline($toid)) {
                $date['data'] = 1;
              }else{
                $date['data'] = 0;
              }
              Gateway::sendToUid($fromid,json_encode($date));
            return;

          //发送消息
          case 'say':

            $toid = $message_data['toid'];
            $text = nl2br(htmlspecialchars($message_data['data']));
            
            $date = ['type'=>'text',
                     'fromid'=>$fromid,
                     'toid'=>$toid,
                     'data'=>$text,
                     'time'=>time(),
                     'genre'=>1
            ];
            //判断用户是否在线
            if (Gateway::isUidOnline($toid))
            {
              // 向指定用户发送 
              Gateway::sendToUid($toid,json_encode($date));
            }

            //保存聊天内容
            $date['type'] = 'save';
            Gateway::sendToUid($fromid,json_encode($date));
            return;

          //发送图片
          case 'img':

            $toid = $message_data['toid'];
            $img = $message_data['data'];
            
            $date = ['type'=>'img',
                     'fromid'=>$fromid,
                     'toid'=>$toid,
                     'data'=>$img,
                     'time'=>time(),
                     'genre'=>2
            ];
            //判断用户是否在线
            if (Gateway::isUidOnline($toid))
            {
              // 向指定用户发送 
              Gateway::sendToUid($toid,json_encode($date));
            }

            //保存聊天内容
            $date['type'] = 'save';
            Gateway::sendToUid($fromid,json_encode($date));
            return;
        }
        
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
      // echo $client_id.PHP_EOL;
      // $uid = Gateway::getUidByClientId($client_id);
      $uid = $_SESSION['uid'];
      // 向所有人发送 
      Gateway::sendToAll(json_encode(['type'=>'onclose','toid'=>$uid,'data'=>0]));
   }
}
