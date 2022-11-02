<?php declare(strict_types=1);
/**
 * ownCloud
 *
 * @author Artur Neumann <artur@jankaritech.com>
 * @copyright Copyright (c) 2017 Artur Neumann artur@jankaritech.com
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace TestHelpers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Email Helper
 *
 *
 */
class EmailHelper {

	/**
	 * Returns the host name or address of the Email server as seen from the
	 * point of view of the system-under-test.
	 *
	 * @return string
	 */
	public static function getEmailHost():string {
		$inbucketHost = \getenv('INBUCKET_HOST');
		if ($inbucketHost === false) {
			$inbucketHost = "127.0.0.1";
		}
		return $inbucketHost;
	}

	/**
	 * Returns the host name or address of the Email server as seen from the
	 * point of view of the test runner.
	 *
	 * @return string
	 */
	public static function getLocalEmailHost():string {
		$localInbucketHost = \getenv('LOCAL_INBUCKET_HOST');
		if ($localInbucketHost === false) {
			$localInbucketHost = self::getEmailHost();
		}
		return $localInbucketHost;
	}

	/**
	 * Returns the host and port where Email messages can be read and deleted
	 * by the test runner.
	 *
	 * @return string
	 */
	public static function getLocalEmailUrl():string {
		$localInbucketHost = self::getLocalEmailHost();

		$inbucketPort = \getenv('INBUCKET_PORT');
		if ($inbucketPort === false) {
			$inbucketPort = "9000";
		}
		return "http://$localInbucketHost:$inbucketPort";
	}
}
