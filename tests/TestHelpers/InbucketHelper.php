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
	 * @param string $emailAddress
	 *
	 * @return string
	 */
	public static function getMailBoxFromEmail(string $emailAddress):string {
		$splitEmailForUserMailBox =  explode("@", $emailAddress);
		return $splitEmailForUserMailBox[0];
	}

	/**
	 * return general response information with mailBox (for foo@example.com, mailBox = foo)
	 *
	 * @param string $mailBox
	 * @param string|null $xRequestId
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getMailboxeInformation(string $mailBox, ?string $xRequestId = null) {
		$response = HttpRequestHelper::get(
			self::getLocalEmailUrl() . "/api/v1/mailbox/" . $mailBox,
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
	 * @param string $mailBox
	 * @param string|null $xRequestId
	 * @param int|null $emailNumber
	 *
	 * @return string
	 * @throws GuzzleException
	 */
	public static function getMailboxIdByEmailNumber(string $mailBox, ?string $xRequestId = null, ?int $emailNumber = 1): string {
		$mailBoxResponse = self::getMailboxeInformation($mailBox, $xRequestId);
		return $mailBoxResponse[\sizeof($mailBoxResponse) - $emailNumber]->id;
	}

	/**
	 * Deletes all the emails
	 *
	 * @param string|null $localInbucketUrl
	 * @param string|null $xRequestId
	 * @param string $mailBox
	 *
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	public static function deleteAllEmailsForAMailbox(
		?string $localInbucketUrl,
		?string $xRequestId,
		string $mailBox
	):ResponseInterface {
		return HttpRequestHelper::delete(
			$localInbucketUrl . "/api/v1/mailbox/" . $mailBox,
			$xRequestId
		);
	}

	/**
	 * Returns the body of the last email according to email number (1 = latest received)
	 *
	 * @param string $emailAddress
	 * @param string|null $xRequestId
	 * @param int|null $emailNumber
	 * @param int|null $waitTimeSec Time to wait for the email
	 *
	 * @return string
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public static function getBodyOfLastEmail(
		string $emailAddress,
		string $xRequestId,
		?int $emailNumber = 1,
		?int $waitTimeSec = EMAIL_WAIT_TIMEOUT_SEC
	) {
		$currentTime = \time();
		$endTime = $currentTime + $waitTimeSec;
		while ($currentTime <= $endTime) {
			$mailBox = self::getMailBoxFromEmail($emailAddress);
			$mailboxId = self::getMailboxIdByEmailNumber($mailBox, $xRequestId, $emailNumber);
			$response = self::getContentOfAnEmail($mailBox, $mailboxId);
				$body = \str_replace(
					"\r\n",
					"\n",
					\quoted_printable_decode($response->body->text . "\n" . $response->body->html)
				);
				return $body;
			\usleep(STANDARD_SLEEP_TIME_MICROSEC * 50);
			$currentTime = \time();
		}
		throw new Exception("Could not find the email to the address: " . $emailAddress);
	}

	/**
	 * returns body content of a specific email (mailBox) with email ID (mailbox Id)
	 *
	 * @param string $mailBox
	 * @param string $mailboxId
	 *
	 * @return mixed JSON encoded contents
	 * @throws GuzzleException
	 */
	public static function getContentOfAnEmail(string $mailBox, string $mailboxId) {
		$response = HttpRequestHelper::get(
			self::getLocalEmailUrl() . "/api/v1/mailbox/" . $mailBox . "/" . $mailboxId,
			null,
			null,
			null,
			['Content-Type' => 'application/json']
		);

		return json_decode($response->getBody()->getContents());
	}

	/**
	 *
	 * @param string $emailAddress
	 * @param string|null $xRequestId
	 * @param int|null $waitTimeSec Time to wait for the email
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public static function isEmailReceived(
		string $emailAddress,
		?string $xRequestId,
		?int $waitTimeSec = EMAIL_WAIT_TIMEOUT_SEC
	):bool {
		try {
			self::getBodyOfLastEmail(
				$emailAddress,
				$xRequestId,
				$waitTimeSec
			);
		} catch (Exception $err) {
			return false;
		}

		return true;
	}

	/**
	 * returns the email address of email sender
	 *
	 * @param string $emailAddress email address of the receiver
	 * @param string|null $xRequestId
	 * @param int|null $emailNumber which number of multiple emails to read (first email is 1)
	 *
	 * @return mixed
	 * @throws GuzzleException
	 * @throws Exception
	 */
	public static function getEmailAddressOfSender(
		string $emailAddress,
		string $xRequestId,
		?int $emailNumber = 1
	) {
		$mailBox = self::getMailBoxFromEmail($emailAddress);
		$mailBoxResponse = self::getMailboxeInformation($mailBox, $xRequestId);
		return $mailBoxResponse[\sizeof($mailBoxResponse) - $emailNumber]->from;
	}
}
