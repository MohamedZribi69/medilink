# Erreur SSL lors de l'envoi d'e-mail (certificate verify failed)

Si vous voyez : **"Unable to connect with STARTTLS"** ou **"SSL operation failed ... certificate verify failed"**, PHP n’arrive pas à vérifier le certificat du serveur SMTP (ex. Gmail). Voici les solutions, dans l’ordre.

---

## Solution 1 : Désactiver la vérification SSL dans la DSN (rapide, dev/local)

Symfony Mailer permet d’ajouter **`?verify_peer=0`** à la fin de votre `MAILER_DSN` pour désactiver la vérification du certificat. À réserver au **développement local** (pas en production).

Dans **`.env`** :

```env
MAILER_DSN=smtps://votre-email:mot-de-passe-app@smtp.gmail.com:465?verify_peer=0
```

Ou avec le port 587 (STARTTLS) :

```env
MAILER_DSN=smtp://votre-email:mot-de-passe-app@smtp.gmail.com:587?verify_peer=0
```

Puis : `php bin/console cache:clear` et réessayez l’envoi.

---

## Solution 2 : Utiliser le port 465 (SMTPS) au lieu de 587 (STARTTLS)

Souvent, la connexion **SSL directe** sur le port 465 fonctionne même quand STARTTLS sur 587 échoue.

Dans votre **`.env`**, remplacez la ligne `MAILER_DSN` :

**Avant (port 587, STARTTLS) :**
```env
MAILER_DSN=smtp://votre-email:mot-de-passe-app@smtp.gmail.com:587
```

**Après (port 465, SSL) :**
```env
MAILER_DSN=smtps://votre-email:mot-de-passe-app@smtp.gmail.com:465
```

- Utilisez **`smtps://`** (avec un **s**) et le port **465**.
- Gardez le même **mot de passe d’application** Gmail.

Puis videz le cache : `php bin/console cache:clear` et réessayez d’envoyer l’e-mail.

---

## Solution 3 : Configurer le bundle de certificats CA dans PHP

Si l’erreur continue, indiquez à PHP où se trouve le fichier des certificats racine (CA).

1. **Téléchargez le fichier CA à jour** :  
   https://curl.se/ca/cacert.pem  
   Enregistrez-le par exemple dans `D:\xampp\htdocs\medilink\cacert.pem` (écrasez l’ancien si besoin).

2. **Dans le `php.ini` utilisé par Apache/PHP** (souvent `D:\xampp\php\php.ini`) :
   - Cherchez la ligne `;openssl.cafile=` ou `openssl.cafile =`
   - Ajoutez ou modifiez pour avoir **exactement** (en adaptant le chemin si besoin) :
   ```ini
   openssl.cafile = "D:/xampp/htdocs/medilink/cacert.pem"
   ```
   - Sauvegardez le fichier.

3. **Redémarrez Apache** (XAMPP : Stop puis Start pour Apache).

4. Réessayez l’envoi d’e-mail (avec soit `smtp://...:587`, soit `smtps://...:465`).

---

## Solution 4 : Antivirus (Avast, etc.)

Si vous utilisez **Avast** (ou un autre antivirus qui analyse le trafic HTTPS), il peut remplacer les certificats et provoquer la même erreur pour l’envoi d’e-mail.

- **Désactivez temporairement** l’option « Analyser les connexions HTTPS » (Protection Web), ou  
- **Excluez** `php.exe` (ex. `D:\xampp\php\php.exe`) des analyses.

Détails possibles dans **SOLUTION_SSL_COMPOSER.md** (même type de réglage).

---

## Résumé

1. Essayer d’abord **Solution 1** (`?verify_peer=0` dans `MAILER_DSN`).
2. Si vous préférez ne pas désactiver la vérification : **Solution 2** (port 465) puis **Solution 3** (cacert.pem + php.ini).
3. Si l’erreur continue : **Solution 4** (antivirus).
