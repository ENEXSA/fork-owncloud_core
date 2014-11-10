<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Mail;

use Swift_Message;

class MessageTest extends \PHPUnit_Framework_TestCase {
	/** @var Swift_Message */
	private $swiftMessage;
	/** @var Message */
	private $message;
	/** @var IConfig */
	private $config;

	/**
	 * @return array
	 */
	public function mailAddressProvider() {
		return array(
			array(array('lukas@owncloud.com' => 'Lukas Reschke'), array('lukas@owncloud.com' => 'Lukas Reschke')),
			array(array('lukas@owncloud.com' => 'Lukas Reschke', 'lukas@öwnclöüd.com', 'lukäs@owncloud.örg' => 'Lükäs Réschke'),
				array('lukas@owncloud.com' => 'Lukas Reschke', 'lukas@xn--wncld-iuae2c.com', 'lukäs@owncloud.xn--rg-eka' => 'Lükäs Réschke')),
			array(array('lukas@öwnclöüd.com'), array('lukas@xn--wncld-iuae2c.com'))
		);
	}

	function setUp() {
		$this->swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();

		$this->message = new Message($this->config, new \OC_Defaults(), $this->swiftMessage);
	}

	/**
	 * @dataProvider mailAddressProvider
	 */
	public function testConvertAddresses($unconverted, $expected) {
		$this->assertSame($expected, \Test_Helper::invokePrivate($this->message, 'convertAddresses', array($unconverted)));
	}

	public function testSetFrom() {
		$this->swiftMessage
			->expects($this->once())
			->method('setFrom')
			->with(array('lukas@owncloud.com'));
		$this->message->setFrom(array('lukas@owncloud.com'));
	}

	public function testGetFrom() {
		$this->swiftMessage
			->expects($this->once())
			->method('getFrom')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getFrom());
	}

	public function testSetTo() {
		$this->swiftMessage
			->expects($this->once())
			->method('setTo')
			->with(array('lukas@owncloud.com'));
		$this->message->setTo(array('lukas@owncloud.com'));
	}

	public function testGetTo() {
		$this->swiftMessage
			->expects($this->once())
			->method('getTo')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getTo());
	}

	public function testSetCc() {
		$this->swiftMessage
			->expects($this->once())
			->method('setCc')
			->with(array('lukas@owncloud.com'));
		$this->message->setCc(array('lukas@owncloud.com'));
	}

	public function testGetCc() {
		$this->swiftMessage
			->expects($this->once())
			->method('getCc')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getCc());
	}

	public function testSetBcc() {
		$this->swiftMessage
			->expects($this->once())
			->method('setBcc')
			->with(array('lukas@owncloud.com'));
		$this->message->setBcc(array('lukas@owncloud.com'));
	}

	public function testGetBcc() {
		$this->swiftMessage
			->expects($this->once())
			->method('getBcc')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getBcc());
	}

	public function testSetSubject() {
		$this->swiftMessage
			->expects($this->once())
			->method('setSubject')
			->with('Fancy Subject');

		$this->message->setSubject('Fancy Subject');
	}

	public function testGetSubject() {
		$this->swiftMessage
			->expects($this->once())
			->method('getSubject')
			->will($this->returnValue('Fancy Subject'));

		$this->assertSame('Fancy Subject', $this->message->getSubject());
	}

	public function testSetPlainBody() {
		$this->swiftMessage
			->expects($this->once())
			->method('setBody')
			->with('Fancy Body');

		$this->message->setPlainBody('Fancy Body');
	}

	public function testGetPlainBody() {
		$this->swiftMessage
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('Fancy Body'));

		$this->assertSame('Fancy Body', $this->message->getPlainBody());
	}

	public function testSetHtmlBody() {
		$this->swiftMessage
			->expects($this->once())
			->method('addPart')
			->with('<blink>Fancy Body</blink>', 'text/html');

		$this->message->setHtmlBody('<blink>Fancy Body</blink>');
	}

	public function testGetMailInstance() {
		$this->assertEquals(\Swift_MailTransport::newInstance(), \Test_Helper::invokePrivate($this->message, 'getMailinstance'));
	}

	public function testGetSendMailInstanceSendMail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'sendmail')
			->will($this->returnValue('sendmail'));

		$this->assertEquals(\Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs'), \Test_Helper::invokePrivate($this->message, 'getSendMailInstance'));
	}

	public function testGetSendMailInstanceSendMailQmail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'sendmail')
			->will($this->returnValue('qmail'));

		$this->assertEquals(\Swift_SendmailTransport::newInstance('/var/qmail/bin/sendmail -bs'), \Test_Helper::invokePrivate($this->message, 'getSendMailInstance'));
	}

	public function testGetSmtpInstanceDefaults() {
		$expected = \Swift_SmtpTransport::newInstance();
		$expected->setHost('127.0.0.1');
		$expected->setTimeout(10);
		$expected->setPort(25);

		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnArgument(1));

		$this->assertEquals($expected, \Test_Helper::invokePrivate($this->message, 'getSmtpInstance'));
	}

	public function testGetInstanceDefault() {
		$this->assertInstanceOf('\Swift_MailTransport', \Test_Helper::invokePrivate($this->message, 'getInstance'));
	}

	public function testGetInstancePhp() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('php'));

		$this->assertInstanceOf('\Swift_MailTransport', \Test_Helper::invokePrivate($this->message, 'getInstance'));
	}

	public function testGetInstanceSmtp() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('smtp'));

		$this->assertInstanceOf('\Swift_SmtpTransport', \Test_Helper::invokePrivate($this->message, 'getInstance'));
	}

	public function testGetInstanceSendmail() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('sendmail'));

		$this->assertInstanceOf('\Swift_SendmailTransport', \Test_Helper::invokePrivate($this->message, 'getInstance'));
	}
}
