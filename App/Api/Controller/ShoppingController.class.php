<?php
// 本类由系统自动生成，仅供测试用途
namespace Api\Controller;
use Think\Controller;
class ShoppingController extends PublicController {

	//***************************
	//  会员获取购物车列表接口
	//***************************
	public function index(){
		$qz=C('DB_PREFIX');
        $shopping=M("shopping_char");
        $shangchang=M("shangchang");
        $product=M("product");
		$total = 0;
		$user_id = intval($_REQUEST['user_id']);
		if (!$user_id) {
			echo json_encode(array('status'=>0));
			exit();
		}

        $cart = $shopping->where('uid='.intval($user_id))->field('id,uid,pid,price,num')->select();
        foreach ($cart as $k => $v) {
        	$pro_info = $product->where('id='.intval($v['pid']))->field('name,photo_x')->find();
        	$cart[$k]['pro_name']=$pro_info['name'];
        	$cart[$k]['photo_x']=__DATAURL__.$pro_info['photo_x'];
			$total += $v['price'] * $v['num'];
        }

		echo json_encode(array('status'=>1,'cart'=>$cart,'total'=>$total));
		exit();
    }

	//购物车商品删除
	public function delete(){
		$shopping=M("shopping_char");
		$cart_id=intval($_REQUEST['cart_id']);
		$check_id = $shopping->where('id='.intval($cart_id))->getField('id');
		if (!$check_id) {
			echo json_encode(array('status'=>1));
			exit();
		}

	    $res = $shopping->where('id ='.intval($cart_id))->delete(); // 删除
		if($res){
			echo json_encode(array('status'=>1));
			exit();
		}else{
			echo json_encode(array('status'=>0));
			exit();
		}
	}

	//***************************
	//  会员修改购物车数量接口
	//***************************
	public function up_cart(){
		$shopping=M("shopping_char");
		$uid = intval($_REQUEST['user_id']);
		$cart_id = intval($_REQUEST['cart_id']);
		$num=intval($_REQUEST['num']);

		if (!$uid || !$cart_id || !$num) {
			echo json_encode(array('status'=>0,'err'=>'网络异常.'.__LINE__));
			exit();
		}

		$check = $shopping->where('id='.intval($cart_id))->find();
		if (!$check) {
			echo json_encode(array('status'=>0,'err'=>'购物车信息错误！'));
			exit();
		}

		//检测库存
		$pro_num = M('product')->where('id='.intval($check['pid']))->getField('num');
		if($num>intval($pro_num)){
			echo json_encode(array('status'=>0,'err'=>'库存不足！'));
			exit();
		}
		
		$data=array();
		$data['num']=$num;

		$res = $shopping->where('id ='.intval($cart_id).' AND uid='.intval($uid))->save($data);
		if ($res) {
			echo json_encode(array('status'=>1,'succ'=>'操作成功!'));
			exit();
		}else{
			echo json_encode(array('status'=>0,'err'=>'操作失败.'));
			exit();
		}
		
	}

	//多个购物车商品删除
	public function qdelete(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'网络异常，请稍后再试.'));
			exit();
		}
		$shopping=M("shopping_char");
		$cart_id=trim($_REQUEST['cart_id'],',');
		if (!$cart_id) {
			echo json_encode(array('status'=>0,'err'=>'网络错误，请稍后再试.'));
			exit();
		}

	    $res = $shopping->where('id in ('.$cart_id.') AND uid='.intval($uid))->delete(); // 删除
		if($res){
			echo json_encode(array('status'=>1));
			exit();
		}else{
			echo json_encode(array('status'=>0,'err'=>'操作失败.'));
			exit();
		}
	}


	//添加购物车
	public function add(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		$pid = intval($_REQUEST['pid']);
		$num = intval($_REQUEST['num']);
		if (!intval($pid) || !intval($num)) {
			echo json_encode(array('status'=>0,'err'=>'参数错误.'));
			exit();
		}
		if($num >= 1){
			//加入购物车
			$check = $this->check_cart(intval($pid));
			if ($check['status']==0) {
				echo json_encode(array('status'=>0,'err'=>$check['err']));
				exit;
			}

			$check_info = M('product')->where('id='.intval($pid).' AND del=0 AND is_down=0')->find();

			//判断库存
			if (intval($check_info['num'])<=$num) {
				echo json_encode(array('status'=>0,'err'=>'库存不足！'));
				exit;
			}

			$shpp=M("shopping_char");
			//判断购物车内是否已经存在该商品
			$data = array();
			$cart_info = $shpp->where('pid='.intval($pid).' AND uid='.intval($uid))->field('id,num')->find();
			if ($cart_info) {
				$data['num'] = intval($num);
				//判断库存
				if (intval($check_info['num'])<=$data['num']) {
					echo json_encode(array('status'=>0,'err'=>'库存不足！'));
					exit;
				}
				$res = $shpp->where('id='.intval($cart_info['id']))->save($data);
			}else{
				$data['pid']=intval($pid);
				$data['num']=intval($num);
				$data['addtime']=time();
				$data['uid']=intval($uid);
				$data['shop_id']=intval($check_info['shop_id']);
				$ptype = 1;
				if (intval($check_info['pro_type'])) {
					$ptype = intval($check_info['pro_type']);
				}
				$data['type']=$ptype;
				
				if($check_info['price_yh'] != ''){
					$check_info['price'] = $check_info['price_yh'];
				}
				
				$data['price'] = $check_info['price'];

				$res=$shpp->add($data);
			}

			if($res){
				echo json_encode(array('status'=>1,'cart_id'=>$res)); //该商品已成功加入您的购物车
				exit;
			}else{
				echo json_encode(array('status'=>0,'err'=>'加入失败.'));
				exit;
			}
		}
		
	}
	//减少购物车
	public function plus(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		$pid = intval($_REQUEST['pid']);
		$num = intval($_REQUEST['num']);
		if (!intval($pid)) {
			echo json_encode(array('status'=>0,'err'=>'参数错误.'));
			exit();
		}
		if($num >= 1){
			//加入购物车
			$check = $this->check_cart(intval($pid));
			if ($check['status']==0) {
				echo json_encode(array('status'=>0,'err'=>$check['err']));
				exit;
			}

			$shpp=M("shopping_char");
			//判断购物车内是否已经存在该商品
			$data = array();
			$cart_info = $shpp->where('pid='.intval($pid).' AND uid='.intval($uid))->field('id,num')->find();
			if ($cart_info) {
				$data['num'] = intval($num);
				
				$res = $shpp->where('id='.intval($cart_info['id']))->save($data);
			}

			if($res){
				echo json_encode(array('status'=>1,'cart_id'=>$res)); 
				exit;
			}else{
				echo json_encode(array('status'=>0,'err'=>'加入失败.'));
				exit;
			}
		}else{
			$check = $this->check_cart(intval($pid));
			if ($check['status']==0) {
				echo json_encode(array('status'=>0,'err'=>$check['err']));
				exit;
			}
			$res = M("shopping_char")->where('pid='.intval($pid).' AND uid='.intval($uid))->delete(); // 删除
			if($res){
				echo json_encode(array('status'=>1));
				exit();
			}else{
				echo json_encode(array('status'=>0));
				exit();
			}
		}
		
	}

	//***************************
	//  会员立即购买下单接口
	//***************************
	public function check_shop(){
		$cart_id = trim($_REQUEST['cart_id'],',');
		$id=explode(',',$cart_id);
		if (!$cart_id) {
			echo json_encode(array('status'=>0));
			exit();
		}

		foreach ($id as $k=>$v){
			$shoop[$k]=M("shopping_char")->where('id ='.intval($v))->field('shop_id,pid')->find();
        }

		foreach($shoop as $key => $value){
			$result[$key] = M("product")->where('id='.intval($value['pid']))->field('id,price,price_yh')->select();
			$price[] = i_array_column($result[$key], 'price_yh');
		}
		//dump($price);exit;
		foreach($price as $keys => $va){
			$str .= implode(",", $va).",";
		}
		$str = trim($str, ",");
		$parr = explode(",", $str);
		if(array_sum($parr) && in_array("0", $parr)){
			echo json_encode(array('status'=>0));
			exit();
		}
		
		$names = i_array_column($shoop, 'shop_id');
		
		$arr=array_unique($names);
		$val= sizeof($arr);
		if($val=='1'){
			echo json_encode(array('status'=>1));
			exit();
		}else{
			echo json_encode(array('status'=>2));
			exit();
		}	 
	}

	//购物车添加。删除检测公共方法
	public function check_cart($pid){
		//检查产品是否存在或删除
		$check_info = M('product')->where('id='.intval($pid).' AND del=0 AND is_down=0')->find();
		if (!$check_info) {
			return array('status'=>0,'err'=>'商品不存在或已下架.');
		}

		return array('status'=>1);
	}   

    /*
       去除HTNL标签
    */
    public function html_entity($array){
    	foreach ($array as $key => $value) {
        	$array[$key]['content'] = strip_tags(html_entity_decode($value['content']));
        }
        return $array;
    }

}