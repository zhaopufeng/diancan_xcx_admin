<?php
header("Content-Type:text/html; charset=utf-8");
//����ʱ��
date_default_timezone_set('PRC');
//����ʱ��
date_default_timezone_set('Asia/Shanghai');
// ���PHP����
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// ��������ģʽ ���鿪���׶ο��� ����׶�ע�ͻ�����Ϊfalse
define('BIND_MODULE', 'Admin');
define('APP_DEBUG',True);

// ����Ӧ������+Ŀ¼
define('APP_NAME','App');
define('APP_PATH','./App/');

// ����ThinkPHP����ļ�
require './ThinkPHP/ThinkPHP.php';