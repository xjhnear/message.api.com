UPDATE yii2_message_send a
INNER JOIN test b
ON a.task_id = b.taskid AND a.phonenumber=b.phone
SET a.errorcode = b.text,
a.status = case when (b.msg<>'���ͳɹ�') then 20 else 10 end
WHERE a.`status`=0 AND (a.message_id = 50 or a.message_id = 51)


UPDATE yii2_message_detail a
INNER JOIN yii2_message_send b
ON a.message_did = b.message_did
SET a.status = 3
WHERE a.`status`=5 AND b.`status`=10 AND (a.message_id = 50 or a.message_id = 51)

//////////////////////////////////////////////////////////////////////////

SELECT MAX(rid) FROM yii2_message_return WHERE is_do = 0

UPDATE yii2_message_send a
INNER JOIN yii2_message_return b
ON a.task_id = b.taskid AND a.phonenumber=b.phone
SET a.errorcode = b.errorcode,
a.status = b.status,
a.extno = b.extno,
a.return_time = b.retime
WHERE a.`status`=0 AND b.is_do = 0


UPDATE yii2_message_detail aa
INNER JOIN
(SELECT a.message_did,b.status FROM yii2_message_send a
INNER JOIN yii2_message_return b
ON a.task_id = b.taskid AND a.phonenumber=b.phone
WHERE b.is_do = 0) bb
ON aa.message_did = bb.message_did
SET aa.status = case when (bb.status<>10) then 4 else 3 end
WHERE aa.`status`=5

UPDATE yii2_message_return SET is_do = 1 WHERE is_do = 0 AND rid <=