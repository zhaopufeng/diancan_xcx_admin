<?php
namespace Api\Controller;
use Think\Controller;
class AddressController extends PublicController {
	//***************************
	//  获取会员地址数据接口
	//***************************
    public function index(){
        $user_id=intval($_REQUEST['user_id']);
        if (!$user_id){
            echo json_encode(array('status'=>0,'err'=>'网络异常.'.__LINE__));
            exit();
        }

    	//所有地址
    	$addressModel = M('address');
		$adds_list=$addressModel->where('uid='.intval($user_id))->order('is_default desc,id desc')->select();
    	
    	//所有省份
		// $china_city=M("china_city");
  //       $sheng = $china_city->where('tid=0')->field('id,name')->select();

		echo json_encode(array('status'=>1,'adds'=>$adds_list));
		exit();
    }

    //***************************
    //  会员添加地址接口
    //***************************
    public function add_adds(){
        $user_id=intval($_REQUEST['user_id']);
        if (!$user_id){
            echo json_encode(array('status'=>0,'err'=>'网络异常.'.__LINE__));
            exit();
        }

        //接收ajax传过来的数据
        //data:{user_id:uid,receiver:rec,tel:tel,sheng:sheng,city:city,quyu:quyu,adds:address,code:code}
        $data = array();
        $data['name'] = trim($_POST['receiver']);
        $data['tel'] = trim($_POST['tel']);
        $data['dizhi'] = $_POST['dizhi'];
        $data['address'] = $_POST['adds'];
        $data['uid'] = intval($user_id);
		$data['address_xq'] = $data['dizhi'] . ' ' . $data['address'];
		if (!$data['name'] || !$data['tel'] || !$data['address'] || !$data['dizhi']) {
            echo json_encode(array('status'=>0,'err'=>'请先完善信息后再提交.'));
            exit();
        }
        $check_id = M('address')->where($data)->getField('id');
        if ($check_id) {
            echo json_encode(array('status'=>0,'err'=>'该地址已经添加了.'));
            exit();
        }
        if ($res) {
            $arr = array();
            $arr['addr_id'] = $res;
            $arr['rec'] = $data['name'];
            $arr['tel'] = $data['tel'];
            $arr['addr_xq'] = $data['dizhi'] . ' ' . $data['address'];
            echo json_encode(array('status'=>1,'add_arr'=>$arr));
            exit();
        }else{
            echo json_encode(array('status'=>0,'err'=>'操作失败.'));
            exit();
        }
    }
	function get_distance(){  
    $info = M('program')->where('id = 1')->find();
	if(!$_POST['selectlatitude'] || !$_POST['selectlongitude']){
		echo json_encode(array('status'=>0,'err'=>'位置获取失败'));exit();
	}
	/*$from[] = $info['latitude'];
	$from[] = $info['longitude'];
	$to[] = $_POST['selectlatitude'];
	$to[] = $_POST['selectlongitude'];
	sort($from);  
    sort($to);  
    $EARTH_RADIUS = 6370.996;   
      
    $distance = $EARTH_RADIUS*2*asin(sqrt(pow(sin( ($from[0]*pi()/180-$to[0]*pi()/180)/2),2)+cos($from[0]*pi()/180)*cos($to[0]*pi()/180)* pow(sin( ($from[1]*pi()/180-$to[1]*pi()/180)/2),2)))*1000;  
      */
	$lat1 = $info['latitude'];
	$lng1 = $info['longitude'];
	$lat2 = (float)$_POST['selectlatitude'];
	$lng2 = (float)$_POST['selectlongitude'];
	
	 $pi80 = M_PI / 180;
	 $lat1 *= $pi80;
	 $lng1 *= $pi80;
	 $lat2 *= $pi80;
	 $lng2 *= $pi80;
	 $r = 6372.797; // mean radius of Earth in km
	 $dlat = $lat2 - $lat1;
	 $dlng = $lng2 - $lng1;
	 $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
	 $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	 $km = $r * $c;
	if($km > $info['distance']){
		echo json_encode(array('status'=>0,'err'=>'超出配送范围' . $distance));exit();
	}else{
		echo json_encode(array('status'=>1));exit();
	}
	
      
}  
    //***************************
    //  会员获取单个地址接口
    //***************************
    public function details(){
        $addr_id = intval($_REQUEST['addr_id']);
        if (!$addr_id) {
            echo json_encode(array('status'=>0));
            exit();
        }

        $address = M('address')->where('id='.intval($addr_id))->find();
        if (!$address) {
            echo json_encode(array('status'=>0));
            exit();
        }
        $arr=array();
        $arr['status']=1;
        $arr['addr_id']=$address['id'];
        $arr['name'] = $address['name'];
        $arr['tel'] = $address['tel'];
        $arr['addr_xq'] = $address['address_xq'];
        echo json_encode($arr);
        exit();
    }

    //***************************
    //  会员删除地址接口
    //***************************
    public function del_adds(){
        $user_id=intval($_REQUEST['user_id']);
        if (!$user_id){
            echo json_encode(array('status'=>0,'err'=>'网络异常.'.__LINE__));
            exit();
        }

        $id_arr = trim($_POST['id_arr'],',');
        if ($id_arr) {
            $res = M('address')->where('uid='.intval($user_id).' AND id IN ('.$id_arr.')')->delete();
            if ($res) {
                echo json_encode(array('status'=>1));
                exit();
            }else{
                echo json_encode(array('status'=>0,'err'=>'操作失败.'));
                exit();
            }
        }else{
            echo json_encode(array('status'=>0,'err'=>'没有找到要删除的数据.'));
            exit();
        }
    }

    //***************************
    //  获取省份数据接口
    //***************************
    public function get_province(){
        //所有省份
        $china_city=M("china_city");
        $list = $china_city->where('tid=0')->field('id,name')->select();

        echo json_encode(array('status'=>1,'list'=>$list));
        exit();
    }

    //***************************
    //  获取城市数据接口
    //***************************
    public function get_city(){
        $sheng=intval($_REQUEST['sheng']);
        if (!$sheng){
            echo json_encode(array('status'=>0,'err'=>'请选择省份.'.__LINE__));
            exit();
        }
        
        //所有省份
        $china_city=M("china_city");
        $list = $china_city->where('tid=0')->field('id,name')->select();
        $city = $china_city->where('tid='.intval($list[$sheng-1]['id']))->field('id,name')->select();

        echo json_encode(array('status'=>1,'city_list'=>$city,'sheng'=>intval($list[$sheng-1]['id'])));
        exit();
    }

    //***************************
    //  获取区域数据接口
    //***************************
    public function get_area(){
        $city=intval($_REQUEST['city']);
        if (!$city){
            echo json_encode(array('status'=>0,'err'=>'请选择城市.'.__LINE__));
            exit();
        }
        
        //所有省份
        $china_city=M("china_city");
        $list = $china_city->where('tid='.intval($_REQUEST['sheng']))->field('id,name')->select();
        $area = $china_city->where('tid='.intval($list[$city-1]['id']))->field('id,name')->select();

        echo json_encode(array('status'=>1,'area_list'=>$area,'city'=>intval($list[$city-1]['id'])));
        exit();
    }

    //***************************
    //  获取邮政编号接口
    //***************************
    public function get_code(){
        $quyu=intval($_REQUEST['quyu']);
        
        //所有省份
        $china_city=M("china_city");
        $list = $china_city->where('tid='.intval($_REQUEST['city']))->field('id,name')->select();
        $code = $china_city->where('id='.intval($list[$quyu-1]['id']))->getField('code');
        echo json_encode(array('status'=>1,'code'=>$code,'area'=>intval($list[$quyu-1]['id'])));
        exit();
    }

    //***************************
    //  设置默认地址
    //***************************
    public function set_default(){
        $uid=intval($_REQUEST['uid']);
        if (!$uid){
            echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
            exit();
        }

        $addr_id = intval($_REQUEST['addr_id']);
        if (!$addr_id) {
            echo json_encode(array('status'=>0,'err'=>'地址信息错误.'));
            exit();
        }
        //修改默认状态
        $check = M('address')->where('uid='.intval($uid).' AND is_default=1')->find();
        if ($check) {
            $up1= M('address')->where('uid='.intval($uid))->save(array('is_default'=>0));
            if (!$up1) {
                echo json_encode(array('status'=>0,'err'=>'设置失败.'.__LINE__));
                exit();
            }
        }
        
        $up2 = M('address')->where('id='.intval($addr_id).' AND uid='.intval($uid))->save(array('is_default'=>1));
        if ($up2) {
            echo json_encode(array('status'=>1));
            exit();
        }else{
            echo json_encode(array('status'=>0,'err'=>'设置失败.'.__LINE__));
            exit();
        }
    }

}