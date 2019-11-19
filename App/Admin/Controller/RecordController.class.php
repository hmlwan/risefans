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
        $model = M ('rechage_record' );
        $phone = I('phone');

        $where = array();
        if($phone) {
            $where["m.phone"] = array('like', '%' . $phone . "%");
        }

        // 查询满足要求的总记录数
        $count = $model->where ( $where )->count ();
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
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    /*手动充值*/
    public function recharge_by_person(){
        $model = M ('rechage_record' );
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
        $model = M ('withdraw_record' );
        $phone = I('phone');

        $where = array();
        if($phone) {
            $where["m.phone"] = array('like', '%' . $phone . "%");
        }

        // 查询满足要求的总记录数
        $count = $model->where ( $where )->count ();
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
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();

    }

    /*手动充值*/
    public function tixian_by_person(){
        $model = M ('withdraw_record' );
        if(IS_POST){
            $id = I('post.id');

            if($data = $model->create()){
                $data['add_time'] = time();
                $res = $model->where(array('id'=>$id))->save($data);
                if(!$res){
                    $this->error('操作失败');
                }
                $this->success('操作成功',U('/Admin/Record/tixian#6#0'));
            }else{
                $this->error('操作失败');
            }
        }else{

            $info = $model->where("1=1")->find();
            $info["phone"] = D('member')->get_mem_phone($info['member_id']);
            $info["cur_name"] = D('currency')->get_cur_name($info['currency_id']);
            $this->assign ('info', $info );

            $currency_list = M('currency')->where(array('status'=>1))->select();
            $this->assign ('cur_list', $currency_list );
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
        $count = $model->where ( $where )->count ();
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


        $this->display ();

    }
}