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
    /**/
    public function config(){
        $model = M ('exchange_conf' );
        $type = I('type');

        $where = array();
        if($type){
            $where["type"] = $type ;
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
            ->order ("id desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($info as &$value){
            $value['dh_cur_name'] = D("currency")->get_cur_name($value['dh_cur_id']);
            $value['xh_cur_name'] = D("currency")->get_cur_name($value['xh_cur_id']);
        }
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    public function config_edit(){
        $db = M("exchange_conf");
        if(IS_POST){
            $id = I('post.id','','');

            if($save_data = $db->create()){
                $save_data['op_time'] = time();
                if($id){ /*编辑*/
                    $res = $db->where(array('id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $res = $db->add($save_data);
                }
                if($res){
                    $this->success('操作成功',U('config#14#0'));
                }else{
                    $this->error('操作失败');
                }
            }
        }else{
            $id = I('get.id');
            $res = $db->where(array('id'=>$id))->find();
            $this->assign('info',$res);
            $currency_list = M('currency')->where(array('status'=>1))->select();
            $this->assign ('cur_list', $currency_list );
            $this->display();
        }
    }
    /*兑换*/
    public function record(){
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