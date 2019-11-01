<?php
/*
 * 后台理财管理
 */
namespace Admin\Controller;
use Admin\Controller\AdminController;
class OrderController extends AdminController {
    // 空操作
    public function _empty() {
        header ( "HTTP/1.0 404 Not Found" );
        $this->display ( 'Public:404' );
    }
    /*订单记录*/
    public function index() {
        $model = M ('order' );
        $phone = I('phone');
        $good_name = I('good_name');

        $where = array();
        if($phone){
            $where["m.phone"] =  $phone;
        }
        if($good_name){
            $where["o.good_name"] = array('like','%'.$good_name.'%') ;
        }
        // 查询满足要求的总记录数
        $count = $model->alias('o')
            ->join('LEFT JOIN  '.C('DB_PREFIX').'member m ON o.member_id=m.member_id')
            ->where ( $where )
            ->count ();
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new \Think\Page ( $count, 20 );
        //将分页（点击下一页）需要的条件保存住，带在分页中
        // 分页显示输出
        $show = $Page->show ();
        //需要的数据
        $field = "o.*,m.phone";
        $info = $model->alias('o')
            ->field ( $field )
            ->where ( $where )
            ->join(' LEFT JOIN blue_member m ON o.member_id=m.member_id')
            ->order ("o.id desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();

        foreach ($info as &$value){
            $value['currency_name'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_name');
        }
        $this->assign ('info', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    /*退货操作*/
    public function return_order(){
        $model = M('order');
        if($_POST){
            $id = I('id');
            $remark = I('remark');
            if(!$remark){
                $this->error('请填写退货备注');
            }
            $r = $model->where(array('id'=>$id))->save(array('remark'=>$remark,'status'=>3));
            if($r){
                $info = $model->where(array('id'=>$id))->find();
                /*退回币种*/
                $res = M('currency_user')
                    ->where(array('currency_id'=>$info['currency_id'],'member_id'=>$info['member_id']))
                    ->setInc('num',$info['num']);
                if($res){
                    /*发送消息*/
                    $msn_data = array(
                        'title' => '兑换商品',
                        'member_id' => $info['member_id'],
                        'type' => 4,
                        'content' => '您兑换的商品<span style="color: #FF0000;">'.$info['good_name'].'</span>已退货，理由:'.$remark,
                        'add_time' => time(),
                        'is_read' => 0,
                    );
                    M('message')->add($msn_data);
                }

                $this->success('操作成功',U('index#5#0'));
            }else{
                $this->error('操作失败');
            }
        }else{
            $id = I('id');
            $info = $model->where(array('id'=>$id))->field('id,remark,delivery_no')->find();
            $this->assign('list',$info);
            $this->display();
        }
    }
    /*发货操作*/
    public function delivery_order(){
        $model = M('order');
        if($_POST){
            $id = I('id');
            $delivery_no = I('delivery_no');
            if(!$delivery_no){
                $this->error('请填写快递单号');
            }
            $r = $model->where(array('id'=>$id))->save(array('delivery_no'=>$delivery_no,'status'=>2));
            if($r){
                $info = $model->where(array('id'=>$id))->find();

                /*发送消息*/
                $msn_data = array(
                    'title' => '兑换商品',
                    'member_id' => $info['member_id'],
                    'type' => 4,
                    'content' => '您兑换的商品<span style="color: #FF0000;">'.$info['good_name'].'</span>已发货，快递单号:'.$delivery_no,
                    'add_time' => time(),
                    'is_read' => 0,
                );
                M('message')->add($msn_data);

                $this->success('操作成功',U('index#5#0'));
            }else{
                $this->error('操作失败');
            }
        }else{
            $id = I('id');
            $info = $model->where(array('id'=>$id))->field('id,remark,delivery_no')->find();
            $this->assign('list',$info);
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
?>