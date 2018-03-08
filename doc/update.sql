UPDATE yii2_message_send a
INNER JOIN test b
ON a.task_id = b.taskid AND a.phonenumber=b.phone
SET a.errorcode = b.text,
a.status = case when (b.msg<>'·¢ËÍ³É¹¦') then 20 else 10 end
WHERE a.`status`=0 AND (a.message_id = 50 or a.message_id = 51)


UPDATE yii2_message_detail a
INNER JOIN yii2_message_send b
ON a.message_did = b.message_did
SET a.status = 3
WHERE a.`status`=5 AND b.`status`=10 AND (a.message_id = 50 or a.message_id = 51)


