<?php
// 引入 Composer 自动加载
require_once __DIR__ . '/vendor/autoload.php';

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;

// ================= 1. 基础配置区域 =================
$mchId               = '';                                 // 商户号 (请替换为实际值)
$mchCertSerial       = '';   // 商户API证书序列号 (40位十六进制字符串)
$platformPublicKeyId = '';   // 微信支付公钥ID (PUB_KEY_ID_开头)

// 证书文件绝对路径 (请确保 PHP 进程对该路径有读取权限)
$privateKeyPath        = __DIR__ . '/cert/apiclient_key.pem';        // 商户私钥文件
$platformPublicKeyPath = __DIR__ . '/cert/pub_key.pem';              // 微信支付公钥文件

// ================= 2. 初始化加载公私钥并构造客户端 =================
try {
    // 【修复点】：统一使用 Rsa::from() 加载私钥和公钥，兼容 PHP 7.2 和 1.x SDK
    $merchantPrivateKey = Rsa::from("file://{$privateKeyPath}", Rsa::KEY_TYPE_PRIVATE);
    $platformPublicKey  = Rsa::from("file://{$platformPublicKeyPath}", Rsa::KEY_TYPE_PUBLIC);

    // 构造 APIv3 客户端实例
    $instance = Builder::factory([
        'mchid'      => $mchId,
        'serial'     => $mchCertSerial,
        'privateKey' => $merchantPrivateKey,
        'certs'      => [
            $platformPublicKeyId => $platformPublicKey,
        ],
    ]);
} catch (\Exception $e) {
    die("公私钥加载或客户端初始化失败: " . $e->getMessage() . "\n");
}

// ================= 3. 构建转账请求参数 =================
// 确保商户单号每次请求全局唯一
$outBillNo = 'TRANSFER' . date('YmdHis') . rand(1000, 9999); // 或者纯数字也行：
// $outBillNo = date('YmdHis') . rand(100000, 999999);; 
$userName  = ''; // 收款人真实姓名（转账金额 >= 2000元时必填，填入后代码会自动加密）

$params = [
    'appid'                => '',                  // 商户绑定的公众号/小程序 AppID
    'out_bill_no'          => $outBillNo,
    'transfer_scene_id'    => '1000',                                // 转账场景ID (如: 1000现金营销，1006企业报销)
    'openid'               => '',        // 收款人的 OpenID
    'transfer_amount'      => 10,                                   // 转账金额（单位：分，100 = 1元）
    'transfer_remark'      => '测试奖励发放',                          // 用户收款时可见的备注
    'notify_url'           => '', // 异步接收转账结果的回调地址
    'user_recv_perception' => '现金奖励',
    'transfer_scene_report_infos' => [
        [
            'info_type'    => '活动名称',
            'info_content' => '新会员有礼'
        ],
        [
            'info_type'    => '奖励说明',
            'info_content' => '活动奖励发放'
        ]
    ]
];

$headers = [
    'Accept'       => 'application/json',
    'Content-Type' => 'application/json',
];

// 【关键逻辑】：如果传了真实姓名，必须用微信支付公钥进行加密，并在 Header 带上公钥ID
if (!empty($userName)) {
    $params['user_name'] = Rsa::encrypt($userName, $platformPublicKey);
    $headers['Wechatpay-Serial'] = $platformPublicKeyId;
}

// ================= 4. 发起网络请求 =================
try {
    // SDK 1.x 的链式调用语法
    $response = $instance->chain('v3/fund-app/mch-transfer/transfer-bills')->post([
        'json'    => $params,
        'headers' => $headers
    ]);

    $statusCode = $response->getStatusCode();
    $result     = json_decode((string)$response->getBody(), true);

    if ($statusCode === 200 && isset($result['state']) && $result['state'] === 'WAIT_USER_CONFIRM') {
        echo "✅ 转账单创建成功！(单据待用户确认)\n";
        echo "微信返回数据：\n";
        print_r($result);
    } else {
        echo "⚠️ 接口请求成功，但状态异常，HTTP状态码: {$statusCode}\n";
        print_r($result);
    }

} catch (\GuzzleHttp\Exception\RequestException $e) {
    echo "❌ 发起请求失败 (HTTP 异常):\n";
    if ($e->hasResponse()) {
        $errorResponse = (string)$e->getResponse()->getBody();
        echo "微信返回错误信息: {$errorResponse}\n";
    } else {
        echo "请求异常: " . $e->getMessage() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ 发生未知异常: " . $e->getMessage() . "\n";
}