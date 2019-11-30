<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/4
 * Time: 15:21
 */

namespace Admin\Controller;
use \Think\Page;
class StatisticsController extends AdminController
{
    /*金币数据统计*/
    public function jb_deal(){
        $model = M ('trade' );
        $phone = I('phone');

        $where = array(
            'd.currency_id' => 3
        );
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
            $v["balance"] = number_format($v['balance'],2,'.','');
            $v["oldbalance"] = number_format($v['oldbalance'],2,'.','');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    /*莱特币金币数据统计*/
    public function ltb_deal(){
        $model = M ('trade' );
        $phone = I('phone');

        $where = array(
            'd.currency_id' => 2
        );
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
            $v["balance"] = number_format($v['balance'],2,'.','');
            $v["oldbalance"] = number_format($v['oldbalance'],2,'.','');
        }
        $this->assign ('list', $list ); // 赋值数据集
        $this->assign ('page', $show ); // 赋值分页输出
        $this->display ();
    }
    /*导出excel*/
    public function export_excel(){

        $data = array();

        $model = M ('trade' );

        /*查询条件*/
        $phone = I('phone');
        $type = I('type');
        $where = array(
            'd.currency_id' => $type
        );
        if($phone) {
            $where["m.phone"] = array('like', '%' . $phone . "%");
        }
        $field = "d.*,m.phone";
        $list = $model->alias('d')
            ->join('left join blue_member as m on m.member_id=d.member_id')
            ->field ( $field )
            ->where ( $where )
            ->order ("d.add_time desc" )
            ->select ();

        if($list){
            foreach ($list as $key => $value){
                $data[$key]['id'] = $value['id'];
                $data[$key]['phone'] = $value['phone'];
                if($value['type'] == 1){
                    $data[$key]['num'] = "+".$value['num'];

                }else{
                    $data[$key]['num'] = "-".$value['num'];
                }
                switch ($value['trade_type']){
                    case 1:
                        $trade_type_name = "每日签到";
                        break;
                    case 2:
                        $trade_type_name = "转盘抽奖";
                        break;
                    case 3:
                        $trade_type_name = "下线推广返利";
                        break;
                    case 4:
                        $trade_type_name = "领取红包";
                        break;
                    case 5:
                        $trade_type_name = "下线领取分红返利";
                        break;
                    case 6:
                        $trade_type_name = "下线抽奖返利";
                        break;
                    case 7:
                        $trade_type_name = "购买vip";
                        break;
                    case 8:
                        $trade_type_name = "关闭vip";
                        break;
                    case 9:
                        $trade_type_name = "分红奖励";
                        break;
                    case 10:
                        $trade_type_name = "提现";
                        break;
                    case 11:
                        $trade_type_name = "充值";
                        break;
                    case 12:
                        $trade_type_name = "兑换莱特币";
                        break;
                    case 13:
                        $trade_type_name = "下线实名奖励";
                        break;
                }
                $data[$key]['trade_type_name'] = $trade_type_name;
                $data[$key]['balance'] = $value['balance'];
                $data[$key]['oldbalance'] = $value['oldbalance'];
                $data[$key]['add_time'] = date("Y-m-d H:i:s",$value['add_time']);
            }
        }
        $xlsCell = array(
            array('id', '单号id'),
            array('phone', '用户手机号'),
            array('num', '资金变动'),
            array('trade_type_name', '变动说明'),
            array('balance', '变动前资金余额'),
            array('oldbalance', '变动后资金余额'),
            array('add_time', '时间'),
        );
        if($type == 2){
            $fileName = "全网莱特币流水统计";
        }else{
            $fileName = "全网金币流水统计";
        }
        exportExcel($fileName,$xlsCell,$data);
    }
}