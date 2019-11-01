<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;

class BankController extends AdminController
{

    public function index(){
        $model = M ('bank' );
        $string = I('string');

        $where = array();
        if($string){
            $where["_string"] = "bank_name like %$string% OR bank_en like %$string%" ;
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
        $db = M("bank");
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
                    $this->success('操作成功',U('index'));
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
}