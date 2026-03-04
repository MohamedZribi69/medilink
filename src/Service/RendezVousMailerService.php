<?php

namespace App\Service;

use App\Entity\RendezVous;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Envoi des e-mails liés aux rendez-vous (confirmation, rappel, etc.).
 */
final class RendezVousMailerService
{
    // IMPORTANT : doit correspondre au compte SMTP utilisé (Gmail)
    private const FROM_EMAIL = 'medilink.no.reply@gmail.com';
    private const FROM_NAME = 'MediLink';

    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    /**
     * Envoie l’e-mail de confirmation au patient après la prise de rendez-vous.
     * Ne fait rien si le patient n’a pas d’email.
     */
    public function envoyerConfirmationRendezVous(RendezVous $rendezVous): void
    {
        $patient = $rendezVous->getPatient();
        if (!$patient || !$patient->getEmail()) {
            return;
        }

        $dispo = $rendezVous->getDisponibilite();
        $medecin = $dispo?->getMedecin();

        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($patient->getEmail())
            ->subject('MediLink – Confirmation de votre rendez-vous')
            ->htmlTemplate('emails/confirmation_rendezvous.html.twig')
            ->context([
                'patientName' => $patient->getFullName() ?? 'Patient',
                'dateHeure' => $rendezVous->getDateHeure(),
                'medecinName' => $medecin ? $medecin->getFullName() : 'Médecin',
                'motif' => $rendezVous->getMotif() ?? '',
            ]);

        $this->mailer->send($email);
    }
}
