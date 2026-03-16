<?php

namespace app\infrastructure\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\backend\shared\components\MailService;

/**
 * Задача отправки email
 */
class SendEmailJob extends BaseObject implements JobInterface
{
    public string $to;
    public string $subject;
    public string $template;
    public array $params = [];
    public ?string $from = null;

    public function execute($queue)
    {
        $mailer = \Yii::$app->mailer;
        
        $message = $mailer->compose($this->template, $this->params)
            ->setTo($this->to)
            ->setSubject($this->subject);

        if ($this->from) {
            $message->setFrom($this->from);
        }

        return $message->send();
    }

    /**
     * Создать задачу отправки email
     */
    public static function create(string $to, string $subject, string $template, array $params = []): self
    {
        return new self([
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
            'params' => $params,
        ]);
    }
}
