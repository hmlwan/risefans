<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class GoodController extends AdminController
{

    public function index(){
        $model = M ('goods' );
        $string = I('string');
        $good_name = I('good_name');
        $good_id = I('get.id');
        $where = array();
//        if($string){
//            $where["_string"] = "good_name like %$string% OR good_title like %$string%" ;
//        }

        if($good_name){
            $where["good_name"] = array('like',"%".$good_name.'%');
        }
        if($good_id){
            $where["id"] = $good_id;
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
            ->order ("sort desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();

        foreach ($info as $k=>$value) {
            $spec_arr = json_decode($value['spec'], true);

            $info[$k]['spec_name'] = $spec_arr['spec_name'];
            $info[$k]['spec_val'] = implode(' ', $spec_arr['spec_val']);
            $info[$k]['currency_name'] = M('currency')->where(array('currency_id' => $value['currency_id']))->getField('currency_name');
            $info[$k]['type_name'] = M('goods_type')->where(array('id' => $value['type_id']))->getField('type_name');
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
        $info['status'] = 1;
        $info['info'] ='删除成功';
        $this->ajaxReturn($info);
    }

    public function edit(){
        $db = M("goods");
        if(IS_POST){
            $id = I('post.id','','');
            $spec_val = I('post.spec_val','','');
            $spec_name = I('post.spec_name','','');

            if($save_data = $db->create()){
                $spec = array();
                if($spec_name && $spec_val){
                    $spec = array(
                        'spec_name' => $spec_name,
                        'spec_val' => $spec_val
                    );
                }
                if($_FILES["logo"]["tmp_name"]){
                    $logo  = $this->upload($_FILES["logo"]);
                }else{
                    $logo = I('oldlogo');
                }
                $save_data['op_time'] = time();
                $save_data['logo'] = $logo;
                $save_data['introduce'] = stripslashes(htmlspecialchars_decode($_POST['introduce']));
                $save_data['spec'] = json_encode($spec,JSON_UNESCAPED_UNICODE);
                if($id){ /*编辑*/
                    $res = $db->where(array('id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $save_data['create_time'] = time();
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
            $res['introduce'] =  htmlspecialchars_decode( $res['introduce'] );
            $spec_arr = json_decode($res['spec'],true);
            $res['spec'] =  htmlspecialchars_decode( $res['introduce'] );
            $res['spec_name'] = $spec_arr['spec_name'];
            $res['spec_val'] = $spec_arr['spec_val'];
            /*币种列表*/
            $cur_list = M('currency')->where(array('is_lock'=> 0))->select();
            /*商品类型列表*/
            $type_list = M('goods_type')->where(array('status'=>1))->select();
            $this->assign('info',$res);
            $this->assign('cur_list',$cur_list);
            $this->assign('type_list',$type_list);
            $this->display();
        }
    }

    /*发布商品*/
    public function pub(){

        $db = M("goods");

        $id = I('post.id','','');
        $status = I('post.status','','');

        if($id){
            $res = $db->where(array('id'=>$id))->save(array('status'=>$status));
            if($res){
                $this->success('操作成功',U('index'));
            }else{
                $this->error('操作失败');
            }

        }else{
            $this->error('未知错误');
        }

    }

    public function type(){
        $model = M ('goods_type' );
        $string = I('string');
        $type_name = I('type_name');
        $where = array();
//        if($string){
//            $where["_string"] = "type_name like %$string% OR id like %$string%" ;
//        }
        if($type_name){
            $where["type_name"] = array('like',"%".$type_name.'%');
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
            ->order ("sort asc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();

        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }

    public function typeedit(){
        $db = M("goods_type");
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
                    $this->success('操作成功',U('type'));
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


    public function usergoods(){
        $model = M ('member_goods' );
        $good_name = I('good_name');
        $member_id = I('member_id');

        $where = array();
        if($good_name){
            $where['good_name'] = array('like','%'.$good_name.'%');
        }
        if($member_id){
            $where['member_id'] = $member_id;
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
        foreach ($info as $key =>$value){
            $info[$key]['phone'] = M('member')->where(array('member_id'=>$value['member_id']))->getField('phone');
        }
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }


    public function outside_ad(){
        $model = M ('outside_ad' );
        $string= I('string');

        $where = array();
        if($string){
            $where["_string"] = "title like %$string% OR subtitle like %$string%" ;
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
            ->order ("sort desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();

        foreach ($info as $k=>$value) {
            $tag_json_arr = json_decode($value['tag_json'], true);
            $info[$k]['tag_json_str'] = implode(' ', $tag_json_arr);
        }
        $this->assign ('list', $info ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    public function outside_ad_edit(){
        $db = M("outside_ad");
        if(IS_POST){
            $id = I('post.id','','');
            $tag_val = I('post.tag_val','','');

            if($save_data = $db->create()){
                $tag_json = array();
                if($tag_val){
                    $tag_json = $tag_val;
                }
                if($_FILES["ad_img"]["tmp_name"]){
                    $ad_img  = $this->upload($_FILES["ad_img"]);
                }else{
                    $ad_img = I('oldad_img');
                }
                if($_FILES["cover_img"]["tmp_name"]){
                    $cover_img  = $this->upload($_FILES["cover_img"]);
                }else{
                    $cover_img = I('oldcover_img');
                }
                $save_data['update_time'] = time();
                $save_data['detail'] = stripslashes(htmlspecialchars_decode($_POST['detail']));
                $save_data['ad_img'] = $ad_img;
                $save_data['cover_img'] = $cover_img;
                $save_data['tag_json'] = json_encode($tag_json,JSON_UNESCAPED_UNICODE);
                if($id){ /*编辑*/
                    $res = $db->where(array('id'=>$id))->save($save_data);
                }else{ /*新增*/
                    $save_data['create_time'] = time();
                    $res = $db->add($save_data);
                }
                if($res){
                    $this->success('操作成功',U('outside_ad#4#2'));
                }else{
                    $this->error('操作失败');
                }
            }
        }else{
            $id = I('get.id');
            $res = $db->where(array('id'=>$id))->find();
            $tag_json_arr = json_decode($res['tag_json'],true);
            $res['tag_json_arr'] = $tag_json_arr;

            $this->assign('info',$res);
            $this->display();
        }
    }

    public function outside_ad_pub(){

        $db = M("outside_ad");

        $id = I('post.id','','');
        $status = I('post.status','','');

        if($id){
            $res = $db->where(array('id'=>$id))->save(array('status'=>$status));
            if($res){
                $this->success('操作成功',U('index'));
            }else{
                $this->error('操作失败');
            }

        }else{
            $this->error('未知错误');
        }

    }


}