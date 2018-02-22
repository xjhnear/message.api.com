<?php
return array(
	//推广任务的消息通知
	'task_message'=>array('newtuiguang_1'=>'新用户注册填写推广ID奖励[text]个游币', 
						'oldtuiguang_1'=>'老用户每推广一名新用户奖励[text]个游币', 
						'oldtuiguang_10'=>'老用户每推广10名新用户奖励[text]个游币', 
						'oldtuiguang_100'=>'老用户每推广100名新用户奖励[text]个游币',
						'oldtuiguang_500'=>'老用户每推广500名新用户奖励[text]个游币', 
						'oldtuiguang_1000'=>'老用户每推广1000名新用户奖励[text]个游币', ),
    'task'=>array('tasktype'=>array('1'=>'每日任务','2'=>'新手任务','3'=>'推广任务')),
	
    'circle_feedtype'=>array('topic'=>'发帖','comment'=>'评论'),

    'user_feedtype'=>array('topic'=>'发帖','reply'=>'回帖','game_comment'=>'游戏评论','article_comment'=>'文章评论','gift'=>'领取礼包','activity'=>'参与活动'),
    //评论类型
    'comment_type'=>array('0'=>'yxd_forum_topic','6'=>'m_games','1'=>'m_news','2'=>'m_gonglue','3'=>'m_feedback','4'=>'m_game_notice','5'=>'m_videos','7'=>'m_xyx_game'),

    'forum_global_channel'=>array('1'=>'新人报道','2'=>'游戏问答','3'=>'游戏秀','4'=>'系统区'),

    'like_type'=>array('0'=>'topic','6'=>'m_games','1'=>'m_news','2'=>'m_gonglue','3'=>'m_feedback','4'=>'m_game_notice','5'=>'m_videos','7'=>'m_xyx_game'),

    'credit_history_tpl'=>array(
        'login'=>'',
    ),

    'at_me'=>array('0'=>'yxd_forum_thread'),

    //需要过滤替换的关键字字库（多个词用‘,’分开，字符前后请不要有空格）
    'filter_chars'=>'淘宝,内购,破解,兼职,同步推,同步,步推,91助手,PP,快用,助手,当乐,taobao,tongbutui,itools',
    //替换后显示的字符
    'replace_chars' => '',
    'baidu_tags' => array(
       'reserve_giftbag' => 'reserve_giftbag_', //预约礼包

    )
);