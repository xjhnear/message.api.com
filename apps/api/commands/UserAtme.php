<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Yxd\Services\AtmeService;

class UserAtme extends Command 
{
	protected $name = 'command:useratme';
	
	protected $description = 'This is UserAtme command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//
		AtmeService::distributeDataFeed();
		$this->info('useratme command is called success');
	}
}
