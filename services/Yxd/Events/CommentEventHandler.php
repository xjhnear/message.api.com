<?php
namespace Yxd\Events;

use Yxd\Services\CreditService;

use Yxd\Services\TaskService;

use Yxd\Services\Cms\CommentService;

use Yxd\Services\UserService;
use Yxd\Services\UserFeedService;

use Yxd\Services\CircleFeedService;

class CommentEventHandler
{
	public function onPost($event)
	{
		$comment = $event[0];
		$table = $comment['target_table'];
		switch($table){
			case 'm_games'://游戏评论
				$userfeed['type'] = 'game_comment';
				$userfeed['comment'] = $comment;
				UserFeedService::makeFeedGameComment($userfeed);
				CommentService::updateCommentCount('games',$comment['target_id']);				
				//游币
				CreditService::doUserCredit($comment['uid'],CreditService::CREDIT_RULE_ACTION_POST_COMMENT);
				//圈子动态
				$circlefeed['type'] = 'comment';
			    $circlefeed['comment'] = $comment;
			    CircleFeedService::makeDataFeed($circlefeed);
				break;
			case 'm_news'://新闻评论
				$userfeed['type'] = 'news_comment';
				$userfeed['comment'] = $comment;
				//游币
				CreditService::doUserCredit($comment['uid'],CreditService::CREDIT_RULE_ACTION_POST_COMMENT);
				UserFeedService::makeFeedNewsComment($userfeed);
				CommentService::updateCommentCount('news',$comment['target_id']);
				break;
			case 'm_gonglue'://攻略评论
				$userfeed['type'] = 'guide_comment';
				$userfeed['comment'] = $comment;
				//游币
				CreditService::doUserCredit($comment['uid'],CreditService::CREDIT_RULE_ACTION_POST_COMMENT);
				UserFeedService::makeFeedGuideComment($userfeed);
				CommentService::updateCommentCount('gonglue',$comment['target_id']);
				break;
			case 'm_feedback'://评测评论
				$userfeed['type'] = 'opinion_comment';
				$userfeed['comment'] = $comment;
				//游币
				CreditService::doUserCredit($comment['uid'],CreditService::CREDIT_RULE_ACTION_POST_COMMENT);
				UserFeedService::makeFeedOpinionComment($userfeed);
				CommentService::updateCommentCount('feedback',$comment['target_id']);
				break;
			case 'm_videos'://视频评论
				$userfeed['type'] = 'video_comment';
				$userfeed['comment'] = $comment;
				//游币
				CreditService::doUserCredit($comment['uid'],CreditService::CREDIT_RULE_ACTION_POST_COMMENT);
				UserFeedService::makeFeedVideoComment($userfeed);
				CommentService::updateCommentCount('videos',$comment['target_id']);
				break;
			case 'm_game_notice'://新游评论
				$userfeed['type'] = 'newgame_comment';
				$userfeed['comment'] = $comment;
				//游币
				CreditService::doUserCredit($comment['uid'],CreditService::CREDIT_RULE_ACTION_POST_COMMENT);
				UserFeedService::makeFeedNewGameComment($userfeed);	
				CommentService::updateCommentCount('game_notice',$comment['target_id']);		
				break;
			case 'yxd_forum_topic'://回帖
				$userfeed['type'] = 'reply';
				$userfeed['comment'] = $comment;				
				UserFeedService::makeFeedTopicComment($userfeed);
				CommentService::updateCommentCount('forum_topic',$comment['target_id']);				
				//游币
				CreditService::doUserCredit($comment['uid'],CreditService::CREDIT_RULE_ACTION_POST_REPLY);
				break;
		}
	}
	
	public function onReply($event)
	{
		
	}
	
    /**
	 * 订阅事件
	 */
	public function subscribe($events)
	{
		$events->listen('comment.post','\\Yxd\\Events\\CommentEventHandler@onPost');
		//$events->listen('comment.reply','\\Yxd\\Events\\CommentEventHandler@onReply');
	}
}