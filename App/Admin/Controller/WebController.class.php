<?php
/*
 * 后台理财管理
 */
namespace Admin\Controller;
use Admin\Controller\AdminController;
class WebController extends AdminController {
    // 空操作
    public function _empty() {
        header ( "HTTP/1.0 404 Not Found" );
        $this->display ( 'Public:404' );
    }
    /*收款方式*/
    public function receipt() {
        $model = M ('receipt_type' );
        $string = I('string');

        $where = array();
        if($string){
            $where["bank_name"] = array('like','%'.$string.'%') ;
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

        $this->assign ('info', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    public function receipt_add(){
        $model= D('receipt_type');
        if(IS_POST){
            $id = I("post.id");
            if($_FILES["logo_img"]["tmp_name"]){
                $logo_img  = $this->upload($_FILES["logo_img"]);
            }else{
                $logo_img = I('logo_img1');
            }
            if($r = $model->create()){
                $r["logo_img"] =  $logo_img;
                $r["op_time"] =  time();
                if($id){
                    $res = $model->where(array('id'=>$id))->save($r);
                }else{
                    $res = $model->add($r);
                }
                if($res){
                    $this->success('操作成功',U('receipt'));
                    return;
                }else{
                    $this->error('服务器繁忙,请稍后重试');
                    return;
                }
            }else{
                $this->error($model->getError());
                return;
            }
        }else{
            $id = I("get.id");
            $info = $model->where(array('id'=>$id))->find();
            $this->assign('info',$info);
            $this->display();
        }
    }

    /*游戏类目*/
    public function game() {
        $model = M ('game_list' );
        $string = I('string');

        $where = array();
        if($string){
            $where["bank_name"] = array('like','%'.$string.'%') ;
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

        $this->assign ('info', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }


    public function game_add(){
        $model= D('game_list');
        if(IS_POST){
            $id = I("post.id");
            if($_FILES["pic"]["tmp_name"]){
                $logo_img  = $this->upload($_FILES["pic"]);
            }else{
                $logo_img = I('pic1');
            }
            if($r = $model->create()){
                $r["pic"] =  $logo_img;
                if($id){
                    $res = $model->where(array('id'=>$id))->save($r);
                }else{
                    $res = $model->add($r);
                }
                if($res){
                    $this->success('操作成功',U('game'));
                    return;
                }else{
                    $this->error('服务器繁忙,请稍后重试');
                    return;
                }
            }else{
                $this->error($model->getError());
                return;
            }
        }else{
            $id = I("get.id");
            $info = $model->where(array('id'=>$id))->find();
            $this->assign('info',$info);
            $this->display();
        }
    }

    /*用户银行卡*/
    public function bank() {
        $model = M ('member_info' );
        $string = I('string');

        $where = array();
        if($string){
            $where["bank_name"] = array('like','%'.$string.'%') ;
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
            $value['member_name'] = M('member')->where(array('id'=>$value['member_id']))->getField('username');
        }

        $this->assign ('info', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    public function bank_edit(){
        $model = M("member_info");
        if(IS_POST){
            $id = I("post.id");
            if($r = $model->create()){
                if(!I("post.tk_pwd")){
                    unset($r['tk_pwd']);
                }else{
                    $r['tk_pwd'] =  ($r['tk_pwd']);
                }
                if($id){
                    $res = $model->where(array('id'=>$id))->save($r);
                }else{
                    $res = $model->add($r);
                }
                if($res){
                    $this->success('操作成功',U('bank'));
                    return;
                }else{
                    $this->error('服务器繁忙,请稍后重试');
                    return;
                }
            }else{
                $this->error($model->getError());
                return;
            }
        }else{
            $id = I("get.id");
            $info = $model->where(array('id'=>$id))->find();
            $this->assign('info',$info);
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