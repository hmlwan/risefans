<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class BonusController extends AdminController
{
    /*配置*/
    public function config(){
        $model = M ('bonus_config' );
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
                $this->success('操作成功',U('/Admin/Bonus/config#12#0'));
            }else{
                $this->success('提交失败');
            }
        }else{
            $info = $model->where("1=1")->find();
            $this->assign ('info', $info );
            /*抽奖配置*/
            $luckdraw_conf_list = M('luckdraw_conf')->where(array('status'=>1))->field("id,title")->select();
            $this->assign ('luckdraw_conf_list', $luckdraw_conf_list );
            /*币种*/
            $cur_list = M('currency')->where(array('is_lock'=>0))->field("currency_id,currency_name")->select();
            $this->assign ('cur_list', $cur_list );
            $this->display ();
        }
    }
    /*记录*/
    public function record(){

        $phone = I('phone');
        $cinfo = M ('bonus_config' )->where("1=1")->find();
        $where = array();
        if($phone){
            $where["m.phone"] = array('like','%'.$phone."%") ;
        }
        $model = M ('bonus_record' );
        // 查询满足要求的总记录数
        $count = $model->alias('r')
            ->join('left join blue_member as m on m.member_id=r.member_id')->where ( $where )->count();
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new \Think\Page ( $count, 20 );
        //将分页（点击下一页）需要的条件保存住，带在分页中
        // 分页显示输出
        $show = $Page->show ();
        //需要的数据
        $field = "r.*,m.phone";
        $info = $model->alias('r')
            ->join('left join blue_member as m on m.member_id=r.member_id')
            ->field ( $field )
            ->where ( $where )
            ->order ("r.id desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($info as &$value){
            $value['currency_name'] = D("currency")->get_cur_name($value['currency_id']);
            $value['num'] = number_format($value['num'],$cinfo['receive_decimal_num'],'.','');
        }
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
}