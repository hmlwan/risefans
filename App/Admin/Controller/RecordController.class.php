<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class RecordController extends AdminController
{
    /*充值*/
    public function recharge(){
        $model = M ('tibi' );
        $phone = I('phone');

        $where = array(
            'd.status'=>array(
                'in',array(2,3,5)
            )
        );
        if($phone) {
            $where["m.phone"] = array('like', '%' . $phone . "%");
        }

        // 查询满足要求的总记录数
        $count = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.user_id')->where ( $where )->count ();
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new \Think\Page ( $count, 20 );
        //将分页（点击下一页）需要的条件保存住，带在分页中
        // 分页显示输出
        $show = $Page->show ();
        //需要的数据
        $field = "d.*,m.phone";
        $list = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.user_id')
            ->field ( $field )
            ->where ( $where )
            ->order ("d.add_time desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($list as &$v){
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    /*手动充值*/
    public function recharge_by_person(){
        $model = M ('tibi' );
        if(IS_POST){
            $id = I('post.id');

            if($data = $model->create()){
                $data['add_time'] = time();
                $data['status'] = 1;
                if($id){
                    $res = $model->where(array('id'=>$id))->save($data);
                }
                if(!$res){
                    $this->error('操作失败');
                }
                $this->success('操作成功',U('/Admin/Record/recharge#6#1'));
            }else{
                $this->error('操作失败');
            }
        }else{

            $info = $model->where("1=1")->find();
            $info["phone"] = D('member')->get_mem_phone($info['member_id']);
            $this->assign ('info', $info );

            $currency_list = M('currency')->where(array('status'=>1))->select();
            $this->assign ('cur_list', $currency_list );
            $this->display ();
        }
    }

    /*提现*/
    public function tixian(){
        $model = M ('tibi' );
        $phone = I('phone');

        $where = array(
            'd.status'=>array(
                'in',array(0,1,4)
            )
        );
        if($phone) {
            $where["m.phone"] = array('like', '%' . $phone . "%");
        }

        // 查询满足要求的总记录数
        $count = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.user_id')->where ( $where )->count ();
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new \Think\Page ( $count, 20 );
        //将分页（点击下一页）需要的条件保存住，带在分页中
        // 分页显示输出
        $show = $Page->show ();
        //需要的数据
        $field = "d.*,m.phone";
        $list = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.user_id')
            ->field ( $field )
            ->where ( $where )
            ->order ("d.add_time desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($list as &$v){
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();

    }

    /*手动充值*/
    public function tixian_by_person(){
        $model = M ('tibi' );
        $id = I('post.id');
        $info = $model->where(array('id'=>$id))->find();
        $member_id = $info['user_id'];
        $cuid = $info['currency_id'];
        $currency=M("Currency")->where("currency_id='$cuid'")->find();//这个是货币

        if(IS_POST){
            $is_agree = I('is_agree');
            if($is_agree == 2){
                D("currency")->mem_inc_cur($cuid,$info['num'],$member_id);
                $model->where(array('id'=>$id))->save(array('status'=>4));
                $this->success('操作成功');
            }
            //判断看这个钱包地址是否是真实地址
            if(!$this->check_qianbao_address($info['url'],$currency)){
                $this->error("提币地址不是一个有效地址");exit();
            }
            $tibi=$this->qianbao_tibi($info['url'],$info['actual'],$currency);
            if($tibi){//成功写入数据库
                $re=M("Tibi")->where(array('id'=>$id))->save(array('ti_id'=>$tibi));
                //减钱操作
                $this->success('操作成功');
            }else{//失败提示
                $this->error('操作失败');
            }
        }else{
            $info["phone"] = D('member')->get_mem_phone($info['member_id']);
            $info["cur_name"] = D('currency')->get_cur_name($info['currency_id']);
            $this->assign ('info', $info );
            $this->display ();
        }
    }

    /*兑换*/
    public function exchange(){
        $model = M ('exchange_order' );
        $phone = I('phone');

        $where = array();
        if($phone) {
            $where["m.phone"] = array('like', '%' . $phone . "%");
        }

        // 查询满足要求的总记录数
        $count = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.member_id')->where ( $where )->count ();
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new \Think\Page ( $count, 20 );
        //将分页（点击下一页）需要的条件保存住，带在分页中
        // 分页显示输出
        $show = $Page->show ();
        //需要的数据
        $field = "d.*,m.phone";
        $list = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.member_id')
            ->field ( $field )
            ->where ( $where )
            ->order ("d.add_time desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($list as &$v){
            $v["dh_cur_name"] = M('currency')->where(array('currency_id'=>$v['dh_cur_id']))->getField('currency_name');
            $v["xh_cur_name"] = M('currency')->where(array('currency_id'=>$v['xh_cur_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();

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
}