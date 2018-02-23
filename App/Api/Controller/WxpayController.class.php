<?php
namespace Api\Controller;
use Think\Controller;
class WxpayController extends Controller{
	//构造函数
    public function _initialize(){
    	//php 判断http还是https
    	$this->http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
		vendor('WeiXinpay.wxpay');
	}

	//***************************
	//  微信支付 接口
	//***************************
	public function wxpay(){
		$pay_sn = trim($_REQUEST['order_sn']);
		if (!$pay_sn) {
			echo json_encode(array('status'=>0,'err'=>'支付信息错误！'));
			exit();
		}

		$order_info = M('order')->where('order_sn="'.$pay_sn.'"')->find();
		if (!$order_info) {
			echo json_encode(array('status'=>0,'err'=>'没有找到支付订单！'));
			exit();
		}

		if (intval($order_info['status'])!=10) {
			echo json_encode(array('status'=>0,'err'=>'订单状态异常！'));
			exit();
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
				echo json_encode(array('status'=>0,'err'=>'不在营业时间内！'));
				exit();
			}
		}
		if($endtime){
			$ar = date_parse($endtime);
			$$endtime = $ar['hour'] * 3600 + $ar['minute'] * 60;
			if($$endtime < $time){
				echo json_encode(array('status'=>0,'err'=>'不在营业时间内！'));
				exit();
			}
		}
		
		//①、获取用户openid
		$tools = new \JsApiPay();
		$openId = M('user')->where('id='.intval($order_info['uid']))->getField('openid');
		if (!$openId) {
			echo json_encode(array('status'=>0,'err'=>'用户状态异常！'));
			exit();
		}
		
		//②、统一下单
		$input = new \WxPayUnifiedOrder();
		$input->SetBody("龙田测试_".trim($order_info['order_sn']));
		$input->SetAttach("龙田测试_".trim($order_info['order_sn']));
		$input->SetOut_trade_no($pay_sn);
		$input->SetTotal_fee(floatval($order_info['amount'])*100);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 3600));
		$input->SetGoods_tag("龙田测试_".trim($order_info['order_sn']));
		$input->SetNotify_url('https://kafei.dino-fei.top/index.php/Api/Wxpay/notify');
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = \WxPayApi::unifiedOrder($input);
		//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
		//printf_info($order);
		$arr = array();
		$arr['appId'] = $order['appid'];
		$arr['nonceStr'] = $order['nonce_str'];
		$arr['package'] = "prepay_id=".$order['prepay_id'];
		$arr['signType'] = "MD5";
		$arr['timeStamp'] = (string)time();
		$str = $this->ToUrlParams($arr);
		$arr['prepay_id'] = $order['prepay_id'];
		$arr['order_id'] = $order_info['id'];
		$jmstr = $str."&key=".\WxPayConfig::KEY;
		$arr['paySign'] = strtoupper(MD5($jmstr));
		echo json_encode(array('status'=>1,'arr'=>$arr));
		exit();
		//获取共享收货地址js函数参数
		//$editAddress = $tools->GetEditAddressParameters();
		//$this->assign('jsApiParameters',$jsApiParameters);
		//$this->assign('editAddress',$editAddress);
	}

	//***************************
	//  支付回调 接口
	//***************************
	public function notify(){
		/*$notify = new \PayNotifyCallBack();
		$notify->Handle(false);*/

		$res_xml = file_get_contents("php://input");
		file_put_contents('ceshi.php',$res_xml);
		libxml_disable_entity_loader(true);
		$ret = json_decode(json_encode(simplexml_load_string($res_xml,'simpleXMLElement',LIBXML_NOCDATA)),true);

		$path = "./Data/log/";
		if (!is_dir($path)){
			mkdir($path,0777);  // 创建文件夹test,并给777的权限（所有权限）
		}
		$content = date("Y-m-d H:i:s").'=>'.json_encode($ret);  // 写入的内容
		$file = $path."weixin_".date("Ymd").".log";    // 写入的文件
		file_put_contents($file,$content,FILE_APPEND);  // 最简单的快速的以追加的方式写入写入方法，

		$data = array();
		$data['order_sn'] = $ret['out_trade_no'];
		$data['pay_type'] = 'weixin';
		$data['trade_no'] = $ret['transaction_id'];
		$data['total_fee'] = $ret['total_fee'];
		$result = $this->orderhandle($data);
		
		
		if (is_array($result)) {
			$xml = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg>";
			$xml.="</xml>";
			echo $xml;
		}else{
			$contents = 'error => '.json_encode($result);  // 写入的内容
			$files = $path."error_".date("Ymd").".log";    // 写入的文件
			file_put_contents($files,$contents,FILE_APPEND);  // 最简单的快速的以追加的方式写入写入方法，
			echo 'fail';
		}
		
	}
	public function sendMessage(){
		$order_id = $_POST['order_id'];
		$form_id = $_POST['form_id'];
		if(!$order_id){
			echo json_encode(array('status'=>0,err=>'无效订单'));exit;
		}
		@session_start();
		//发送模板消息
		if($_SESSION['access_token']){
			$access_token = $_SESSION['access_token'];
			
		}else{
			$result = $this->get_access_token(C('weixin'));
			$res = json_decode($result,true);
			if($res){
				session('access_token',$res['access_token'],7100);
				$access_token = $_SESSION['access_token'];
			}
			
		}
		$ar = M('order')->where("id = $order_id")->find();
		$arr = M('order_product')->where("order_id = $ar[id]")->select();
		$ar['name'] = '';
		foreach($arr as $val){
			$ar['name'] .= $val['name'];
		}
		
		$this->send_msg($access_token,$ar,$form_id);
	}
	public function get_access_token($wx_config){
			
		$appid = $wx_config['appid'];
		$secret = $wx_config['secret'];
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
		return $data = $this->curl_get($url);
			
			
	}

	public function curl_get($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		return $data;
	}
	public function send_msg($access_token,$ar,$form_id){
		$data = $ar;
		$info = M('program')->where('id = 1')->find();
		$access_token = $access_token;
		$touser = $info['openid'];
		$template_id = 'qSDa6dlla2gxEMCSPk5EnfCZwmjL1JdNRiezGWZqiVg';
		$page = '';
		$form_id = $form_id;
		$value = array(
			"keyword1"=>array(
			"value"=>$data['order_sn'],
			 
			),
			"keyword2"=>array(
				"value"=>$data['amount'],
				
			),
			"keyword3"=>array(
				"value"=>$data['name'],
				
			),
			"keyword4"=>array(
				"value"=>date('Y-m-d H:i:s',$data['pay_time']),
				
			),
			"keyword5"=>array(
				"value"=>$data['receiver'],
				
			),
			"keyword6"=>array(
				"value"=>$data['tel'],
				
			),
			"keyword7"=>array(
				"value"=>$data['address_xq'],
				
			),
			"keyword8"=>array(
				"value"=>$data['remark'],
				
			)
		);
		
		$url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
		$dd = array();
		$dd['touser']=$touser;
		$dd['template_id']=$template_id;
		$dd['page']=$page;  //点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,该字段不填则模板无跳转。
		$dd['form_id']=$form_id;
		
		$dd['data']=$value;                        //模板内容，不填则下发空模板
		
		/* curl_post()进行POST方式调用api： api.weixin.qq.com*/
		$result = $this->https_curl_json($url,$dd,'json');
		if($result){
			echo json_encode(array('state'=>1,'msg'=>$result));
		}else{
			echo json_encode(array('state'=>0,'msg'=>$result));
		}
    }
    /* 发送json格式的数据，到api接口 -xzz0704  */
    function https_curl_json($url,$data,$type){
        if($type=='json'){//json $_POST=json_decode(file_get_contents('php://input'), TRUE);
            $headers = array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache");
            $data=json_encode($data);
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
        $output = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl);
        return $output;
    }
	//***************************
	//  订单处理 接口
	//***************************
	public function orderhandle($data){
		$order_sn = trim($data['order_sn']);
		$pay_type = trim($data['pay_type']);
		$trade_no = trim($data['trade_no']);
		$total_fee = floatval($data['total_fee']);
		$check_info = M('order')->where('order_sn="'.$order_sn.'"')->find();
		if (!$check_info) {
			return "订单信息错误...";
		}

		if ($check_info['status']<10 || $check_info['back']>'0') {
			return "订单异常...";
		}

		if ($check_info['status']>10) {
			return array('status'=>1,'data'=>$data);
		}

		$up = array();
		$up['type'] = $pay_type;
		$up['price_h'] = sprintf("%.2f",floatval($total_fee/100));
		$up['status'] = 20;
		$up['trade_no'] = $trade_no;
		$up['pay_time'] = time();
		$res = M('order')->where('order_sn="'.$order_sn.'"')->save($up);
		if ($res) {
			//处理优惠券
			/*if (intval($check_info['vid'])) {
				$vou_info = M('user_voucher')->where('uid='.intval($check_info['uid']).' AND vid='.intval($check_info['vid']))->find();
				if (intval($vou_info['status'])==1) {
					M('user_voucher')->where('id='.intval($vou_info['id']))->save(array('status'=>2));
				}
			}*/
			return array('status'=>1,'data'=>$data);
		}else{
			return '订单处理失败...';
		}
	}

	//构建字符串
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
}
?>