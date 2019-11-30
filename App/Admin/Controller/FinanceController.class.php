<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;
use \Think\Page;
class FinanceController extends AdminController
{
    /*交易记录*/
    public function index(){
        $model = M ('trade' );
        $phone = I('phone');

        $where = array();
        if($phone) {
            $where["m.phone"] = array('like', '%' . $phone . "%");
        }

        // 查询满足要求的总记录数
        $count = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.member_id')->where ( $where )->count();
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
            $v["currency_name"] = M('currency')->where(array('currency_id'=>$v['currency_id']))->getField('currency_name');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    public function trade(){
        $type=I('type');
        $currency_id=I('currency_id');
        $phone=I('phone');
        if(!empty($type)){
            $where['a.type'] = array("EQ",$type);
        }
        if(!empty($currency_id)){
            $where['a.currency_id'] = array("EQ",$currency_id);
        }
        if(!empty($_POST['member_id'])){
            $where['c.phone'] = array("EQ",$phone);
        }

        $field = "a.*,b.currency_name as b_name,c.phone";
        $count      = M('Trade_currency')
            ->alias('a')
            ->field($field)
            ->join("LEFT JOIN ".C("DB_PREFIX")."currency AS b ON a.currency_id = b.currency_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."member as c on a.member_id = c.member_id ")
            ->where($where)
            ->count();// 查询满足要求的总记录数
        $Page       = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        //给分页传参数
        setPageParameter($Page, array('type'=>$type,'currency_id'=>$currency_id,'phone'=>$phone));

        $show       = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = M('Trade_currency')
            ->alias('a')
            ->field($field)
            ->join("LEFT JOIN ".C("DB_PREFIX")."currency AS b ON a.currency_id = b.currency_id")
            ->join("LEFT JOIN ".C("DB_PREFIX")."member as c on a.member_id = c.member_id ")
            ->where($where)
            ->order(" a.add_time desc ")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        if($list){
            foreach ($list as $key=>$vo) {
                $list[$key]['type_name'] = getOrdersType($vo['type']);
            }
        }
        //币种
        $currency = M('Currency')->field('currency_name,currency_id')->select();
        $this->assign('currency',$currency);
        $this->assign('list',$list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
}