<?php
return array(
    'debug'=>true,
    'gateway'=>'http://TSC2.800CT.COM:9006/sms/v2/std/single_send',
//    'template'=>'【金麟网络】您的验证码为{code}，在30分钟内有效。',
    'template'=>'同事您好，感谢您对此次测试的配合。{code}',
    'userid'=>'JC2135',
    'pwd'=>'236158',
    'key'=>'00000000',

    'action_arr'=>array(
        '1'=>array('sms'=>'sms.aspx', 'status'=>'statusApi.aspx', 'call'=>'callApi.aspx'),
        '2'=>array('sms'=>'http/submitSms', 'status'=>'http/getReportWithTime', 'call'=>'http/getReply'),
        '3'=>array('sms'=>'MessageTransferWebAppJs/servlet/messageTransferServiceServletByXml', 'status'=>'MessageTransferWebAppJs/servlet/messageTransferServiceServletByXml', 'call'=>'MessageTransferWebAppJs/servlet/messageTransferServiceServletByXml')
    ),
);