# Configuration du mailing – MediLink

## Ce qui est en place

- **Symfony Mailer** : `symfony/mailer` et `symfony/mime` (dans `composer.json` / `vendor`)
- **Config** : `config/packages/mailer.yaml` (DSN + paramètre `mailer_from`)
- **Envoi du reçu** : uniquement en **admin** → fiche d’un don validé → bouton **« Envoyer le reçu par e-mail au patient »**
- Le PDF est généré (Dompdf), attaché à l’e-mail et envoyé à l’e-mail du **donateur** (patient) lié au don (`id_user`).

## Fichiers concernés

| Fichier | Rôle |
|--------|------|
| `config/packages/mailer.yaml` | DSN du mailer + paramètre `mailer_from` |
| `.env` | `MAILER_DSN`, `MAILER_FROM` |
| `src/Controller/Admin/DonController.php` | Action `envoyerRecuEmail` (génère PDF + envoi) |
| `src/Service/PdfService.php` | Génération du PDF à partir du template |
| `templates/pdf/recu_don.html.twig` | Modèle du reçu PDF |
| `templates/admin/don/show.html.twig` | Bouton « Envoyer le reçu par e-mail au patient » |

## Variables d’environnement (.env)

```env
# En dev : les e-mails ne sont pas vraiment envoyés (null transport)
MAILER_DSN=null://null

# En production : exemples
# MAILER_DSN=smtp://user:password@smtp.example.com:587
# MAILER_DSN=smtps://user:password@smtp.example.com:465

# Adresse expéditeur (optionnel, défaut: noreply@medilink.org)
MAILER_FROM=noreply@medilink.org
```

## Envoi réel en production

1. Définir **`MAILER_DSN`** dans `.env` ou `.env.local` avec un vrai serveur SMTP, par exemple :
   - `smtp://user:pass@smtp.example.com:587`
   - `smtps://user:pass@smtp.example.com:465`
2. Ajuster **`MAILER_FROM`** si besoin (sinon `noreply@medilink.org`).
3. S’assurer que les dons ont un **donateur** (user) avec une **adresse e-mail** (champ `id_user` renseigné à la création du don côté front).

## Test en dev avec envoi réel

Pour envoyer vraiment en dev, remplacez temporairement dans `.env` :

```env
MAILER_DSN=smtp://votre-user:votre-pass@smtp.gmail.com:587
```

(Ex. Gmail : mot de passe d’application si 2FA activé.)

## Vérification rapide

- Admin → Dons → ouvrir un **don validé** qui a un **patient (donateur)** avec e-mail.
- Cliquer sur **« Envoyer le reçu par e-mail au patient »**.
- Avec `MAILER_DSN=null://null`, le message est « consommé » par le transport null (pas d’envoi réel, pas d’erreur).
- Avec un vrai SMTP, l’e-mail part vers l’adresse du donateur.
