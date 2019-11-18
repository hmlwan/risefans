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
        $model = M ('bonus_conf' );
        if(IS_POST){
            $id = I('post.id');
            if($_FILES["bottom_img"]["tmp_name"]){
                $bottom_img  = $this->upload($_FILES["bottom_img"]);
            }else{
                $bottom_img = I('oldbottom_img');
            }
            if($data = $model->create()){

                $data['op_time'] = time();
                $data['bottom_img'] = $bottom_img;
                if($id){
                    $res = $model->where(array('id'=>$id))->save($data);
                }else{
                    $res = $model->add($data);
                }
                if(!$res){
                    $this->error('提交失败');
                }
                $this->success('操作成功',U('/Admin/Hongbao/config#4#0'));
            }else{
                $this->success('提交失败');
            }
        }else{

            $info = $model->where("1=1")->find();
            $this->assign ('info', $info );
            /*抽奖配置*/
            $luckdraw_conf_list = M('luckdraw_conf')->where(array('status'=>1))->field("id,title")->select();
            $this->assign ('luckdraw_conf_list', $luckdraw_conf_list );
            $this->display ();
        }
    }
    /**/
    public function adconfig(){
        $model = M ('hongbao_ad' );
        $ad_title = I('ad_title');

        $where = array();
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
        $info = $model->field ( $field )
            ->where ( $where )
            ->order ("id desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    public function adconfig_edit(){
        $db = M("hongbao_ad");
        if(IS_POST){
            $id = I('post.id','','');
            if($_FILES["ad_img"]["tmp_name"]){
                $ad_img  = $this->upload($_FILES["ad_img"]);
            }else{
                $ad_img = I('oldad_img');
            }
            if($save_data = $db->create()){
                $save_data['op_time'] = time();
                $save_data['ad_img'] = $ad_img;
                if($id){ /*编辑*/
                    $res = $db->where(array('id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $res = $db->add($save_data);
                }
                if($res){
                    $this->success('操作成功',U('adconfig#4#1'));
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
    public function ad_record(){
        $model = M ('hongbao_ad_record' );
        $ad_title = I('ad_title');

        $where = array();
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
            $v["ad_title"] = M('hongbao_ad')->where(array('id'=>$v['hongbao_ad_id']))->getField('ad_title');
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
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