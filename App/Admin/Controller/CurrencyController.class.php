<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class CurrencyController extends AdminController
{

    public function index(){
        $model = M ('currency' );
        $string = I('string');

        $where = array();
        if($string){
            $where["_string"] = "currency_name like %$string% OR currency_en like %$string%" ;
        }

        // 查询满足要求的总记录数
        $count = $model->where ( $where )->count ();
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new \Think\Page ( $count, 20 );
        //将分页（点击下一页）需要的条件保存住，带在分页中
        // 分页显示输出
        $show = $Page->show ();
        //需要的数据
        $field = "*";
        $info = $model->field ( $field )
            ->where ( $where )
            ->order ("sort asc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();

        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    public function del(){
        if(empty($_POST['id'])){
            $info['status'] = -1;
            $info['info'] ='传入参数有误';
            $this->ajaxReturn($info);
        }

        $id = I('post.id','','intval');
        $model = I('post.model','','');
        $rs=M('Currency_user')->where('currency_id='.$id)->sum("num");

        if($rs>0){
            $this->error('该币种尚有用户持有，不能删除');
        }
        $r = M($model)->delete($id);

        if(!$r){
            $info['status'] = 0;
            $info['info'] ='删除失败';
            $this->ajaxReturn($info);
        }
        $info['status'] = 1;
        $info['info'] ='删除成功';
        $this->ajaxReturn($info);
    }

    public function edit(){
        $db = M("currency");
        if(IS_POST){
            $id = I('post.currency_id','','');

            if($save_data = $db->create()){
                if($_FILES["Filedata"]["tmp_name"]){
                    $currency_logo  = $this->upload($_FILES["Filedata"]);
                }else{
                    $currency_logo = I('currency_logo');
                }
                $save_data['currency_logo'] = $currency_logo;
                $save_data['create_time'] = time();
                if($id){ /*编辑*/
                    $res = $db->where(array('currency_id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $res = $db->add($save_data);
                }
                if($res){
                    $this->success('操作成功',U('index'));
                }else{
                    $this->error('操作失败');
                }
            }

        }else{
            $currency_id = I('get.currency_id');
            $res = $db->where(array('currency_id'=>$currency_id))->find();
            $this->assign('info',$res);
            $this->display();
        }
    }
    /**
     * 给某个用户钱包转账
     */
    public function  set_member_currencyForQianbao(){
        $cuid=intval(I("cuid"));
        if(empty($cuid)){
            $this->error("无效货币参数",U("Currency/index"));exit();
        }
        $currency=M("Currency")->where("currency_id='$cuid'")->find();

        $currency['balance']=$this->get_qianbao_balance($currency);

        if(empty($currency)){
            $this->error("无效货币",U("Currency/index"));exit();
        }
        if(IS_POST){

            $admin=M("Admin")->where("admin_id='{$_SESSION['admin_userid']}'")->find();
            if(empty($_POST['password'])){
                $this->error("请输入管理员密码");
            }
            if(md5($_POST['password'])!=$admin['password']){
                $this->error("您输入的管理员密码错误");
            }

            $phone=I("phone");//用户名
            $num=I('num');//数量
            if(empty($phone)){
                $this->error("请输入用户名手机号");exit();
            }
            if(empty($num)||!is_numeric($num)){
                $this->error("数量请输入数字类型");exit();
            }
            $member=M("Member")->where("phone='$phone'")->find();
            if(empty($member)){
                $this->error("查无此人，请核实");exit();
            }

            $qa=M("Qianbao_address")->where("user_id='{$member['member_id']}' and currency_id='{$cuid}'")->find();
            if(empty($qa['qianbao_url'])){
                $this->error("此用户没有绑定提币地址，无法转账");exit();
            }
            //判断看这个钱包地址是否是真实地址
            if(!$this->check_qianbao_address($qa['qianbao_url'],$currency)){
                $this->error("提币地址不是一个有效地址");exit();
            }
            $num=floatval($num);
            $data['fee']=0;//手续费
            $data['currency_id']=$cuid;
            $data['user_id']=$qa['user_id'];
            $data['url']=$qa['qianbao_url'];
            $data['name']=$qa['name'];
            $data['num']=$num;
            $data['actual']=$num;//实际到账价格
            $data['status']=0;
            $data['add_time']=time();

            $tibi=$this->qianbao_tibi($qa['qianbao_url'],$num,$currency);//提币程序

            if($tibi){//成功写入数据库
                $data['ti_id']=$tibi;
                $re=M("Tibi")->add($data);
                //减钱操作
//     			M("Currency_user")->where("member_id='{$_SESSION['USER_KEY_ID']}' and currency_id='$cuid'")->setDec("num",$num);
                $this->success("转账成功，请耐心等待",U('Currency/index'));exit();

            }else{//失败提示
                $this->error("转账失败");exit();
            }
        }

        $this->assign("currency",$currency);
        $this->display();
    }

    /**
     * 检测地址是否是有效地址
     *
     * @return boolean 如果成功返回个true
     * @return boolean 如果失败返回个false；
     *  @param unknown $url
     *  @param $port_number 端口号 来区分不同的钱包
     */

    private function check_qianbao_address($url,$currency){
        require_once 'App/Common/Common/easybitcoin.php';
        $bitcoin = new \Bitcoin($currency['rpc_user'],$currency['rpc_pwd'],$currency['rpc_url'],$currency['port_number']);
        $address = $bitcoin->validateaddress($url);
        if($address['isvalid']){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 提币引用的方法
     * @param unknown $url 钱包地址
     * @param unknown $money 提币数量
     */
    private function qianbao_tibi($url,$money,$currency){
        require_once 'App/Common/Common/easybitcoin.php';
        $bitcoin = new \Bitcoin($currency['rpc_user'],$currency['rpc_pwd'],$currency['rpc_url'],$currency['port_number']);
        $bitcoin->walletlock();//强制上锁
        $bitcoin->walletpassphrase($currency['qianbao_key'],20);
        $id=$bitcoin->sendtoaddress($url,$money);
        $bitcoin->walletlock();
        return $id;
    }
}