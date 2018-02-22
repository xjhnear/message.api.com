<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Yxd\Services\ClearCacheService;

class ClearCache extends Command 
{
	protected $name = 'command:clearcache';
	
	protected $description = 'This is ClearCache command';
	
    public function __construct()
	{
		parent::__construct();
	}
	
    public function fire()
	{
		//
		ClearCacheService::clearAllByQueue();
		$this->info('clearcache command is called success');
	}	
}
