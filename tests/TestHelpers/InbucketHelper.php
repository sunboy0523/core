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
 * Helper to test email sending, using inbucket email service
 *
 *
 */
class InbucketHelper {

	/**
	 * retrieving emails sent from mailhog
	 *
	 * @param string $mailbox
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getMailboxes (string $mailbox) {
		$response = HttpRequestHelper::get(
			 "http://localhost:9100" . "/api/v1/mailbox/${mailbox}",
			null,
			null,
			null,
			['Content-Type' => 'application/json']
		);
		$json = json_decode($response->getBody()->getContents());
		return $json;
	}

	/**
	 * retrieving emails sent from mailhog
	 *
	 * @param string $mailbox
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getMailboxIds (string $mailbox) {
		$mailboxemailsmetainfo = self::getMailboxes($mailbox);
		$mailboxIds = [];
		for ($i = 0; $i < sizeof($mailboxemailsmetainfo); $i++) {
			$mailboxIds[] = $mailboxemailsmetainfo[$i]->id;
		}
		return $mailboxIds;
	}

	/**
	 *
	 * @param string|null $localInbucketUrl
	 * @param string|null $xRequestId
	 * @param string $mailbox
	 *
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	public static function deleteAllEmails(
		?string $localInbucketUrl,
		?string $xRequestId,
		?string $mailbox
	):ResponseInterface {
		return HttpRequestHelper::delete(
			$localInbucketUrl . "/api/v1/mailbox/" .$mailbox,
			$xRequestId
		);
	}


	/**
	 * retrieving emails sent from mailhog
	 *
	 * @param string $mailboxid
	 * @param string $mailbox
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getBodyContentWithID (string $mailbox, string $mailboxid) {
		$response = HttpRequestHelper::get(
			"http://localhost:9100" . "/api/v1/mailbox/${mailbox}/" . $mailboxid,
			null,
			null,
			null,
			['Content-Type' => 'application/json']
		);

		$json = json_decode($response->getBody()->getContents());
		return $json;
	}

	/**
	 * retrieving emails sent from mailhog
	 *
	 * @param string $mailbox
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getMailbox (string $mailbox) {
		$response = HttpRequestHelper::get(
			"http://localhost:9100" . "/api/v1/mailbox/${mailbox}/",
			null,
			null,
			null,
			['Content-Type' => 'application/json']
		);

		$json = json_decode($response->getBody()->getContents());
		return $json;
	}

	/**
	 *
	 * @param string|null $localMailhogUrl
	 * @param string|null $emailAddress
	 * @param string|null $xRequestId
	 * @param array $mailboxes,
	 * @param int $emailNumber
	 *
	 * @return string
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public static function getBodyOfLastEmail(
		?string $localMailhogUrl,
		?string $emailAddress,
		?string $xRequestId = '',
		array $mailboxes,
		?int $emailNumber = 1
//		?int $waitTimeSec = EMAIL_WAIT_TIMEOUT_SEC
	) {
		foreach ($mailboxes as $mailbox){
			$mailboxIds = self::getMailboxIds($mailbox);
			$response = self::getBodyContentWithID($mailbox, $mailboxIds[sizeof($mailboxIds) - $emailNumber]);
			if(str_contains($response->to[0], $emailAddress)){
				return $response->body->text;
			}
		}
		throw new Exception("Could not find the email to the address: " . $emailAddress);
	}

	/**
	 *
	 * @param string|null $localMailhogUrl
	 * @param string|null $emailAddress
	 * @param string|null $xRequestId
	 * @param array $mailboxes
	 *
	 * @return boolean
	 */
	public static function emailReceived(
		?string $localMailhogUrl,
		?string $emailAddress,
		?string $xRequestId,
		array $mailboxes
	):bool {
		try {
			self::getBodyOfLastEmail(
				$localMailhogUrl,
				$emailAddress,
				$xRequestId,
				$mailboxes
			);
		} catch (Exception $err) {
			return false;
		}

		return true;
	}


	/**
	 *
	 * @param string|null $localMailhogUrl
	 * @param string|null $emailAddress
	 * @param string|null $xRequestId,
	 * @param array $mailboxes
	 *
	 * @return mixed
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public static function getSenderOfEmail(
		?string $localMailhogUrl,
		?string $emailAddress,
		?string $xRequestId = '',
		array $mailboxes
	) {
		foreach ($mailboxes as $mailbox){
			$mailboxIds = self::getMailboxIds($mailbox);
			$response = self::getBodyContentWithID($mailbox, $mailboxIds[sizeof($mailboxIds) - 1]);
			if(str_contains($response->to[0], $emailAddress)){
				return $response->from;
			}
		}
		throw new Exception("Could not find the email to the address: " . $emailAddress);
	}


	/**
	 * Returns the host name or address of the Mailhog server as seen from the
	 * point of view of the system-under-test.
	 *
	 * @return string
	 */
	public static function getInbucketHost():string {
		$inbucketHost = \getenv('INBUCKET_HOST');
		if ($inbucketHost === false) {
			$inbucketHost = "127.0.0.1";
		}
		return $inbucketHost;
	}


	/**
	 * Returns the host name or address of the Mailhog server as seen from the
	 * point of view of the test runner.
	 *
	 * @return string
	 */
	public static function getLocalInbucketHost():string {
		$localInbucketHost = \getenv('LOCAL_INBUCKET_HOST');
		if ($localInbucketHost === false) {
			$localInbucketHost = self::getInbucketHost();
		}
		return $localInbucketHost;
	}

	/**
	 * Returns the host and port where Mailhog messages can be read and deleted
	 * by the test runner.
	 *
	 * @return string
	 */
	public static function getLocalInbucketMailUrl():string {
		$localInbucketHost = self::getLocalInbucketHost();

		$inbucketPort = \getenv('INBUCKET_PORT');
		if ($inbucketPort === false) {
			$inbucketPort = "9100";
		}
		return "http://$localInbucketHost:$inbucketPort";
	}
}
