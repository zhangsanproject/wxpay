```markdown
# 微信支付商家转账到零钱（PHP）

基于 [wechatpay-php](https://github.com/wechatpay-apiv3/wechatpay-php) SDK 1.x 实现的 **商家转账到零钱** 示例代码，支持 APIv3 接口。

适用于：活动奖励、现金营销、企业报销等场景。

---

## 功能说明

- 使用微信支付 APIv3 商家转账接口（`/v3/fund-app/mch-transfer/transfer-bills`）
- 自动加载商户私钥与微信支付公钥
- 支持收款人真实姓名加密（金额 ≥ 2000 元时必填）
- 生成全局唯一商户单号
- 完整的异常处理与错误信息输出

---

## 环境要求

| 项目 | 要求 |
|------|------|
| PHP 版本 | ≥ 7.2 |
| 扩展 | `openssl`、`curl`、`json` |
| Composer | 已安装 |
| 证书权限 | PHP 进程对证书目录有读取权限 |

---

## 安装依赖

在项目根目录执行：

```bash
composer require wechatpay/wechatpay
```

或确保已存在 `vendor/autoload.php`。

---

## 目录结构建议

```
project/
├── cert/
│   ├── apiclient_key.pem      # 商户 API 私钥
│   └── pub_key.pem            # 微信支付公钥
├── vendor/
├── wxpay.php                  # 本示例脚本
└── README.md
```

---

## 配置说明

打开 `wxpay.php`，修改以下配置项：

```php
$mchId               = '你的商户号';
$mchCertSerial       = '商户API证书序列号（40位十六进制）';
$platformPublicKeyId = 'PUB_KEY_ID_xxxxxxxx';   // 微信支付公钥ID

$privateKeyPath        = __DIR__ . '/cert/apiclient_key.pem';
$platformPublicKeyPath = __DIR__ . '/cert/pub_key.pem';
```

### 转账参数

```php
$params = [
    'appid'                => '绑定的公众号/小程序 AppID',
    'out_bill_no'          => $outBillNo,           // 自动生成，建议保持全局唯一
    'transfer_scene_id'    => '1000',               // 转账场景ID
    'openid'               => '收款人 OpenID',
    'transfer_amount'      => 10,                   // 金额单位：分（10 = 0.1 元）
    'transfer_remark'      => '测试奖励发放',
    'notify_url'           => 'https://你的域名/notify.php',
    'user_recv_perception' => '现金奖励',
    'transfer_scene_report_infos' => [
        // 场景报备信息（根据实际业务填写）
    ]
];
```

### 姓名加密（可选）

当转账金额 ≥ 2000 元时，必须填写收款人真实姓名：

```php
$userName = '张三';   // 填写后代码会自动使用微信支付公钥加密
```

---

## 使用方法

1. 将商户私钥 `apiclient_key.pem` 和微信支付公钥 `pub_key.pem` 放入 `cert/` 目录。
2. 填写所有配置项。
3. 执行脚本：

```bash
php wxpay.php
```

### 成功响应示例

```
✅ 转账单创建成功！(单据待用户确认)
微信返回数据：
Array
(
    [out_bill_no] => TRANSFER20260821123456xxxx
    [transfer_bill_no] => ...
    [state] => WAIT_USER_CONFIRM
    ...
)
```

### 常见错误

- **公私钥加载失败**：检查证书路径和文件权限。
- **HTTP 异常**：查看返回的错误码与提示信息（如参数错误、余额不足、openid 不匹配等）。

---

## 注意事项

1. **商户单号**必须全局唯一，建议使用时间戳 + 随机数。
2. 转账金额单位为 **分**，请勿填错。
3. 不同 `transfer_scene_id` 对应不同业务场景，请参考微信官方文档选择正确场景。
4. 生产环境请将敏感配置（商户号、证书等）放入环境变量或配置文件，避免硬编码。
5. 建议配置 `notify_url` 接收异步转账结果通知。
6. 本示例仅演示发起转账，实际业务中请做好日志记录、幂等处理与安全校验。

---

## 相关文档

- [微信支付商家转账产品文档](https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_1.shtml)
- [wechatpay-php SDK](https://github.com/wechatpay-apiv3/wechatpay-php)
- [APIv3 签名与证书说明](https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay-1.shtml)

---

## 免责声明

本代码仅供学习与参考，请根据实际业务需求进行安全加固与测试。使用过程中产生的任何资金损失或合规问题，由使用者自行承担。
```