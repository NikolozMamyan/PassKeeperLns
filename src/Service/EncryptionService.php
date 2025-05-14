<?php
// src/Service/EncryptionService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EncryptionService
{
    private string $encryptionKey;
    private string $method = 'aes-256-gcm'; // Algorithme plus sécurisé que CBC

    public function __construct(
        #[Autowire('%env(APP_ENCRYPTION_KEY)%')] string $encryptionKey
    ) {
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * Chiffre une chaîne de caractères avec AES-GCM
     */
    public function encrypt(string $plaintext): string
    {
        if (empty($plaintext)) {
            return '';
        }

        // Générer un IV aléatoire
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        // Tag d'authentification pour AES-GCM
        $tag = '';
        
        // Chiffrer avec GCM qui fournit l'authentification
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->method,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        // Concaténer l'IV, le tag et le texte chiffré
        $encrypted = base64_encode($iv . $tag . $ciphertext);
        
        return $encrypted;
    }

    /**
     * Déchiffre une chaîne de caractères avec AES-GCM
     */
    public function decrypt(string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }
        
        $decoded = base64_decode($encrypted);
        
        // Récupérer l'IV
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = substr($decoded, 0, $ivlen);
        
        // Récupérer le tag (taille de 16 octets pour GCM)
        $tag = substr($decoded, $ivlen, 16);
        
        // Récupérer le texte chiffré
        $ciphertext = substr($decoded, $ivlen + 16);
        
        // Déchiffrer
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->method,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return $plaintext !== false ? $plaintext : '';
    }
}