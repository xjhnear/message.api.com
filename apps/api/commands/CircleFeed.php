<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Yxd\Services\CircleFeedService;

class CircleFeed extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:circlefeed';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This is CircleFeed command';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		//
		CircleFeedService::distributeDataFeed();
		$this->info('circlefeed command is called success');
	}
	
    /**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		/*
		return array(
			array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
		*/
		return array();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		/*
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
		*/
		return array();
	}
}