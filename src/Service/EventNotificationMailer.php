<?php

namespace App\Service;

use App\Entity\Evenement;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Envoi des e-mails aux participants (ex. lorsqu'un événement est supprimé).
 */
final class EventNotificationMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail = 'noreply@medilink.local',
        private string $projectDir = '',
        private string $kernelEnvironment = 'dev',
    ) {
    }

    /**
     * Envoie un e-mail à chaque participant pour les informer de la suppression de l'événement.
     *
     * @param string[] $recipientEmails Adresses e-mail des participants
     */
    public function sendEventDeletedNotification(Evenement $evenement, array $recipientEmails): void
    {
        if ($recipientEmails === []) {
            return;
        }

        $html = $this->twig->render('email/event_deleted.html.twig', [
            'evenement' => $evenement,
        ]);

        $subject = 'Événement annulé : ' . $evenement->getTitre();

        foreach ($recipientEmails as $email) {
            $email = trim($email);
            if ($email === '') {
                continue;
            }
            $message = (new Email())
                ->from($this->fromEmail)
                ->to($email)
                ->subject($subject)
                ->html($html);
            $this->mailer->send($message);

            $this->logEmailInDev($subject, $email, $html);
        }
    }

    /**
     * En dev : enregistre une copie de l'e-mail dans var/log/emails/ pour vérification sans SMTP.
     */
    private function logEmailInDev(string $subject, string $to, string $html): void
    {
        if ($this->kernelEnvironment !== 'dev' || $this->projectDir === '') {
            return;
        }
        $dir = $this->projectDir . \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . 'log' . \DIRECTORY_SEPARATOR . 'emails';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . \DIRECTORY_SEPARATOR . 'event_deleted_' . date('Y-m-d_H-i-s') . '_' . substr(uniqid('', true), -6) . '_' . preg_replace('/[^a-z0-9.-]/i', '_', $to) . '.html';
        $content = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Copie e-mail (dev)</title></head><body>";
        $content .= "<p><strong>À :</strong> " . htmlspecialchars($to) . "</p>";
        $content .= "<p><strong>Sujet :</strong> " . htmlspecialchars($subject) . "</p><hr>";
        $content .= $html;
        $content .= "</body></html>";
        @file_put_contents($file, $content);
    }
}
