<?php
namespace Mobile\Controller;

use Common\Controller\CommonController;
class AjaxController extends CommonController {
    /**
     * 注册用户
     */
    public function addReg(){

        //增加添加时间,IP
		$data['agree'] = 1;
		$data['reg_time'] = time();
		$data['ip'] = get_client_ip();
		$data['username'] = I('post.username');
		$data['pwd'] = md5(I('post.pwd'));
		$data['email'] = I('post.email');

		if(empty($data['username']) || empty($data['pwd'])){
            $msg['status']=2;
            $msg['info']="用户名和密码不能为空！";
            $this->ajaxReturn($msg);
		}

		$member = M('Member');

		$u = $member->where(array('username'=>$data['username']))->find();
		if($u){
           $msg['status'] = 2;
           $msg['info'] = '帐号己存在！';
           $this->ajaxReturn($msg);
		}

		$e = $member->where(array('email'=>$data['email']))->find();
		if($u){
            $msg['status'] = 2;
            $msg['info'] = '邮箱己存在！';
            $this->ajaxReturn($msg);
		}

        $r = $member->add($data);

        if($r){
            $msg['status'] = 1;
            $msg['info'] = '提交成功';
            $this->ajaxReturn($msg);
        }else{
            $msg['status'] = 2;
            $msg['info'] = '服务器繁忙,请稍后重试';
            $this->ajaxReturn($msg);
        }
    }

    /**
     * 忘记密码
     */
    public function findpwd(){

        if(empty($_POST['email'])){
           $msg['status']=2;
           $msg['info']="请填写邮箱";
           $this->ajaxReturn($data);
        }
        if(!checkEmail($_POST['email'])){
           $msg['status']=2;
           $msg['info']="请输入正确的邮箱";
           $this->ajaxReturn($data);
        }
        if(empty($_POST['captcha'])){
           $msg['status']=2;
           $msg['info']="请填写验证码";
           $this->ajaxReturn($data);
        }
        $verify = new Verify();
        if(!$verify->check($_POST['captcha'])){
           $msg['status']=2;
           $msg['info']="验证码输入错误";
           $this->ajaxReturn($data);
        }
        $info = M('Member')->where(array('email'=>$_POST['email']))->find();
        if($info==false){
            $msg['status']=2;
            $msg['info']="用户不存在";
            $this->ajaxReturn($data);
        }
        $token = strtoupper(md5($_POST['email']).md5(time()));//大写md5当前邮箱+当前时间
        $url = "http://".$_SERVER['SERVER_NAME'].U('Login/resetPwd',array('key'=>$token));
        $content = "<div>";
        $content.= "您好，<br><br>请点击链接：<br>";
        $content.= "<a target='_blank' href='{$url}' >重置您的密码</a>";
        $content.= "<br><br>如果链接无法点击，请复制并打开以下网址：<br>";
        $content.= "<a target='_blank' href='{$url}' >{$url}</a>";
        $r = setPostEmail($this->config['EMAIL_HOST'],$this->config['EMAIL_USERNAME'],$this->config['EMAIL_PASSWORD'],$this->config['name'].'团队',$_POST['email'],$this->config['name'].'团队[密码找回]',$content);
	    if($r){
			$msg['status']=2;
			$msg['info']="服务器繁忙,请稍后重试";
			$this->ajaxReturn($data);
        }
        $member_id = $info['member_id'];
        $data['member_id'] = $info['member_id'];
        $data['token'] = $token;
        $data['add_time'] = time();
        if(M('Findpwd')->add($data)){
            $msg['status']=1;
            $msg['info']="邮箱已发送";
            $this->ajaxReturn($msg);
        }else{
            $msg['status']=2;
            $msg['info']="服务器繁忙,请稍后重试";
            $this->ajaxReturn($msg);
        }
    }

    /**
     * 处理登录请求
     * 全部用ajax提交
     */
    public function checkLog(){
        $username = I('post.username');
        $pwd = md5(I('post.pwd'));

		if(empty($username) || empty($pwd)){
            $msg['status']=2;
            $msg['info']="用户名和密码不能为空！";
            $this->ajaxReturn($msg);
		}

        $M_member = D('Member');
        //再次判断
//         if((checkEmail($email) || checkMobile($email))==false){
//             $msg['status']=2;
//             $msg['info']="请输入正确的手机或者邮箱";
//             $this->ajaxReturn($data);
//         }
        //判断传值是手机还是email
        $info = $M_member->logCheckUsername($username);
        if($info['status']==2){
            $msg['status']=2;
            $msg['info']="非常抱歉您的账号已被禁用";
            $this->ajaxReturn($msg);
        }
        //验证手机或邮箱
        if($info==false){
            $msg['status']=2;
            $msg['info']="用户名不存在";
            $this->ajaxReturn($msg);
        }
        //验证密码
        if($info['pwd']!=$pwd){
            //$this->error('密码输入错误');
            $msg['status']=2;
            $msg['info']="密码输入错误";
            $this->ajaxReturn($msg);
        }
        //获取下方能用到的参数
//         $new_ip = get_client_ip();
        $old_login_ip = $info['login_ip']?$info['login_ip']:$info['ip'];
        $card = I('post.year').I('post.month').I('post.day');
        $idcard = substr($info['idcard'],6,8);
        //验证身份信息如果身份证存在并且 当前IP 和上次登录Ip不一样
//         if($old_login_ip != $new_ip && $info['idcard'] ){
//             if($card != $idcard){
//                 $msg['status']=2;
//                 $msg['info']="生日与您当前填写不符";
//                 $this->ajaxReturn($data);
//             }
//         }
//        $this->pullMessage($info['member_id'],$info['login_time']?$info['login_time']:$info['reg_time'] );
//         if($this->pullMessage($info['member_id'],$info['login_time']?$info['login_time']:$info['reg_time'])==false){
//             $msg['status']=2;
//             $msg['info']="服务器繁忙,请稍后重试12";
//             $this->ajaxReturn($data);
//         }
        //如果当前操作Ip和上次不同更新登录IP以及登录时间
        $data['login_ip'] = $new_ip;
        $data['login_time']= time();
        $where['member_id'] = $info['member_id'];
        $r = $M_member->where($where)->save($data);
        if($r===false){
            $msg['status']=2;
            $msg['info']="服务器繁忙,请稍后重试";
            $this->ajaxReturn($msg);
        }

        $msg['status']=1;
		$msg['member_id']=$info['member_id'];
		$msg['gold']=$info['gold'];
        $msg['info']="登录成功";
        $this->ajaxReturn($msg);
    }

    /**
     * ajax验证邮箱
     * @param string $email 规定传参数的结构
     *
     */
    public function ajaxCheckEmail($email){
        $email = urldecode($email);
        $data = array();
        if(!checkEmail($email)){
            $msg['status'] = 0;
            $msg['msg'] = "邮箱格式错误";
        }else{
            $M_member = M('Member');
            $where['email']  = $email;
            $r = $M_member->where($where)->find();
            if($r){
                $msg['status'] = 0;
                $msg['msg'] = "邮箱已存在";
            }else{
                $msg['status'] = 1;
                $msg['msg'] = "";
            }
        }
        $this->ajaxReturn($msg);
    }

	//记录游戏
	public function RankingAdd(){

		$data['addtime'] = time();
		$data['ip'] = get_client_ip();
		$data['member_id'] = I('post.member_id');
		$data['score'] = I('post.score');

		if(empty($data['member_id']) || empty($data['score'])){
            $msg['status']=2;
            $msg['info']="未登录或分数为空！";
            $this->ajaxReturn($msg);
		}

        $game = M('Game_integral');

		$r = $game->add($data);
		if($r){
			$gold = I('post.gold');

			M('Member')->where("member_id='".$data['member_id']."'")->setInc("gold",$gold);

			$msg['status']=1;
			$msg['info']="提交成功";
			$this->ajaxReturn($msg);
		}else{
			$msg['status'] = 2;
			$msg['info'] = '提交失败';
			$this->ajaxReturn($msg);
		}
	}

	//查询游戏
	public function RankingList(){
		$game = M('Game_integral');
/*
		$where[C("DB_PREFIX")."game_integral.addtime >= ".strtotime(date('Y-m-d'))." AND ".C("DB_PREFIX")."game_integral.addtime <= ".time()];
		$info = $game->join(C("DB_PREFIX").'member ON '.C("DB_PREFIX").'game_integral.member_id = '.C("DB_PREFIX").'member.member_id');
		$info = $game->field('DISTINCT '.C("DB_PREFIX").'member.member_id,'.C("DB_PREFIX").'game_integral.id,'.C("DB_PREFIX").'game_integral.addtime,'.C("DB_PREFIX").'game_integral.score,'.C("DB_PREFIX").'member.username,'.C("DB_PREFIX").'member.head')
						->where($where)
						//->group(C("DB_PREFIX").'game_integral.member_id')
						->order(C("DB_PREFIX").'game_integral.score DESC,'.C("DB_PREFIX").'game_integral.id DESC')
						->limit(0,50)
						->select();
*/
		$info = $game->query("SELECT g.* FROM (SELECT a.id,a.member_id,a.score,a.addtime,b.head,b.username,b.nick FROM ".C("DB_PREFIX")."game_integral AS a LEFT JOIN ".C("DB_PREFIX")."member AS b ON a.member_id=b.member_id WHERE (b.nick IS NOT NULL OR b.username IS NOT NULL) ORDER BY a.score DESC,a.addtime DESC) AS g GROUP BY g.member_id ORDER BY g.score DESC,g.addtime DESC");//AND a.addtime >= ".strtotime(date('Y-m-d'))." AND a.addtime <= ".time()."
		if($info){
			$this->ajaxReturn($info);
		}else{
			$msg['status'] = 0;
			$msg['info'] = '暂无记录';
			$this->ajaxReturn($msg);
		}
	}

	//蓝币兑换体力值
	public function changeStrength(){
		$coins = array('100'=>10);
		$data['currency_mark'] = 'BEC';
		$data['member_id'] = I('request.member_id');
		$data['strength'] = I('request.strength');
		if(empty($data['member_id'])||empty($data['strength'])||empty($coins[$data['strength']])){
			$msg['status'] = 0;
			$msg['info'] = '参数错误';
			$this->ajaxReturn($msg);
		}

		$currency = M('currency')->query("SELECT a.num,a.forzen_num,a.chongzhi_url,a.member_id,b.currency_id,b.trade_currency_id,b.rpc_url,b.rpc_user,b.rpc_pwd,b.is_lock FROM ".C("DB_PREFIX")."currency_user AS a LEFT JOIN ".C("DB_PREFIX")."currency AS b ON a.currency_id=b.currency_id WHERE b.currency_mark='".$data['currency_mark']."' AND a.member_id=".$data['member_id']);

		if($currency[0]['is_lock']){
	       $msg['status'] = 2;
	       $msg['info']='该币种暂时不能交易';
	       $this->ajaxReturn($msg);
		}

		if($coins[$data['strength']] > $currency[0]['num']){
	       $msg['status'] = 2;
	       $msg['info']='该币数量不足本次兑换';
	       $this->ajaxReturn($msg);
		}
		$num = $coins[$data['strength']];

		$r = $this->setUserMoney($data['member_id'], $currency[0]['currency_id'], $num , 'dec','num');

		if($r){
			$datas = array(
				'member_id'=>$data['member_id'],
				'currency_id'=>$currency[0]['currency_id'],
				'currency_trade_id'=>$currency[0]['trade_currency_id'],
				'price'=>0,
				'num'=>$num,
				'fee'=>0,
				'money'=>0,
				'type'=>'change',
			);
			if (D('Trade')->create($datas)){
				if (D('Trade')->add()){
					$msg['status'] = 1;
					$msg['strength'] = $data['strength'];
					$msg['info']='操作成功!';

					$this->ajaxReturn($msg);
				}else{
					$msg['status'] = 2;
					$msg['info']='操作失败';
					$this->ajaxReturn($msg);
				}
			}
		}
	}


	public function wxIndex(){
	    $data = array(
            array(
                "img"=> '/images/pro_01.jpg',
                "title"=> '精英贷1',
                "cont"=>"22周岁即可\n最快3小时下款\n件均8万，最高20万"
            ),
            array(
                "img"=> '/images/pro_01.jpg',
                "title"=> '精英贷3',
                "cont"=>"22周岁即可\n最快3小时下款\n件均8万，最高20万"
            ),
            array(
                "img"=> '/images/pro_01.jpg',
                "title"=> '精英贷4',
                "cont"=>"22周岁即可\n最快3小时下款\n件均8万，最高20万"
            )
        );
        $msg['status'] = 0;
        $msg['info'] = '暂无记录';
        $msg['data'] = $data;
        $this->ajaxReturn($msg);
    }
    public function getDetail(){
	    $id = I('id');
        if($id == 0){
            $this->ajaxReturn("精英贷0");
        }elseif ($id == 1){
            $this->ajaxReturn("精英贷1");
        }elseif ($id == 2){
            $this->ajaxReturn("精英贷2");
        }else{
            $this->ajaxReturn("精英贷3");
        }
    }
}