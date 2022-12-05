<?php
namespace OCA\MyApp\Cron;

use OCA\B2shareBridge\Cron\B2shareCommunityFetcher;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;

class B2shareTimedJob extends TimedJob {

    private B2shareCommunityFetcher $myService;

    function __construct(ITimeFactory $time, B2shareCommunityFetcher $service) {
        parent::__construct($time);
        $this->myService = $service;

        // Run once an hour
        $this->setInterval(3600);
    }

    protected function run($arguments) {
        $this->myService->run($arguments['uid']);
    }

}