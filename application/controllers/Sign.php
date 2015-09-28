<?php
defined('BASEPATH') or die('No direct script access allowed');

class Sign extends CI_Controller{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('sign_model');
		$this->load->helper('url_helper');
		$this->load->library('LB_base_lib');
		define('USER_NAME_EXISTS', -1);
		define('USER_EMAIL_EXISTS', -2);
	}
	public function index()
	{
     $this->load->view('sign/index.php');
	}
	//注册接口
	public function signup()
	{
		$username  = $this->input->post('username');
		$email     = $this->input->post('email');
		$password  = $this->input->post('password');
		$password2 = $this->input->post('password2');

		//验证数据是否合法
		if (!$this->check_username_formate($username))
		{
			$this->lb_base_lib->echo_json_result(-1,'username is illegal');
		}
		if (!$this->check_email_formate($email)) 
		{
			$this->lb_base_lib->echo_json_result(-1,'email is illegal');
		}
		if (!$this->check_password_formate($password)) 
		{
			$this->lb_base_lib->echo_json_result(-1,'password is illegal');
		}

		//检查用户名是否已经注册
		if ($this->sign_model->check_username_exists($username))
		{
			$this->lb_base_lib->echo_json_result(-1,"username is exists");
		}
		//检查邮箱是否已经注册
		if ($this->sign_model->check_email_exists($email))
		{
			$this->lb_base_lib->echo_json_result(-1,"email is exists");
		}
			
		//输入信息过滤
		$username = addslashes(trim($username));
		$email    = addslashes(trim($email));
		$password = addslashes(trim($password));
		$regip    = $this->lb_base_lib->real_ip();
		//用户信息写入数据库
		$result = $this->sign_model->add_user($username,$email,$password,$regip);
		$this->lb_base_lib->echo_json_result($result,"success");

	}
	/*
	*用户名和口令：
			正则表达式限制用户输入口令；
			密码加密保存 md5(md5(passwd+salt))；
			允许浏览器保存口令；
			口令在网上传输协议http;
	*用户登陆状态：因为http是无状态的协议，每次请求都是独立的， 所以这个协议无
			法记录用户访问状态，在多个页面跳转中如何知道用户登陆状态呢?
			那就要在每个页面都要对用户身份进行认证。实现这个功能用到的技术就是浏览器的
			cookie功能，把用户登陆信息放在客户端的cookie里;
	*使用cookie的一些原则：
		(1)cookie中保存用户名，登录序列，登录token;
				用户名：明文；
				登录序列：md5散列过的随机数,仅当强制用户输入口令时更新;
				登陆token：md5散列过的随机数，仅一个登陆session内有效，新的session会更新他。
		(2)上述三个东西会存放在服务器上，服务器会验证客户端cookie与服务器是否一致；
		(3)这样设计的效果
				(a)登录token是单实例，一个用户只能有一个登录实例；
				(b)登录序列用来做盗用行为检测
	*找回密码功能
		(1)不要使用安全问答
		(2)通过邮件自行重置。当用户申请招呼密码时，系统生成一个md5唯一的随机字串
		　放在数据库中，然后设置上时限，给用户发一个邮件，这个链接中包含那个md5
		　，用户通过点击那个链接来自己重置新的口令。
		(3)更好的做法多重认证。
  *口令探测防守
		(1)验证码
		(2)用户口令失败次数,并且增加尝试的时间成本
		(3)系统全局防守,比如系统每天5000次口令错误，就认为遭遇了攻击，
	  	然后增加所有用户输错口令的时间成本。
		(4)使用第三方的OAuth和OpenID
	*/

	//登陆接口
	public function signin()
	{
        $login_username = addslashes(trim($this->input->post('login_username')));
		$login_passwd   = addslashes(trim($this->input->post('login_passwd')));
		$user = $this->sign_model->get_user_by_username($login_username);

		if(empty($user))
		{
	      $this->lb_base_lib->echo_json_result(-1,"username or password was wrong");
		}

		$login_passwd = md5(md5($login_passwd).$user->salt);
		if ($login_passwd == $user->password)//登录成功
		{
			$last_signin_ip = $this->lb_base_lib->real_ip();
			$this->sign_model->update_signin($last_signin_ip,time(),$user->username);

		    $this->lb_base_lib->echo_json_result(1,"signin success");
		}
		else//登陆失败
		{
		    $this->lb_base_lib->echo_json_result(-1,"username or password was wrong");
		}

	}

	public function signout()
	{
		$result = $this->sign_model->signout();
	
	
	}
	public function change_passwd()
	{
	
	}
	//检查用户名格式
	public function check_username_formate($username)
	{
		return preg_match('/^[0-9A-Za-z_]{6,32}$/', $username);
	}
	//检查邮件格式
	public function check_email_formate($email)
	{
    return preg_match('/^([0-9A-Za-z]+)([0-9a-zA-Z_-]*)@([0-9A-Za-z]+).([A-Za-z]+)$/',$email);

	}
	//检查密码格式
	public function check_password_formate($password)
	{
    return preg_match('/[a-zA-Z]+/', $password) && preg_match('/[0-9]+/',$password) && preg_match('/[\s\S]{6,16}$/',$password);
	}



}
