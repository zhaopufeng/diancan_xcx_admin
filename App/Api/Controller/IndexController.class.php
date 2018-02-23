<?php
namespace Api\Controller;
use Think\Controller;
class IndexController extends PublicController {
	//***************************
	//  首页数据接口
	//***************************
    public function index(){
    	//如果缓存首页没有数据，那么就读取数据库
    	/***********获取首页顶部轮播图************/
    	$type = intval($_REQUEST['type']);
		$user_id = intval($_REQUEST['userId']);
		$where = '';
		$cart = array();
		switch ($type){
			case 0: 
				$where = ' AND type=1 ';
				break;
			case 1: 
				$where = ' AND is_show=1 ';
				break;
			case 2: 
				$where = ' AND is_hot=1 ';
				break;
			default:
				$where = ' AND type=1 ';
				break;
		}
		if($user_id){
			$shopping=M("shopping_char");
			$product=M("product");
			$cart = $shopping->where('uid='.intval($user_id))->field('id,uid,pid,price,num')->select();
		}	
		$ggtop=M('guanggao')->order('sort desc,id asc')->field('id,name,photo')->limit(10)->select();
		foreach ($ggtop as $k => $v) {
			$ggtop[$k]['photo']=__DATAURL__.$v['photo'];
			$ggtop[$k]['name']=urlencode($v['name']);
		}
    	/***********获取首页顶部轮播图 end************/
		$list = M('category')->where('tid=1')->field('id,tid,name')->select();
        $plist = $list;
		//======================
    	//首页产品
    	//======================
		foreach($plist as $k=>$v){
			$array = M('product')->where('del=0 AND pro_type=1 AND is_down=0' . $where . ' AND tid = ' . $v['id'])->order('sort desc,id desc')->field('id,name,intro,photo_x,price_yh,price,shiyong')->limit(8)->select();
			if(empty($array)){
				unset($plist[$k]);continue;
			}
			foreach ($array as $key => $val) {
				$array[$key]['photo_x'] = __DATAURL__.$val['photo_x'];
				$array[$key]['count'] = 0;
				if($cart){
					foreach($cart as $item){
						if($item['pid'] == $val['id']){
							$array[$key]['count'] = $item['num'];
						}
					}
				}
			}
			$plist[$k]['pro_list'] = $array;
		}
    	/* 获取营业时间 */
		$ar = array();
		$yingye = 1;
		$config = M('program')->where('id=1')->find();
		$starttime = $config['starttime'];
		$endtime = $config['endtime'];
		$time = date('H:i',time());
		$ar = date_parse($time);
		$time = $ar['hour'] * 3600 + $ar['minute'] * 60;
		if($starttime){
			$ar = date_parse($starttime);
			$starttime = $ar['hour'] * 3600 + $ar['minute'] * 60;
			if($starttime > $time){
				$yingye = 0;
			}
		}
		if($endtime){
			$ar = date_parse($endtime);
			$$endtime = $ar['hour'] * 3600 + $ar['minute'] * 60;
			if($$endtime < $time){
				$yingye = 0;
			}
		}
		echo json_encode(array('ggtop'=>$ggtop,'prolist'=>$plist,'cart'=>$cart,'yingye' => $yingye,'config' => $config));
    	exit();
    }

    //***************************
    //  首页产品 分页
    //***************************
    public function getlist(){
        $page = intval($_REQUEST['page']);
        $limit = intval($page*8)-8;

        $pro_list = M('product')->where('del=0 AND pro_type=1 AND is_down=0 AND type=1')->order('sort desc,id desc')->field('id,name,photo_x,price_yh,shiyong')->limit($limit.',8')->select();
        foreach ($pro_list as $k => $v) {
            $pro_list[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }

        echo json_encode(array('prolist'=>$pro_list));
        exit();
    }

    public function ceshi(){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<32;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        echo $str;
    }

}