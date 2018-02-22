<?php
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Yxd\Services\AtmeService;

class GiftNotice extends Command 
{
	protected $name = 'command:giftnotice';
	
	protected $description = 'This is GiftNotice command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//
		$this->info('giftnotice command is called success');
	}
}
