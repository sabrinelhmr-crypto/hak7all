<?php

namespace App\Service;

class WebPushKeys
{
    /**
     * Génère des clés VAPID sans utiliser les fonctionnalités EC problématiques.
     * Utilise openssl_pkey_new avec EC P-256 de manière compatible.
     */
    public static function generate(): array
    {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ];

        $res = @openssl_pkey_new($config);
        if (false === $res) {
            // Essayer avec openssl.cnf explicite
            $res = @openssl_pkey_new();
            if (false === $res) {
                throw new \RuntimeException('Impossible de générer les clés VAPID. Activez openssl/gmp.');
            }

            $details = openssl_pkey_get_details($res);
            if (!is_array($details) || !isset($details['ec'])) {
                throw new \RuntimeException('Clé générée non EC.');
            }

            $priv = $details['ec']['priv'] ?? null;
            $pub = $details['ec']['pub'] ?? null;

            return [
                'publicKey' => $pub ?? '',
                'privateKey' => $priv ?? '',
            ];
        }

        $details = openssl_pkey_get_details($res);
        if (!is_array($details) || !isset($details['ec'])) {
            throw new \RuntimeException('Détails de clé EC indisponibles.');
        }

        return [
            'publicKey' => $details['ec']['pub'] ?? '',
            'privateKey' => $details['ec']['priv'] ?? '',
        ];
    }
}
