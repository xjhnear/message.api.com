<?php
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Yxd\Modules\Message\PromptService;

class MsgNum extends Command 
{
	protected $name = 'command:msgnum';
	
	protected $description = 'This is MsgNum command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//
		PromptService::distributeData();
		$this->info('msgnum command is called success');
	}
}
