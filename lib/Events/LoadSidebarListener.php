<?php

namespace OCA\B2shareBridge\Events;

use OCP\Util;
use OCP\EventDispatcher\Event;
use OCA\B2shareBridge\AppInfo\Application;
use OCP\EventDispatcher\IEventListener;
use OCA\Files\Event\LoadSidebar;

class LoadSidebarListener implements IEventListener {
    public function handle(Event $event) :void {
        if (!($event instanceof LoadSidebar)) {
            return;
        }

        Util::addStyle(Application::APP_ID, 'b2sbsidebar');
        Util::addScript(Application::APP_ID, 'b2sbsidebar');
    }
}
