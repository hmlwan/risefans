<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class ExchangeController extends AdminController
{
    /*兑换配置*/
    public function config(){
        $model = M ('exhange_config' );
        if(IS_POST){
            $id = I('post.id');

            if($data = $model->create()){
                $num_val = I('num_val');
                if($num_val){
                    asort($num_val);
                    $num_val = array_filter(array_unique($num_val));
                    $data['num_json'] = json_encode($num_val);
                }
                $data['op_time'] = time();
                if($id){
                    $res = $model->where(array('id'=>$id))->save($data);
                }else{
                    $res = $model->add($data);
                }
                if(!$res){
                    $this->error('提交失败');
                }
                $this->success('操作成功',U('config'));
            }else{
                $this->success('提交失败');
            }
        }else{

            $info = $model->where("1=1")->find();
            $info["num_json"] = json_decode($info["num_json"],true);
            $this->assign ('info', $info );
            /*币种*/
            $cur_list = M('currency')->where(array('is_lock'=>0))->field("currency_id,currency_name")->select();
            $this->assign ('cur_list', $cur_list );
            $this->display ();
        }
    }

    /*管理发布*/
    public function pub(){
        $model = M ('exchange_pub' );
        $phone = I('phone');
        $type = I('type');

        $where = array();
        if($phone){
            $where["phone"] = $phone;
        }
        if($type){
            $where["type"] = $type;
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
            ->order ("add_time desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($info as &$value){
            if($value['status'] == 1){
                if($this->ex_config['invalid_time'] > 0){
                    $time =  $this->ex_config['invalid_time'] * 3600 + $value['add_time'];
                    if($time < time()){
                        $value['status'] = 7;
                    }
                }
            }
            $value['currency_name'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    /*发布下架*/
    public function down(){
        $db = M ('exchange_pub' );
        $id = I('id');

        $info = $db->where(array('id'=>$id))->find();
        if($id){
            $res = $db->where(array('id'=>$id))->save(array('status'=>3));
            if($res){
                //卖单返币
                if($info['type'] == 2){
                    M("currency_user")->where(array('member_id'=>$info['member_id'],'currency_id'=>$info['currency_id']))->setInc('num',$info['num']);
                }
                $info['status'] = 1;
                $info['info'] ='下架成功';
                $this->ajaxReturn($info);
            }else{
                $info['status'] = 0;
                $info['info'] ='下架失败';
                $this->ajaxReturn($info);
            }
        }else{
            $info['status'] = 0;
            $info['info'] ='下架失败';
            $this->ajaxReturn($info);
        }



    }
    /*订单交易*/
    public function order(){
        $model = M ('exchange_order' );

        $phone = I('phone');
        $type = I('type');

        $where = array();
        if($phone){
            $where["phone"] = $phone;
        }
        if($type){
            $where["type"] = $type;
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
            ->order ("add_time desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($info as &$value){
            $value['currency_name'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    /*投诉/申诉判定*/
    public function judge(){
        $this->display ();
    }

    /*冻结账号*/
    public function freeze_account(){
        $model = M ('exchange_freeze' );
        $phone = I('phone');

        $where = array();
        if($phone){
            $where["phone"] = $phone ;
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
            ->order ("freeze_time desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    public function freeze_edit(){
        $db = M("exchange_freeze");
        if(IS_POST){
            $id = I('post.id','','');
            $phone = I('phone');
            if($save_data = $db->create()){
                $save_data['freeze_time'] = time();
                if($id){ /*编辑*/
                    $res = $db->where(array('id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $is_exist = M('member')->where(array('phone'=>$phone))->find();
                     if(!$is_exist){
                         $this->error('该手机号不存在');
                     }
                    $save_data['member_id'] = $is_exist['member_id'];
                    $res = $db->add($save_data);
                }
                if($res){
                    $this->success('操作成功',U('freeze_account#11#3'));
                }else{
                    $this->error('操作失败');
                }
            }

        }else{
            $id = I('get.id');
            $res = $db->where(array('id'=>$id))->find();
            $this->assign('info',$res);
            $this->display();
        }
    }
    public function del(){
        if(empty($_POST['id'])){
            $info['status'] = -1;
            $info['info'] ='传入参数有误';
            $this->ajaxReturn($info);
        }

        $id = I('post.id','','intval');
        $model = I('post.model','','');
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

}