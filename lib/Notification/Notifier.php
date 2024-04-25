<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\B2shareBridge\Notification;

use OCA\B2shareBridge\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use Psr\Log\LoggerInterface;

class Notifier implements INotifier
{

    protected $factory;
    protected $url;
    protected $logger;

    public function __construct(
        IFactory $factory,
        IURLGenerator $urlGenerator,
        LoggerInterface $logger,
    ) {
        $this->factory = $factory;
        $this->url = $urlGenerator;
        $this->logger = $logger;
    }

    public function getID(): string
    {
        return 'b2sharebridge';
    }

    /**
     * Human readable name describing the notifier
     */
    public function getName(): string
    {
        return $this->factory->get(Application::APP_ID)->t('B2shareBridge notification');
    }

    /**
     * @param INotification $notification
     * @param string $languageCode The code of the language that should be used to prepare the notification
     */
    public function prepare(INotification $notification, string $languageCode): INotification
    {
        if ($notification->getApp() !== Application::APP_ID) {
            // Not my app => throw
            throw new \InvalidArgumentException('Unknown App');
        }

        // Read the language from the notification
        $l = $this->factory->get(Application::APP_ID, $languageCode);

        switch ($notification->getSubject()) {
            // Deal with known subjects
            case 'internal_error':
            case 'external_error':
            case 'no_upload_result':
            case 'not_accessible':
            case 'unauthorized':
                $notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/error.svg')))
                    ->setLink($this->url->linkToRouteAbsolute(Application::APP_ID . '.View.index'));


                return $this->setErrorNotificationSubject($notification, $l);
            case 'upload_successful':
                $parameters = $notification->getSubjectParameters();
                $notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/checkmark.svg')))
                    ->setLink($parameters['url']);

                // Set rich subject, see https://github.com/nextcloud/server/issues/1706 for more information
                // and https://github.com/nextcloud/server/blob/master/lib/public/RichObjectStrings/Definitions.php
                // for a list of defined objects and their parameters.
                $notification->setRichSubject($l->t('Your transfer to B2SHARE was successful. Finalize your publication at: {share}'), [
                    'share' => [
                        'type' => 'pending-federated-share',
                        'id' => $notification->getObjectId(),
                        'name' => $parameters['url'],
                    ]
                ]);

                // Set the plain text subject automatically
                $this->setParsedSubjectFromRichSubject($notification);
                return $notification;

            default:
                // Unknown subject => Unknown notification => throw
                throw new \InvalidArgumentException('Unknown subject "' . $notification->getSubject() . '"');
        }
    }

    private function setErrorNotificationSubject($notification, $l): INotification
    {
        $parameters = $notification->getSubjectParameters();
        switch ($notification->getSubject()) {
            // Deal with known subjects
            case 'internal_error':
                $notification->setRichSubject($l->t('Your transfer to B2SHARE failed. An internal server error occured!'));
                break;
            case 'external_error':
                $notification->setRichSubject($l->t('Your transfer to B2SHARE failed. An external server error occured!'));
                break;
            case 'no_upload_result':
                $notification->setRichSubject($l->t('Your transfer to B2SHARE failed. Your upload returned no result! Please check '. $parameters['url'] . ', if your draft was created.'));
                break;
            case 'not_accessible':
                $notification->setRichSubject($l->t('Your transfer to B2SHARE had issues. Some file was not accessible.'));
                break;
            case 'unauthorized':
                $notification->setRichSubject($l->t('Your transfer to B2SHARE failed. You are not allowed to upload to the community "'. $parameters['community'] . '".'));
                break;
            default:
                // Unknown subject => Unknown notification => throw
                throw new \InvalidArgumentException('Unknown subject "' . $notification->getSubject() . '"');
        }

        // Set the plain text subject automatically
        $this->setParsedSubjectFromRichSubject($notification);
        return $notification;
    }

    /**
     * This is a little helper function which automatically sets the simple parsed subject
     * based on the rich subject you set. This is also the default behaviour of the API
     * since Nextcloud 26, but in case you would like to return simpler or other strings,
     * this function allows you to take over.
     *
     * @param INotification $notification
     */
    protected function setParsedSubjectFromRichSubject(INotification $notification): void
    {
        $placeholders = $replacements = [];
        foreach ($notification->getRichSubjectParameters() as $placeholder => $parameter) {
            $placeholders[] = '{' . $placeholder . '}';
            if ($parameter['type'] === 'file') {
                $replacements[] = $parameter['path'];
            } else {
                $replacements[] = $parameter['name'];
            }
        }

        $notification->setParsedSubject(str_replace($placeholders, $replacements, $notification->getRichSubject()));
    }
}