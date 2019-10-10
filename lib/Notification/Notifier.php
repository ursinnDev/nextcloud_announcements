<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
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

namespace OCA\NextcloudAnnouncements\Notification;


use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	const SUBJECT = 'announced';

	/** @var string */
	protected $appName;
	/** @var IFactory */
	protected $l10nFactory;
	/** @var IURLGenerator */
	protected $url;
	/** @var IConfig */
	protected $config;
	/** @var IGroupManager */
	protected $groupManager;

	public function __construct(string $appName,
								IFactory $l10nFactory,
								IURLGenerator $url,
								IConfig $config,
								IGroupManager $groupManager) {
		$this->appName = $appName;
		$this->l10nFactory = $l10nFactory;
		$this->url = $url;
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== $this->appName) {
			// Not my app => throw
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get($this->appName, $languageCode);

		switch ($notification->getSubject()) {
			case self::SUBJECT:
				$parameters = $notification->getSubjectParameters();
				$message = $parameters[0];
				$notification->setParsedSubject($l->t('Nextcloud announcement'))
					->setIcon($this->url->getAbsoluteURL($this->url->imagePath($this->appName, 'app-dark.svg')));

				$isAdmin = $this->groupManager->isAdmin($notification->getUser());
				if ($isAdmin) {
					$groups = $this->config->getAppValue($this->appName, 'notification_groups', '');
					if ($groups === '') {
						$action = $notification->createAction();
						$action->setParsedLabel($l->t('Disable announcements'))
							->setLink($this->url->linkToOCSRouteAbsolute('provisioning_api.Apps.disable', ['app' => 'nextcloud_announcements']), 'DELETE')
							->setPrimary(false);
						$notification->addParsedAction($action);

						$message .= "\n\n" . $l->t('(These announcements are only shown to administrators)');
					}
				}

				$notification->setParsedMessage($message);

				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}
}
