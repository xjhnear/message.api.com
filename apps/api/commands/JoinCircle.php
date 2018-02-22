<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Yxd\Services\UserFeedService;

class JoinCircle extends Command 
{
	protected $name = 'command:joincircle';
	
	protected $description = 'This is JoinCircle command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//
		UserFeedService::execFeedJoinCircle();
		$this->info('joincircle command is called success');
	}
}
