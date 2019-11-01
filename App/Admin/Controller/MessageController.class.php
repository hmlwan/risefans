<?php

/**
 * Created by PhpStorm.
 * User: "姜鹏"
 * Date: 16-3-14
 * Time: 上午10:35
 */
namespace Admin\Controller;

use Think\Page;

class MessageController extends AdminController {
	public function _initialize() {
		parent::_initialize ();
	}
	/**
	 * 显示系统消息界面
	 */
	public function index() {
		$title = I ( 'post.title' );
		if (! empty ( $title )) {
			$where ['title'] = array (
					'like',
					'%' . $title . '%' 
			);
		}
		$where ['type'] = array (
				"GT",
				0 
		);
		$M_member_all = M ( 'Message_all' );
		$count = $M_member_all->where ( $where )->count (); // 查询满足要求的总记录数
		$Page = new Page ( $count, 25 ); // 实例化分页类 传入总记录数和每页显示的记录数(25)
		 //给分页传参数
        setPageParameter($Page, array('title'=>$title));
		
		$show = $Page->show (); // 分页显示输出
		                             // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
//		$list = $M_member_all->where ( $where )->alias ( 'a' )->field ( 'a.*, b.name type_name' )->join ( C("DB_PREFIX")."message_category as b on a.type = b.id " )->order ( " a.add_time desc " )->limit ( $Page->firstRow . ',' . $Page->listRows )->select ();
		$list = $M_member_all->where ( $where )->order ( "add_time desc " )->limit ( $Page->firstRow . ',' . $Page->listRows )->select ();
		// 查询消息类型
		$this->assign ( 'list', $list ); // 赋值数据集
		$this->assign ( 'page', $show ); // 赋值分页输出
		$this->display (); // 输出模板
	}
	
	/**
	 * 添加/修改消息
	 */
	public function add() {
		if (IS_POST) {
		    $id = $_POST ['id'];
			// 判断发送个人还是全体
			if ($_POST ['radios'] == 'one') {
				$data ['u_id'] = I ( 'post.u_id', '', 'intval' );
				$data ['send_type'] = 2;
			} else {
				$data ['u_id'] = - 1; // -1 状态代表群发
                $data ['send_type'] = 1;

            }

			$data ['status'] = 0;
			$data ['title'] = I ( 'post.title' );
//			$data ['content'] = I ( 'post.content', '', 'html_entity_decode' );
            $data['content'] = stripslashes(htmlspecialchars_decode($_POST['content']));

            $data ['type'] = 1;
			$M_Message_all = D ( 'Message_all' );
			if (! $M_Message_all->create ( $data )) {
				$this->error ( $M_Message_all->getError () );
				return;
			}
			if($id){
                $data ['op_time'] = time ();
                if ($M_Message_all->where(array('id'=>$id))->save ($data) === false) {
                    $this->error ( '服务器繁忙,请稍后重试' );
                    return;
                }
            }else{
                $data ['add_time'] = time ();
                if ($M_Message_all->add ($data) === false) {
                    $this->error ( '服务器繁忙,请稍后重试' );
                    return;
                }
            }

			$this->success ( '添加成功', U ( 'Message/index#9#0' ) );
			return;
		} else {
			$id = I ( 'get.id' );
			$list = M ( 'Message_all' )->where ( array (
					'id' => $id 
			) )->find ();
			// 分类
			$this->assign ( 'list', $list );
			$this->display ();
		}
	}
	/**
	 * 删除消息
	 * 
	 * @param
	 *        	$id
	 */
	public function del($id) {
		if (empty ( $id )) {
			$this->error ( '参数错误' );
		}
		if (M ( 'Message_all' )->where ( array (
				'id' => $id 
		) )->delete () === false) {
			$this->error ( '服务器繁忙,请稍后重试' );
		}
		if (M ( 'Message' )->where ( array (
				'message_all_id' => $id 
		) )->delete () === false) {
			$this->error ( '服务器繁忙,请稍后重试' );
		}
		$this->success ( '删除成功', U ( 'Message/index#9#0' ) );
	}
	/*
	 * 发布
	 */
    public function pub(){

        $db = M("Message_all");
        $id = I('post.id','','');
        if($id){
            $info = $db->where(array('id'=>$id))->find();
            if($info){
                $send_data = array(
                    'title' => $info['title'],
                    'type' => 1 ,
                    'content' =>$info['content'],
                    'add_time' =>time(),
                    'is_read' =>0,
                    'message_all_id' =>$id,
                );
                if($info['send_type'] == 1){ /*群发*/
                    $user_ids = M("member")->getField('member_id',true);
                }else{ /*个人*/
                    $user_ids = explode(',',$info['u_id']);
                }
                foreach ($user_ids as $v_id){
                    $send_data['member_id'] = $v_id;
                    M('message')->add($send_data);
                }
                /*发送完成*/
                $db->where(array('id'=>$id))->save(array('status'=>1));
                $this->success('发布成功',U('index'));
            }else{
                $this->error('发布失败');
            }

        }else{
            $this->error('未知错误');
        }

    }
    /*记录*/
    public function record() {
        $title = I ( 'post.title' );
        if (! empty ( $title )) {
            $where ['title'] = array (
                'like',
                '%' . $title . '%'
            );
        }
        $where ['type'] = array (
            "GT",
            0
        );
        $M_member_all = M ( 'Message' );
        $count = $M_member_all->where ( $where )->count (); // 查询满足要求的总记录数
        $Page = new Page ( $count, 25 ); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        //给分页传参数
        setPageParameter($Page, array('title'=>$title));

        $show = $Page->show (); // 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
		$list = $M_member_all->where ( $where )->alias ( 'a' )->field ( 'a.*, b.phone' )->join ( C("DB_PREFIX")."member as b on a.member_id = b.member_id " )->order ( " a.add_time desc " )->limit ( $Page->firstRow . ',' . $Page->listRows )->select ();
        // 查询消息类型

        $this->assign ( 'list', $list ); // 赋值数据集
        $this->assign ( 'page', $show ); // 赋值分页输出
        $this->display (); // 输出模板
    }
}