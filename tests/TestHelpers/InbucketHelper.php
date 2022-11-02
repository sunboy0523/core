<?php declare(strict_types=1);
/**
 * ownCloud
 *
 * @author Sagar Gurung <sagar@jankaritech.com>
 * @copyright Copyright (c) 2022 Sagar Gurung sagar@jankaritech.com
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
class InbucketHelper extends EmailHelper {

	/**
	 * retrieving emails sent from inbucket
	 *
	 * @param string $mailbox
	 * @param string|null $xRequestId
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getMailboxes (string $mailbox , ?string $xRequestId = null) {
		$response = HttpRequestHelper::get(
			 self::getLocalEmailUrl() . "/api/v1/mailbox/${mailbox}",
			$xRequestId,
			null,
			null,
			['Content-Type' => 'application/json']
		);
		return json_decode($response->getBody()->getContents());
	}

	/**
	 * retrieving all the email id's of mailbox(from email recipient)
	 *
	 * @param string $mailbox
	 * @param string|null $xRequestId
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getMailboxIds (string $mailbox, ?string $xRequestId = null) {
		$mailboxemailsmetainfo = self::getMailboxes($mailbox , $xRequestId);
		$mailboxIds = [];
		for ($i = 0; $i < sizeof($mailboxemailsmetainfo); $i++) {
			$mailboxIds[] = $mailboxemailsmetainfo[$i]->id;
		}
		return $mailboxIds;
	}

	/**
	 *
	 * Deletes all the emails
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
		string $mailbox
	):ResponseInterface {
		return HttpRequestHelper::delete(
			$localInbucketUrl . "/api/v1/mailbox/" .$mailbox,
			$xRequestId
		);
	}


	/**
	 * retrieving content of a specific email with email ID
	 *
	 * @param string $mailboxid
	 * @param string $mailbox
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getBodyContentWithID (string $mailbox, string $mailboxid) {
		$response = HttpRequestHelper::get(
			self::getLocalEmailUrl() . "/api/v1/mailbox/${mailbox}/" . $mailboxid,
			null,
			null,
			null,
			['Content-Type' => 'application/json']
		);

		return json_decode($response->getBody()->getContents());
	}

	/**
	 *
	 * @param string|null $emailAddress
	 * @param string|null $xRequestId
	 * @param array $mailboxes,
	 * @param int|null $emailNumber
	 * @param int|null $waitTimeSec Time to wait for the email
	 *
	 * @return string
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public static function getBodyOfLastEmail(
		?string $emailAddress,
		string $xRequestId,
		array $mailboxes,
		?int $emailNumber = 1,
		?int $waitTimeSec = EMAIL_WAIT_TIMEOUT_SEC
	) {
		$currentTime = \time();
		$endTime = $currentTime + $waitTimeSec;
		while($currentTime <= $endTime){
			foreach ($mailboxes as $mailbox){
				$mailboxIds = self::getMailboxIds($mailbox, $xRequestId);
				$response = self::getBodyContentWithID($mailbox, $mailboxIds[sizeof($mailboxIds) - $emailNumber]);
				if(str_contains($response->to[0], $emailAddress)){
					$body = \str_replace(
						"\r\n",
						"\n",
						\quoted_printable_decode($response->body->text . "\n" . $response->body->html)
					);
					return $body;
				}
			}
			\usleep(STANDARD_SLEEP_TIME_MICROSEC * 50);
			$currentTime = \time();
		}
		throw new Exception("Could not find the email to the address: " . $emailAddress);
	}

	/**
	 *
	 * @param string|null $emailAddress
	 * @param string|null $xRequestId
	 * @param array $mailboxes
	 * @param int|null $waitTimeSec Time to wait for the email
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public static function emailReceived(
		?string $emailAddress,
		?string $xRequestId,
		array $mailboxes,
		?int $waitTimeSec = EMAIL_WAIT_TIMEOUT_SEC
	):bool {
		try {
			self::getBodyOfLastEmail(
				$emailAddress,
				$xRequestId,
				$mailboxes,
				$waitTimeSec
			);
		} catch (Exception $err) {
			return false;
		}

		return true;
	}


	/**
	 *
	 * @param string|null $emailAddress
	 * @param string|null $xRequestId,
	 * @param array $mailboxes,
	 * @param int|null $emailNumber which number of multiple emails to read (first email is 1)
	 * @param int|null $waitTimeSec Time to wait for the email
	 *
	 * @return mixed
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public static function getSenderOfEmail(
		string $emailAddress,
		string $xRequestId,
		array $mailboxes,
		?int $emailNumber = 1,
		?int $waitTimeSec = EMAIL_WAIT_TIMEOUT_SEC
	) {
		$currentTime = \time();
		$endTime = $currentTime + $waitTimeSec;

		while($currentTime <= $endTime) {
			foreach ($mailboxes as $mailbox){
				$mailboxIds = self::getMailboxIds($mailbox, $xRequestId);
				$response = self::getBodyContentWithID($mailbox, $mailboxIds[sizeof($mailboxIds) - $emailNumber]);
				if(str_contains($response->to[0], $emailAddress)){
					return $response->from;
				}
			}
			\usleep(STANDARD_SLEEP_TIME_MICROSEC * 50);
			$currentTime = \time();
		}

		throw new Exception("Could not find the email to the address: " . $emailAddress);
	}
}
