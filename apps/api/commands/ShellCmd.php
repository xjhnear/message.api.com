<?php
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ShellCmd extends Command 
{
	protected $name = 'command:shell-cmd';
	
	protected $description = 'This is ShellCmd command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//while(true){
			//$this->call('command:clearcache');
			//$this->call('command:circlefeed');
			//$this->call('command:msgnum');
			//$this->call('command:useratme');
			//$this->call('command:userfeed');
			//$this->call('command:applepush');
			//sleep(5);
		//}
		Yxd\Services\TaskService::doTuiguangNum(111902, 'oldtuiguang_1000');
		$this->info('shell-cmd command is called success');
	}	
}
