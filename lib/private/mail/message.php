<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Mail;

use OCP\IConfig;
use Swift_Message;
use \OCP\Mail\IMessage;

/**
 * Class Message provides some basic functions to create a mail messages.
 *
 * Example usage:
 *
 * 	$message = \OC::$server->createMailMessage();
 * 	$message->setSubject('Your Subject');
 * 	$message->setFrom(array('cloud@domain.org' => 'ownCloud Notifier');
 * 	$message->setTo(array('recipient@domain.org' => 'Recipient');
 * 	$message->setBody('The message text');
 * 	$failures = $message->send();
 *
 * This message can then be passed to send() of \OC\Mail\Mailer
 *
 * @package OC\Mail
 */
class Message implements IMessage {
	/** @var Swift_Message */
	private $message;
	/** @var IConfig */
	private $config;
	/** @var \OC_Defaults */
	private $defaults;
	/** @var \Swift_SmtpTransport|\Swift_SendmailTransport|\Swift_MailTransport Cached transport */
	private $instance = null;

	/**
	 * @param IConfig $config
	 * @param \OC_Defaults $defaults
	 * @param Swift_Message $swiftMessage
	 */
	function __construct(IConfig $config, \OC_Defaults $defaults, Swift_Message $swiftMessage) {
		$this->config = $config;
		$this->defaults = $defaults;
		$this->message = $swiftMessage;
	}

	/**
	 * SwiftMailer does currently not work with IDN domains, this function therefore converts the domains
	 *
	 * FIXME: Remove this once SwiftMailer supports IDN
	 *
	 * @param array $addresses Array of mail addresses, key will get converted
	 * @return array Converted addresses if `idn_to_ascii` exists
	 */
	protected function convertAddresses($addresses) {
		if (!function_exists('idn_to_ascii')) {
			return $addresses;
		}

		$convertedAddresses = array();

		foreach($addresses as $email => $readableName) {
			if(!is_numeric($email)) {
				list($name, $domain) = explode('@', $email, 2);
				$domain = idn_to_ascii($domain);
				$convertedAddresses[$name.'@'.$domain] = $readableName;
			} else {
				list($name, $domain) = explode('@', $readableName, 2);
				$domain = idn_to_ascii($domain);
				$convertedAddresses[$email] = $name.'@'.$domain;
			}
		}

		return $convertedAddresses;
	}

	/**
	 * Set the from address of this message.
	 *
	 * If no "From" address is used \OC\Mail\Mailer will use mail_from_address and mail_domain from config.php
	 *
	 * @param array $addresses Example: array('sender@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setFrom(array $addresses) {
		$addresses = $this->convertAddresses($addresses);

		$this->message->setFrom($addresses);
		return $this;
	}

	/**
	 * Get the from address of this message.
	 *
	 * @return array
	 */
	public function getFrom() {
		return $this->message->getFrom();
	}

	/**
	 * Set the to addresses of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setTo(array $recipients) {
		$recipients = $this->convertAddresses($recipients);

		$this->message->setTo($recipients);
		return $this;
	}

	/**
	 * Get the to address of this message.
	 *
	 * @return array
	 */
	public function getTo() {
		return $this->message->getTo();
	}

	/**
	 * Set the CC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setCc(array $recipients) {
		$recipients = $this->convertAddresses($recipients);

		$this->message->setCc($recipients);
		return $this;
	}

	/**
	 * Get the cc address of this message.
	 *
	 * @return array
	 */
	public function getCc() {
		return $this->message->getCc();
	}

	/**
	 * Set the BCC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setBcc(array $recipients) {
		$recipients = $this->convertAddresses($recipients);

		$this->message->setBcc($recipients);
		return $this;
	}

	/**
	 * Get the Bcc address of this message.
	 *
	 * @return array
	 */
	public function getBcc() {
		return $this->message->getBcc();
	}

	/**
	 * Set the subject of this message.
	 *
	 * @param $subject
	 * @return $this
	 */
	public function setSubject($subject) {
		$this->message->setSubject($subject);
		return $this;
	}

	/**
	 * Get the from subject of this message.
	 *
	 * @return string
	 */
	public function getSubject() {
		return $this->message->getSubject();
	}

	/**
	 * Set the plain-text body of this message.
	 *
	 * @param string $body
	 * @return $this
	 */
	public function setPlainBody($body) {
		$this->message->setBody($body);
		return $this;
	}

	/**
	 * Get the plain body of this message.
	 *
	 * @return string
	 */
	public function getPlainBody() {
		return $this->message->getBody();
	}

	/**
	 * Set the HTML body of this message. Consider also sending a plain-text body instead of only an HTML one.
	 *
	 * @param string $body
	 * @return $this
	 */
	public function setHtmlBody($body) {
		$this->message->addPart($body, 'text/html');
		return $this;
	}

	/**
	 * Returns the mail transport
	 * @return \Swift_MailTransport
	 */
	protected function getMailInstance() {
		return \Swift_MailTransport::newInstance();
	}

	/**
	 * Returns the sendmail transport
	 * @return \Swift_SendmailTransport
	 */
	protected function getSendMailInstance() {
		switch ($this->config->getSystemValue('mail_smtpmode', 'sendmail')) {
			case 'qmail':
				$binaryPath = '/var/qmail/bin/sendmail';
				break;
			default:
				$binaryPath = '/usr/sbin/sendmail';
				break;
		}

		return \Swift_SendmailTransport::newInstance($binaryPath . ' -bs');
	}

	/**
	 * Returns the SMTP transport
	 * @return \Swift_SmtpTransport
	 */
	protected function getSmtpInstance() {
		$transport = \Swift_SmtpTransport::newInstance();
		$transport->setTimeout($this->config->getSystemValue('mail_smtptimeout', 10));
		$transport->setHost($this->config->getSystemValue('mail_smtphost', '127.0.0.1'));
		$transport->setPort($this->config->getSystemValue('mail_smtpport', 25));
		if ($this->config->getSystemValue('mail_smtpauth', false)) {
			$transport->setUsername($this->config->getSystemValue('mail_smtpname', ''));
			$transport->setPassword($this->config->getSystemValue('mail_smtppassword', ''));
			$transport->setAuthMode($this->config->getSystemValue('mail_smtpauthtype', 'LOGIN'));
		}
		$smtpSecurity = $this->config->getSystemValue('mail_smtpsecure', '');
		if (!empty($smtpSecurity)) {
			$transport->setEncryption($smtpSecurity);
		}
		return $transport;
	}

	/**
	 * Returns whatever transport is configured within the config
	 * @return \Swift_SmtpTransport|\Swift_SendmailTransport|\Swift_MailTransport
	 */
	protected function getInstance() {
		if(!is_null($this->instance)) {
			return $this->instance;
		}

		switch ($this->config->getSystemValue('mail_smtpmode', 'php')) {
			case 'smtp':
				$this->instance = $this->getSMTPInstance();
				break;
			case 'sendmail':
				$this->instance = $this->getSendMailInstance();
				break;
			default:
				$this->instance = $this->getMailInstance();
				break;
		}

		return $this->instance;
	}

	/**
	 * Send the constructed message. Also sets the from address to the value defined in config.php
	 * if no-one has been passed.
	 *
	 * @return string[] Array with failed recipients. Be aware that this depends on the used mail backend and
	 * therefore should be considered
	 * @throws \Exception In case it was not possible to send the message. (for example if an invalid mail address
	 * has been supplied.)
	 */
	public function send() {
		if(sizeof($this->message->getFrom()) === 0) {
			$this->message->setFrom(array(\OCP\Util::getDefaultEmailAddress($this->defaults->getName())));
		}

		$failedRecipients = array();

		$this->getInstance()->send($this->message, $failedRecipients);

		return $failedRecipients;
	}
}
