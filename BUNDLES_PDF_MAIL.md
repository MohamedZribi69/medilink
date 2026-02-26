# PDF et Mailing avec bundles Symfony

Ce projet utilise des **bundles / composants Symfony** pour le PDF et le mailing.

---

## Mailing : Symfony Mailer

Le **mailing** est géré par le **composant officiel Symfony Mailer** (`symfony/mailer` + `symfony/mime`), configuré dans `config/packages/mailer.yaml`.

- Config : `config/packages/mailer.yaml`
- Variables : `MAILER_DSN`, `MAILER_FROM` dans `.env`

---

## PDF : NucleosDompdfBundle (activé)

Le **PDF** est généré via le **bundle Symfony NucleosDompdfBundle**, utilisé par le service `App\Service\PdfService`.

- Le bundle est **enregistré** dans `config/bundles.php`.
- `PdfService` utilise le **DompdfWrapper** du bundle pour générer les PDF (reçu personnalisé, etc.).
- En l'absence du bundle, le service bascule sur Dompdf directement (fallback).

---

## Récapitulatif

| Fonctionnalité | Solution                         | Type               |
|----------------|----------------------------------|--------------------|
| **Mailing**    | Symfony Mailer                   | Composant Symfony  |
| **PDF**        | NucleosDompdfBundle              | Bundle Symfony     |

Le flux PDF et mailing repose sur des composants/bundles Symfony.
