<?php

namespace App\Service;

class MailService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->host = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
        $this->port = (int) ($_ENV['MAIL_PORT'] ?? 587);
        $this->username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->encryption = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@example.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'MediLink';
    }

    /**
     * @return \PHPMailer\PHPMailer\PHPMailer
     */
    private function createMailer()
    {
        require_once __DIR__ . '/../../mail/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../../mail/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../mail/PHPMailer/src/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->host;
        $mail->SMTPAuth = true;
        $mail->Username = $this->username;
        $mail->Password = $this->password;
        $mail->SMTPSecure = $this->encryption;
        $mail->Port = $this->port;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($this->fromEmail, $this->fromName);

        return $mail;
    }

    public function sendPasswordReset(string $toEmail, string $toName, string $newPlainPassword): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Réinitialisation de votre mot de passe MediLink';

            $bodyText = "Bonjour {$toName},\n\n"
                . "Un nouveau mot de passe a été généré pour votre compte MediLink.\n\n"
                . "Nouveau mot de passe : {$newPlainPassword}\n\n"
                . "Vous pouvez maintenant vous connecter avec ce mot de passe, puis le modifier depuis votre espace si nécessaire.\n\n"
                . "Ceci est un message automatique, merci de ne pas y répondre.";

            $mail->Body = $bodyText;
            $mail->AltBody = $bodyText;

            return $mail->send();
        } catch (PHPMailerException) {
            return false;
        }
    }

    public function sendRegistrationCode(string $toEmail, string $toName, string $code): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Code de vérification de votre compte MediLink';

            $bodyText = "Bonjour {$toName},\n\n"
                . "Merci d'avoir créé un compte sur MediLink.\n\n"
                . "Voici votre code de vérification : {$code}\n\n"
                . "Si vous n'êtes pas à l'origine de cette inscription, vous pouvez ignorer ce message.\n\n"
                . "Ceci est un message automatique, merci de ne pas y répondre.";

            $mail->Body = $bodyText;
            $mail->AltBody = $bodyText;

            return $mail->send();
        } catch (PHPMailerException) {
            return false;
        }
    }
}

