<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Yxd\Services\UserFeedService;

class UserFeed extends Command 
{
	protected $name = 'command:userfeed';
	
	protected $description = 'This is UserFeed command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//
		UserFeedService::distributeDataFeed();
		$this->info('userfeed command is called success');
	}
}
