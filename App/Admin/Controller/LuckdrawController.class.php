<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class LuckdrawController extends AdminController
{

    public function index(){
        $model = M ('luckdraw_conf' );
        $string = I('string');

        $where = array();
        if($string){
            $where["_string"] = "title like %$string% OR id like %$string%" ;
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
            ->order ("id asc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        foreach ($info as &$value){
            $value['currency_name'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_name');
        }
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
        $r = M($model)->delete($id);

        if(!$r){
            $info['status'] = 0;
            $info['info'] ='删除失败';
            $this->ajaxReturn($info);
        }
        /*删除奖励数值*/
        M("luckdraw_conf_detail")->where(array('luckdraw_id'=>$id))->delete();
        $info['status'] = 1;
        $info['info'] ='删除成功';
        $this->ajaxReturn($info);
    }

    public function edit(){
        $db = M("luckdraw_conf");
        if(IS_POST){
            $id = I('post.id','','');

            if($save_data = $db->create()){
                $save_data['add_time'] = time();
                if($id){ /*编辑*/
                    $res = $db->where(array('id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $is_exist = $db->where(array('title'=>I('post.title')))->find();
                    if($is_exist){
                        $this->error('存在规则名称重复');
                    }
                    $res = $db->add($save_data);
                }
                if($res){
                    $this->success('操作成功',U('index'));
                }else{
                    $this->error('操作失败');
                }
            }
        }else{
            $id = I('get.id');
            $res = $db->where(array('id'=>$id))->find();
            $currency_list = M('currency')->where(array('status'=>1))->select();
            $this->assign ('cur_list', $currency_list );
            $this->assign('info',$res);
            $this->display();
        }
    }
    public function detail(){
        $model = M ('luckdraw_conf_detail' );
        $string = I('string');
        $luckdraw_id = I('luckdraw_id');
        $where = array();
        if($string){
            $where["_string"] = "title like %$string% OR id like %$string%" ;
        }
        $where['luckdraw_id'] =  $luckdraw_id;
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
            ->order ("id asc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        $this->assign('luckdraw_id',$luckdraw_id);
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    public function detailedit(){
        $db = M("luckdraw_conf_detail");
        if(IS_POST){
            $id = I('post.id','','');
            $luckdraw_id = I('post.luckdraw_id','','');

            if($save_data = $db->create()){
                if($id){ /*编辑*/
                    $res = $db->where(array('id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $res = $db->add($save_data);
                }
                if($res){
                    $this->success('操作成功',U('detail',array('luckdraw_id'=>$luckdraw_id)));
                }else{
                    $this->error('操作失败');
                }
            }
        }else{
            $id = I('get.id');
            $luckdraw_id = I('get.luckdraw_id');
            $res = $db->where(array('id'=>$id))->find();
            $this->assign('info',$res);
            $this->assign('luckdraw_id',$luckdraw_id);
            $this->display();
        }
    }
    public function mulitadd(){
        $db = M("luckdraw_conf_detail");
        if(IS_POST){
            $luckdraw_id = I('post.luckdraw_id','','');
            $mulit_num = trim(I('post.mulit_num'));
            if(!$mulit_num){
                $this->error('请输入奖励数值');
            }
            $mulit_num = array_filter(explode("+",$mulit_num));
            foreach ($mulit_num as $v){
                $db->add(array(
                    'num' => $v,
                    'luckdraw_id' =>$luckdraw_id,
                ));
            }
            $this->success('操作成功',U('detail',array('luckdraw_id'=>$luckdraw_id)));
        }else{
            $luckdraw_id = I('get.luckdraw_id');

            $this->assign('luckdraw_id',$luckdraw_id);
            $this->display();
        }
    }
    public function detaildel(){
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