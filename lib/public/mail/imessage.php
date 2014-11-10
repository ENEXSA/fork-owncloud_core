<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Mail;

/**
 * Class Message provides some basic functions to create a mail message that can be used in combination with
 * \OC\Mail\Mailer.
 *
 * Example usage:
 *
 * 	$message = \OC::$server->createMailMessage();
 * 	$message->setSubject('Your Subject');
 * 	$message->setFrom(array('cloud@domain.org' => 'ownCloud Notifier');
 * 	$message->setTo(array('recipient@domain.org' => 'Recipient');
 * 	$message->setBody('The message text');
 *
 * This message can then be passed to send() of \OC\Mail\Mailer
 *
 * @package OCP\Mail
 */
interface IMessage {
	/**
	 * Set the from address of this message.
	 *
	 * If no "From" address is used \OC\Mail\Mailer will use mail_from_address and mail_domain from config.php
	 *
	 * @param array $addresses Example: array('sender@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setFrom(array $addresses);

	/**
	 * Get the from address of this message.
	 *
	 * @return array
	 */
	public function getFrom();

	/**
	 * Set the to addresses of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setTo(array $recipients);

	/**
	 * Get the to address of this message.
	 *
	 * @return array
	 */
	public function getTo();

	/**
	 * Set the CC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setCc(array $recipients);

	/**
	 * Get the cc address of this message.
	 *
	 * @return array
	 */
	public function getCc();

	/**
	 * Set the BCC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setBcc(array $recipients);

	/**
	 * Get the Bcc address of this message.
	 *
	 * @return array
	 */
	public function getBcc();

	/**
	 * Set the subject of this message.
	 *
	 * @param $subject
	 * @return $this
	 */
	public function setSubject($subject);

	/**
	 * Get the from subject of this message.
	 *
	 * @return string
	 */
	public function getSubject();

	/**
	 * Set the plain-text body of this message.
	 *
	 * @param string $body
	 * @return $this
	 */
	public function setPlainBody($body);

	/**
	 * Get the plain body of this message.
	 *
	 * @return string
	 */
	public function getPlainBody();

	/**
	 * Set the HTML body of this message. Consider also sending a plain-text body instead of only an HTML one.
	 *
	 * @param string $body
	 * @return $this
	 */
	public function setHtmlBody($body);

	/**
	 * Send the constructed message. Also sets the from address to the value defined in config.php
	 * if no-one has been passed.
	 *
	 * @return string[] Array with failed recipients. Be aware that this depends on the used mail backend and
	 * therefore should be considered
	 * @throws \Exception In case it was not possible to send the message. (for example if an invalid mail address
	 * has been supplied.)
	 */
	public function send();
}
