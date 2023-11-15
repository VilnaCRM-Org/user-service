<?php

namespace App\User\Infrastructure;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\Shared\Infrastructure\Bus\Event\UserRegisteredEvent;
use PHPMailer\PHPMailer\PHPMailer;

class SendEmailMessageHandler implements DomainEventSubscriber
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    public static function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }

    public function __invoke(UserRegisteredEvent $userRegisteredEvent)
    {
        // Enable debugging (remove this line when your code is working)
        $this->mail->SMTPDebug = 2;

        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.example.com';
        $this->mail->Port = 587;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'your_smtp_username';
        $this->mail->Password = 'your_smtp_password';
        $this->mail->setFrom('your_email@example.com', 'Your Name');
        $this->mail->addAddress('recipient@example.com', 'Recipient Name');
        $this->mail->Subject = 'Test Email';
        $this->mail->Body = 'This is a test email sent using PHPMailer.';

        if (!$this->mail->send()) {
            error_log('Mailer Error: '.$this->mail->ErrorInfo);
        } else {
            error_log('Message sent!');
        }
    }
}
