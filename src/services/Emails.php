<?php

namespace QD\commerce\economic\services;

use Craft;
use craft\base\Component;
use craft\commerce\Plugin as Commerce;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\web\View;

class Emails extends Component
{

	protected $newEmail;
	protected $mailer;
	protected $craftMailSettings;

	public function __construct()
	{
		$this->mailer = Craft::$app->getMailer();
		$this->newEmail = Craft::createObject(['class' => $this->mailer->messageClass, 'mailer' => $this->mailer]);
		$this->craftMailSettings = App::mailSettings();
	}

	public function send(): bool
	{
		if (!Craft::$app->getMailer()->send($this->newEmail)) {
			return false;
		}

		return true;
	}

	public function setupMail(string $subject, string $to, array $data, string $template): object
	{
		$craftMailSettings = App::mailSettings();

		$fromEmail = Commerce::getInstance()->getSettings()->emailSenderAddress ?: $craftMailSettings->fromEmail;
		$fromEmail = Craft::parseEnv($fromEmail);

		$fromName = Commerce::getInstance()->getSettings()->emailSenderName ?: $craftMailSettings->fromName;
		$fromName = Craft::parseEnv($fromName);

		if ($fromEmail) {
			$this->newEmail->setFrom($fromEmail);
		}

		if ($fromName && $fromEmail) {
			$this->newEmail->setFrom([$fromEmail => $fromName]);
		}

		$this->newEmail->setTo(explode(',', $to));
		$this->newEmail->setSubject($subject);
		$this->newEmail->setHtmlBody(Craft::$app->getView()->renderTemplate($template, $data, View::TEMPLATE_MODE_SITE));

		return $this;
	}

	public function attatchPdfData($data, $fileName)
	{
		$tempPath = Assets::tempFilePath('pdf');

		file_put_contents($tempPath, $data);

		// Attachment information
		$options = ['fileName' => $fileName . '.pdf', 'contentType' => 'application/pdf'];
		$this->newEmail->attach($tempPath, $options);
		return $this;
	}
}
