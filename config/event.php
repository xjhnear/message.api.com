<?php
return array(
    'listens'=>array(
        //'user.login'=>'\\Yxd\\Events\\UserEventHandler@onUserLogin',
        //'user.update_userinfo_cache'=>'\\Yxd\\Events\\UserEventHandler@onUpdateUserInfoCache'
    ),
    'handlers'=>array(
        '\\Yxd\\Events\\UserEventHandler',
        '\\Yxd\\Events\\TopicEventHandler',
        '\\Yxd\\Events\\CommentEventHandler',
    )
);