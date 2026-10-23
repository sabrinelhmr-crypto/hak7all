# README — Hak 7all (Windows + XAMPP)

## Prérequis
- XAMPP (Apache + MariaDB) installé dans C:\xampp
- PHP 8.3 installé dans C:\xampp\php83 (comme configuré dans ce projet)
- Node.js + npm (pour asset-mapper/importmap si besoin)

## Lancer sous Windows avec XAMPP
1. Démarrer Apache et MariaDB depuis xampp-control.exe
2. Ouvrir un terminal dans C:\Users\admin\Desktop\hak7all
3. Copier .env.example vers .env.local et ajuster si nécessaire

## Base de données
`ash
C:\xampp\php83\php.exe bin\console doctrine:database:create --if-not-exists
C:\xampp\php83\php.exe bin\console doctrine:migrations:migrate --no-interaction
C:\xampp\php83\php.exe bin\console doctrine:fixtures:load --no-interaction
`

## Lancer l’application
`ash
# Serveur Symfony (dev)
C:\xampp\php83\php.exe -S 127.0.0.1:8000 -t public
# ou utiliser le serveur de Symfony CLI si disponible
`

## Temps réel (Mercure) — dev
Télécharger le binaire Mercure depuis https://github.com/dunglas/mercure/releases et lancer :
`ash
mercure --jwt-key='!ChangeThisMercureHubJWTSecretKey!' --addr=':3000' --cors-allowed-origins='*' --publish-allowed-origins='*'
`
Mettre à jour MERCURE_URL/MERCURE_PUBLIC_URL dans .env.local si besoin.

## Tâches en arrière-plan (Messenger)
`ash
C:\xampp\php83\php.exe bin\console messenger:consume async --time-limit=3600
`

## Web Push (VAPID)
Générer des clés VAPID et les mettre dans .env.local :
`env
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:contact@hak7all.tn
`
L’implémentation « log » est utilisée pour les SMS de secours. Brancher SmsSenderInterface vers un fournisseur tunisien plus tard.

## Comptes de démo (après fixtures)
- Artisan : mohamed.trabelsi@example.tn / Motdepasse123!
- Client : client.demo@example.tn / Motdepasse123!
