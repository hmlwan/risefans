<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class TaskController extends AdminController
{
    /*配置*/
    public function signconfig(){
        $model = M ('sign_conf' );
        if(IS_POST){
            $id = I('post.id');

            if($data = $model->create()){

                $data['op_time'] = time();
                if($id){
                    $res = $model->where(array('id'=>$id))->save($data);
                }else{
                    $res = $model->add($data);
                }
                if(!$res){
                    $this->error('提交失败');
                }
                $this->success('操作成功',U('/Admin/Task/signconfig#5#0'));
            }else{
                $this->error('提交失败');
            }
        }else{

            $info = $model->where("1=1")->find();
            $this->assign ('info', $info );

            $currency_list = M('currency')->where(array('status'=>1))->select();
            $this->assign ('cur_list', $currency_list );
            $this->display ();
        }
    }
    /*每日签到领奖记录*/
    public function daily_luckdraw(){
        $model = M ('daily_luckdraw' );
        $ad_title = I('ad_title');

        $where = array(
        );
        if($ad_title){
            $where["ad_title"] = $ad_title ;
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
        $list = $model->field ( $field )
            ->where ( $where )
            ->order ("add_time desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($list as &$v){
            $v["phone"] = M('member')->where(array('member_id'=>$v['member_id']))->getField('phone');
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    /*领取抽奖数记录*/
    public function task_luckdraw_record(){
        $model = M ('task_luckdraw_record' );
        $ad_title = I('ad_title');

        $where = array(
            'stype' => 2
        );
        if($ad_title){
            $where["ad_title"] = $ad_title ;
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
        $list = $model->field ( $field )
            ->where ( $where )
            ->order ("id desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($list as &$v){
            $v["phone"] = M('member')->where(array('member_id'=>$v['member_id']))->getField('phone');
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

}