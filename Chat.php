<?php

namespace app\api\controller;

use think\Controller;

class Chat extends Controller
{

	/**
	 *保存消息
	*/
	public function saveMsg()
	{
		$msg = input();

		$data['fromid'] = $msg['fromid'];
		$data['fromname'] = $this->getName($msg['fromid']);
		$data['toid'] = $msg['toid'];
		$data['toname'] = $this->getName($msg['toid']);
		$data['content'] = $msg['data'];
		$data['isread'] = 0;
		$data['time'] = $msg['time'];
		$data['type'] = $msg['genre'];

		db('msg')->insert($data);
	}

	/**
	 *改变未读消息状态
	*/
	public function changeReadMsg()
	{
		$fromid = input('fromid');
		$toid = input('toid');

		db('msg')->where(['fromid'=>$toid,'toid'=>$fromid])->update(['isread'=>1]);
	}

	//获取最后一条聊天记录
	public function getLastMsg($fromid,$toid)
	{
		$msg = db('msg')->where("(fromid=$fromid and toid=$toid)||(fromid=$toid and toid=$fromid)")->order('id desc')->field('content,type')->find();

		return $msg;
	}

	//获取用户名
	public function getName($uid)
	{
		$name = db('user')->where('id',$uid)->value('name');

		return $name;
	}

	public function getBothName()
	{	
		$fromid = input('fromid');
		$toid = input('toid');
		$name = db('user')->where('id',$fromid)->value('name');
		$toname = db('user')->where('id',$toid)->value('name');

		return ['fromname'=>$name,'toname'=>$toname];
	}

	//获取聊天记录
	public function getMsg()
	{
		$page = input('page') ?: 1;
		$num = 10;
		

		$fromid = input('fromid');
		$toid = input('toid');

		$count = db('msg')->where("(fromid=$fromid and toid=$toid)||(fromid=$toid and toid=$fromid)")->count();

		// $count = db('msg')->where(['fromid'=>$fromid,'toid'=>$toid])->whereOr(['fromid'=>$toid,'toid'=>$fromid])->count();

		$start = $count-$page*$num;
		if ($start < 0) {
			$m = abs($start);
			if ($m >= $num) {
				return ['code'=>0,'data'=>'','msg'=>'已无更多消息'];
			}else{
				$start = 0;
				$num -= $m;
			}
		}
		$list = db('msg')->where("(fromid=$fromid and toid=$toid)||(fromid=$toid and toid=$fromid)")
						->limit($start,$num)
						->order('id asc')
						->select();

		return ['code'=>1,'data'=>$list];
	}

	//上传图片
	public function uploadImg()
	{
		$file = $_FILES['file'];
		$fromid = input('fromid');
		$toid = input('toid');
		$online = input('online');

		$suffix = strtolower(strrchr($file['name'],'.'));
		$type = ['.jpg','.jpeg','png','gif'];

		if (!in_array($suffix,$type)) {
			return ['code'=>0,'msg'=>'图片类型错误'];
		}

		if ($file['size']/1024 > 1024*5) {
			return ['code'=>0,'msg'=>'图片不能大于5M'];
		}

		$name = uniqid();
		$path = ROOT_PATH.'public\\uploads\\';
		$file_name = $path.$name.$suffix;

		$res = move_uploaded_file($file['tmp_name'], $file_name);

		if ($res)
		{
			return ['code'=>1,'data'=>$name.$suffix];
		}else{
			return ['code'=>0,'msg'=>'上传失败'];
		}
	}

	//获取列表
	public function getList()
	{
		$id = input('id');

		$list = db('msg')->field('fromid,toid,fromname,toname,time,content')->where('toid',$id)->group('fromid')->select();
		
		foreach ($list as $k => $v) {
			$list[$k]['last_msg'] = $this->getLastMsg($v['fromid'],$v['toid']);
		}
		return ['code'=>1,'data'=>$list];
	}
}