<?php
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Yxd\Modules\Message\PushService;

class ApplePush extends Command 
{
	protected $name = 'command:applepush';
	
	protected $description = 'This is ApplePush command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//
		PushService::distributePush();
		$this->info('applepush command is called success');
	}
}
