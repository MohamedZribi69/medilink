# Intégrations API – Calendriers et SMS

Ce document décrit comment intégrer les **calendriers** (Google Calendar, Microsoft Outlook) et les **SMS** dans le module rendez-vous de MediLink, avec les explications nécessaires à la compréhension et à la mise en œuvre.

---

## 1. Intégration Calendriers

### 1.1 Objectif

 synchroniser automatiquement :
- les **disponibilités** du médecin avec son agenda externe (éviter les doublons, voir ses créneaux dans son calendrier habituel) ;
- les **rendez-vous** confirmés (médecin et patient voient le RDV dans leur calendrier, rappels automatiques).

### 1.2 Concepts clés

| Concept | Explication |
|--------|-------------|
| **OAuth 2.0** | Protocole d’autorisation. L’utilisateur se connecte à Google/Outlook une fois, puis l’application peut accéder à son calendrier de manière sécurisée sans connaître son mot de passe. |
| **Sync bidirectionnelle** | MediLink ↔ calendrier : créneaux créés/modifiés dans MediLink sont envoyés vers le calendrier, et éventuellement les changements côté calendrier peuvent être rapatriés. |
| **Sync unidirectionnelle** | MediLink → calendrier : uniquement l’envoi des créneaux et RDV vers le calendrier. Plus simple à mettre en place. |

Pour une première version, une **sync unidirectionnelle** (MediLink → calendrier) est recommandée.

### 1.3 Flux général

```
┌─────────────────┐    1. Médecin crée/modifie      ┌─────────────────────┐
│  MediLink       │    une disponibilité ou RDV     │  Service Calendrier  │
│  (RendezVous,   │ ─────────────────────────────► │  (CalendarService)   │
│   Disponibilite)│                                └──────────┬──────────┘
└─────────────────┘                                          │
                                                              │ 2. Création/mise à jour
                                                              │    de l’événement via API
                                                              ▼
                                                    ┌─────────────────────┐
                                                    │  Google Calendar    │
                                                    │  ou Outlook         │
                                                    └─────────────────────┘
```

### 1.4 Google Calendar

#### API utilisée
- **Google Calendar API** (REST)
- Documentation : https://developers.google.com/calendar/api/v3/reference

#### Étapes d’intégration

1. **Créer un projet Google Cloud**
   - Aller sur https://console.cloud.google.com/
   - Activer l’API Calendar
   - Créer des identifiants OAuth 2.0 (type "Application web" ou "Application de bureau")

2. **Structure d’un événement Google Calendar**
   ```json
   {
     "summary": "RDV Dr Martin - Jean Dupont",
     "description": "Motif : Consultation générale",
     "start": { "dateTime": "2025-02-15T09:00:00+01:00", "timeZone": "Europe/Paris" },
     "end":   { "dateTime": "2025-02-15T09:30:00+01:00", "timeZone": "Europe/Paris" }
   }
   ```

3. **Bibliothèque PHP recommandée**
   - `google/apiclient` (composer require google/apiclient)

4. **Stockage des tokens**
   - Stocker `refresh_token` et `access_token` liés à l’utilisateur (médecin) dans une table `user_calendar_token` ou un champ JSON sur l’entité User.

### 1.5 Microsoft Outlook / Microsoft 365

#### API utilisée
- **Microsoft Graph API** – endpoint `/me/calendar/events`
- Documentation : https://learn.microsoft.com/graph/api/event-post

#### Étapes d’intégration

1. **Créer une application Azure**
   - https://portal.azure.com/ → Azure Active Directory → Inscriptions d’applications
   - Permissions : `Calendars.ReadWrite`, `offline_access`

2. **Structure d’un événement Outlook**
   ```json
   {
     "subject": "RDV Dr Martin - Jean Dupont",
     "body": { "content": "Motif : Consultation générale", "contentType": "text" },
     "start": { "dateTime": "2025-02-15T09:00:00", "timeZone": "Europe/Paris" },
     "end":   { "dateTime": "2025-02-15T09:30:00", "timeZone": "Europe/Paris" }
   }
   ```

3. **Bibliothèque PHP**
   - Utiliser `league/oauth2-client` ou `thenetworg/oauth2-azure` pour OAuth, puis des requêtes HTTP vers Graph API.

### 1.6 Points d’intégration dans MediLink

| Événement | Action calendrier |
|-----------|-------------------|
| Création d’une **disponibilité** (médecin) | Créer un événement "Créneau libre - Dr X" |
| Modification d’une **disponibilité** | Mettre à jour l’événement |
| Suppression d’une **disponibilité** | Supprimer l’événement |
| Création d’un **rendez-vous** (réservation) | Créer un événement "RDV - Patient X" avec statut "réservé" |
| **Confirmation** du RDV | Mettre à jour l’événement (titre/description) |
| **Annulation** du RDV | Supprimer ou marquer annulé dans le calendrier |

### 1.7 Stockage côté base de données

Pour garder le lien entre MediLink et le calendrier externe :

- Ajouter une colonne `calendar_event_id` (et éventuellement `calendar_provider`) sur `Disponibilite` et/ou `RendezVous`.
- Permet de mettre à jour ou supprimer l’événement sans le rechercher par date/heure.

Exemple :
```php
// Dans Disponibilite.php
#[ORM\Column(length: 255, nullable: true)]
private ?string $googleEventId = null;

#[ORM\Column(length: 255, nullable: true)]
private ?string $outlookEventId = null;
```

---

## 2. Intégration SMS

### 2.1 Objectif

Envoyer des **SMS automatiques** aux patients (et éventuellement aux médecins) pour :
- confirmer une prise de rendez-vous ;
- rappeler le RDV à J-1 ou le jour même ;
- notifier une annulation ou modification.

### 2.2 Concepts clés

| Concept | Explication |
|--------|-------------|
| **API SMS** | Service externe (Twilio, OVH, Nexmo/Vonage, etc.) qui envoie les SMS via une API REST. |
| **Tâches planifiées** | Les rappels à J-1 sont envoyés par une commande Symfony exécutée par CRON, pas au moment de la création du RDV. |
| **Webhooks** | Certains fournisseurs (Twilio) peuvent envoyer des statuts de livraison via webhooks (optionnel). |

### 2.3 Fournisseurs SMS courants

| Fournisseur | API | Tarifs typiques |
|-------------|-----|-----------------|
| **Twilio** | REST simple | ~0,07 €/SMS FR |
| **OVH SMS** | REST | ~0,05 €/SMS FR |
| **Nexmo (Vonage)** | REST | ~0,06 €/SMS |
| **Brevo (ex-Sendinblue)** | REST | SMS marketing + transactionnels |

Pour la France, OVH et Twilio sont fréquemment utilisés.

### 2.4 Flux général

```
┌─────────────────────┐    1. Événement (RDV créé,     ┌─────────────────────┐
│  MediLink           │    confirmé, annulé, etc.)     │  SmsService          │
│  RendezVousService  │ ────────────────────────────►  │  sendRappel(),       │
│  ou Controller      │                                │  sendConfirmation()  │
└─────────────────────┘                                └──────────┬──────────┘
                                                                  │
                                                                  │ 2. Appel API SMS
                                                                  ▼
                                                        ┌─────────────────────┐
                                                        │  Twilio / OVH / ... │
                                                        └──────────┬──────────┘
                                                                   │
                                                                   │ 3. Envoi au téléphone
                                                                   ▼
                                                        ┌─────────────────────┐
                                                        │  Patient (mobile)   │
                                                        └─────────────────────┘
```

### 2.5 Exemple : Twilio

#### Configuration

1. Créer un compte sur https://www.twilio.com/
2. Récupérer : `Account SID`, `Auth Token`, numéro d’expéditeur (ex. `+33612345678`)
3. Variables d’environnement :
   ```
   TWILIO_ACCOUNT_SID=...
   TWILIO_AUTH_TOKEN=...
   TWILIO_FROM_NUMBER=+33612345678
   ```

#### Appel API (POST)

```
POST https://api.twilio.com/2010-04-01/Accounts/{AccountSid}/Messages.json
Content-Type: application/x-www-form-urlencoded

Body=RDV+confirmé+le+15/02+à+9h.+MediLink&To=+33698765432&From=+33612345678
```

Authentification : Basic HTTP avec `AccountSID:AuthToken` en Base64.

### 2.6 Exemple : OVH SMS

OVH propose une API REST avec authentification par signature. Documentation : https://api.ovh.com/

Paramètres typiques : `account`, `sender`, `recipient`, `message`.

### 2.7 Numéro de téléphone du patient

**Important** : L’entité `User` actuelle n’a pas de champ `telephone`. Il faut l’ajouter pour envoyer des SMS :

```php
// Migration à créer
#[ORM\Column(length: 20, nullable: true)]
private ?string $telephone = null;
```

Format recommandé : E.164 (ex. `+33612345678`) pour un envoi international fiable.

### 2.8 Moments d’envoi des SMS

| Moment | Contexte | Exemple de message |
|--------|----------|-------------------|
| **Prise de RDV** | Patient réserve un créneau | « RDV réservé le 15/02 à 9h. Dr Martin. Confirmez sur MediLink. » |
| **Confirmation** | Médecin confirme le RDV | « Votre RDV du 15/02 à 9h est confirmé. Dr Martin, [adresse]. » |
| **Rappel J-1** | CRON, 18h la veille | « Rappel : RDV demain 15/02 à 9h. Dr Martin. MediLink. » |
| **Rappel jour J** | CRON, 1h avant | « RDV aujourd’hui à 9h. Dr Martin. » |
| **Annulation** | RDV annulé | « Votre RDV du 15/02 a été annulé. Prenez un nouveau RDV sur MediLink. » |

### 2.9 Commande CRON pour les rappels

```php
// src/Command/EnvoiRappelsSmsCommand.php
// Exécution : 0 18 * * * (tous les jours à 18h) pour J-1
// Exécution : 0 * * * * (toutes les heures) pour le jour même

$rdvDemain = $this->rendezVousRepository->findRendezVousPourDate($dateDemain);
foreach ($rdvDemain as $rdv) {
    if ($rdv->getPatient()?->getTelephone()) {
        $this->smsService->envoyerRappelJ1($rdv);
    }
}
```

### 2.10 Abstraction du service SMS

Pour pouvoir changer de fournisseur facilement :

```php
// src/Service/Sms/	SmsProviderInterface.php
interface SmsProviderInterface
{
    public function send(string $to, string $message): bool;
}

// src/Service/Sms/TwilioSmsProvider.php
// src/Service/Sms/OvhSmsProvider.php
```

Le `SmsService` utilise l’interface et le fournisseur injecté via la configuration (Twilio, OVH, etc.).

---

## 3. Récapitulatif des dépendances

| Intégration | Composer | Configuration |
|-------------|----------|---------------|
| Google Calendar | `google/apiclient` | Credentials OAuth, `calendar_event_id` |
| Outlook | `league/oauth2-client` ou `thenetworg/oauth2-azure` | Azure App, `outlook_event_id` |
| SMS Twilio | `twilio/sdk` | `TWILIO_*` dans `.env` |
| SMS OVH | HTTP client Symfony | Credentials OVH |

---

## 4. Ordre de mise en œuvre suggéré

1. **SMS** : plus simple (pas d’OAuth), impact immédiat pour les patients.
   - Ajouter `telephone` à `User`.
   - Créer `SmsService` + provider (Twilio ou OVH).
   - Brancher l’envoi lors de la création/confirmation/annulation du RDV.
   - Ajouter la commande CRON pour les rappels.

2. **Calendrier** : nécessite OAuth et une interface de connexion médecin.
   - Ajouter `google_event_id` / `outlook_event_id` et champs OAuth sur `User`.
   - Créer `CalendarService` avec méthodes `createEvent`, `updateEvent`, `deleteEvent`.
   - Créer les écrans "Connecter mon calendrier" pour le médecin.
   - Brancher les événements du calendrier sur création/modification/suppression des disponibilités et rendez-vous.

---

## 5. Sécurité et bonnes pratiques

- **Ne jamais** mettre les clés API ou tokens dans le code : tout dans `.env` ou un gestionnaire de secrets.
- **Tester** en sandbox/test (Twilio offre un mode test).
- **Loguer** les envois SMS (succès/échec) pour le débogage et la facturation.
- **Demander le consentement** du patient pour les SMS (RGPD) – envisager un champ "J'accepte de recevoir des SMS" lors de l’inscription ou de la prise de RDV.
