<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\EiCourses;
use App\Models\EiPlatform;
use App\Models\MyCourse;
use App\Models\Order;
use Common\Functions;
use Curl\Curl;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    //生成订单，并在请求微信下单支付
    public function placeAnOrder(Request $request)
    {
        //获得用户id
        $msg = [
            'commodity_type.required' => '你没有提供商品类型',
            'commodity_id.required' => '你没有提供商品id'
        ];

        $validator = Validator::make(Input::all(),[
            'commodity_type' => 'required',
            'commodity_id' => 'required'
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $user_id = $request->user()->id;
        if(!isset($request->user()->weixin_open_id)){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=>'你不是微信登录用户'
            ]);
        }
        $openid = $request->user()->weixin_open_id;
//        $openid = 'oPap_4k-VDZ_0yfzm0Ic_tckWN0I';

        $commodity_type = $request->commodity_type;
        $commodity_id = $request->commodity_id;

        if($commodity_type=='机构课程'){
            $commodity_data = EiCourses::where('id',$commodity_id)->first();
            if($commodity_data){
                $ei_data = EiPlatform::where('id',$commodity_data->ei_id)->first();
                $merchant_name = $ei_data->ei_name;
            }
        }

        if(!$commodity_data){    //此商品不存在
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=> '购买信息获取失败'
            ]);
        }

        $appid = env('WEIXIN_APP_ID','');     //小程序id
        $mch_id = env('WEIXIN_MCH_ID','');    //商户号
        $functions = new Functions();
        $nonce_str = $functions->random_str(32);//随机字符串
        $bady = $merchant_name.'-'.$commodity_data->name;//商品描述
        $time = time();
        $out_trade_no = date("YmdHis",$time).$functions->random_str(10);//商品订单号（当前时间+随机数）
        $total_fee = 100*$commodity_data->price;//标价金额
        $spbill_create_ip = '114.215.28.64';//终端ip
        $time_start = date("YmdHis",$time);//交易起始时间
        $time_expire = date("YmdHis",$time+1800);//交易结束时间（有效时间30分钟）
        $notify_url = 'https://lecheng.viiwen.cn/api/wx_pay_buck';//通知地址（回调地址）

        $data = [
            'appid'=>$appid,    //小程序id
            'mch_id'=>$mch_id,  //商户号
            'device_info'=>'', //（非必须）设备号
            'nonce_str'=>$nonce_str,//随机字符串
            'sign_type'=>'MD5',//（非必须）签名类型
            'body'=>$bady,//商品描述
            'detail'=>'',//（非必须）商品详情
            'attach'=>'',//（非必须）附加数据，
            'out_trade_no'=>$out_trade_no,//商品订单号
            'fee_type'=>'CNY',//（非必须）标价币种
            'total_fee'=>$total_fee,//标价金额
            'spbill_create_ip'=>$spbill_create_ip,//终端IP
            'time_start'=>$time_start,//（非必须）交易起始时间
            'time_expire'=>$time_expire,//（非必须）交易结束时间
            'notify_url'=>$notify_url,//通知地址
            'trade_type'=>'JSAPI',//交易类型
            'openid'=>$openid,//（非必须）用户标识
        ];
        ksort($data);
        $stringA = '';
        $count = 0;//循环结束
        foreach ($data as $key=>$value){
            if($value){    //如果参数值不为空
                $count +=1;
                if($count==1){
                    $stringA .=$key.'='.$value;
                }else{
                    $stringA .='&'.$key.'='.$value;
                }
            }
        }

        $key = env('WEIXIN_BUSINESS_KEY','');  //商户平台的密钥key
        $stringSignTemp = $stringA.'&key='.$key;   //API密钥
        $sign = md5($stringSignTemp);  //生成签名字符串
        $sign = strtoupper($sign);     //将签名字符串转换成大写
        $data['sign'] = $sign;

        //将data数组转换成XML
        $xml_str='<xml>';
        $num = 0;
        foreach ($data as $key1 => $value1){
            $num +=1;
            $xml_str .='<'.$key1.'>'.$value1.'</'.$key1.'>';
            if($num==count($data)){
                $xml_str .='</xml>';
            }
        }

        //访问微信接口
        $curl = new Curl();
        $response = $curl->post('https://api.mch.weixin.qq.com/pay/unifiedorder',$xml_str);

        //微信接口返回的是XML，我们将XML转化成对象
        $obj_data = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);//将XML转化成对象

        if($obj_data->return_code=='SUCCESS'&&$obj_data->result_code=='SUCCESS'){
            //将下单数据写入数据库中
            $add = [
                'user_id'=>$user_id,
                'commodity_type'=>$commodity_type,
                'commodity_id'=>$commodity_id,
                'out_trade_no'=>$out_trade_no,
                'total_fee'=>$total_fee,
                'time_start'=>$time,
            ];
            $result = Order::insert($add);
            if(!$result){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$SystemErr,
                    'msg'=> '系统错误，请稍后重试'
                ]);
            }

            //返回下单数据给客服端
            return response()->json([
                'result' => 'ok',
                'code' => Code::$OK,
                'msg'=> '成功',
                'data' => $obj_data
            ]);

        }
    }

    //微信支付结果回调函数
    public function wxPayBuck(Request $request)
    {
        /**  微信反馈回来的字段，返回形式为XML
         * appid           小程序id
         * bank_type       付款银行
         * cash_fee        现金支付金额
         * fee_type        货币种类
         * is_subscribe    是否关注公众账号
         * mch_id          商户号
         * nonce_str       随机字符串
         * openid          用户标识
         * out_trade_no    商户订单号
         * result_code     业务结果
         * return_code     返回状态码
         * sign            签名
         * time_end        支付完成时间
         * total_fee       订单金额
         * trade_type      交易类型
         * transaction_id  微信支付订单号
         */
        //所有请求数据$request，包括head跟body
        $body = file_get_contents("php://input");//获得body（这里是XML格式的字符串）
        $obj_body = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);//将XML转化成对象
        if($obj_body->return_code=='SUCCESS'){
            $out_trade_no = $obj_body->out_trade_no;    //商户订单号
            $time_end = $obj_body->time_end;            //支付完成时间
            $total_fee = $obj_body->total_fee;          //订单金额（单位为分）
            $transaction_id = $obj_body->transaction_id;   //微信支付订单号

            //查询订单
            $result = Order::where('out_trade_no',$out_trade_no)->first();
            if($result){
                if($result->total_fee == $total_fee){   //订单金额没有问题
                    //更新订单数据
                    $up_date_arr = [
                        'transaction_id'=>$transaction_id,
                        'time_end'=>$time_end,
                        'status'=>2
                    ];
                    $upDate = Order::where('out_trade_no',$out_trade_no)->update($up_date_arr);

                    $commodity_type = $result->commodity_type;   //商品种类
                    if($commodity_type == '机构课程'){
                        $my_course_data = [
                            'user_id'=>$result->user_id,
                            'course_id'=>$result->commodity_id,
                            'time'=>time(),
                        ];
                        //报名成功，写入我的课程
                        MyCourse::insert($my_course_data);
                    }
                }
            }
        }
    }
}
