Scénario de démonstration Hak 7all

1. Artisan
   - Ouvrir http://127.0.0.1:8000/
   - Créer un compte « Je suis artisan » (Mohamed Trabelsi)
   - Remplir profil : métier Plombier, ville Sousse, téléphone +216 98 123 456, consentement numéro public, disponibilite activée
   - Vérifier l’e-mail (lien dans les logs/maildev ou via /renvoyer-verification)

2. Client
   - Ouvrir http://127.0.0.1:8000/
   - Créer un compte « Je cherche un artisan »
   - Rechercher : métier Plombier, ville Sousse
   - Voir la carte de l’artisan (disponible, tarif, bouton Copier)
   - Cliquer « Envoyer une demande » -> cocher « C’est urgent, alerter l’artisan » -> envoyer

3. Artisan (app ouverte)
   - Se connecter en tant qu’artisan
   - Onglet « Demandes » : voir la demande avec étiquette Urgent
   - Alerte plein écran orange avec son (3 bips) apparaît
   - Cliquer « Voir la demande » ou « Plus tard »
   - Accepter la demande -> le téléphone du client apparaît avec bouton Copier

4. Client
   - Aller dans « Mes demandes » -> statut passe en « Acceptée » en temps réel, téléphone de l’artisan visible

5. Escalade (simulation)
   - Envoyer une demande urgente sans ouvrir l’app artisan
   - Après 3 min : relance + SMS (log) dans var/log/dev.log
   - Après 10 min sans réponse : notification « Toujours pas de réponse » avec suggestion d’autres artisans
