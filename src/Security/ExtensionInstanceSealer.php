<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\MStudio\Bundle\Entity\ExtensionInstance;

class ExtensionInstanceSealer
{
    private const ENC_PREFIX = "ENC:";

    private readonly string $key;
    private readonly string $cipherMethod;

    public function __construct(string $key, string $cipherMethod = "AES-256-CBC")
    {
        $this->cipherMethod = $cipherMethod;
        $this->key = $key;
    }

    /**
     * Asserts that the secret of an extension instance is encrypted.
     *
     * This method is idempotent, so it can be safely called multiple times
     * on the same instance.
     *
     * This method has no counterpart on purpose, to avoid accidentally persisting
     * an unsealed extension instance. Use the `unsealExtensionInstanceSecret`
     * method to decrypt a single extension instance secret independently of the
     * extension instance state.
     *
     * @param ExtensionInstance $instance
     * @return void
     * @throws ExtensionInstanceSealerException
     */
    public function sealExtensionInstance(ExtensionInstance $instance): void
    {
        $instance->setSecret($this->sealExtensionInstanceSecret($instance->getSecret()));
    }

    /**
     * Encrypts a single extension instance string.
     *
     * This method is idempotent, so it can be safely called multiple times
     * on the same instance.
     *
     * @param string $secret
     * @return string
     * @throws ExtensionInstanceSealerException
     */
    public function sealExtensionInstanceSecret(string $secret): string
    {
        if (str_starts_with($secret, self::ENC_PREFIX)) {
            return $secret;
        }

        if ($this->key === "CHANGE_ME") {
            throw new ExtensionInstanceSealerException("no encryption key was defined; refusing to continue for your own good; read the documentation to learn how to configure an encryption key");
        }

        $length = openssl_cipher_iv_length($this->cipherMethod);
        if ($length === false) {
            throw new ExtensionInstanceSealerException("error while building IV");
        }

        $iv = openssl_random_pseudo_bytes($length);

        $encSecret = openssl_encrypt($secret, $this->cipherMethod, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($encSecret === false) {
            throw new ExtensionInstanceSealerException("error while sealing extension instance secret");
        }

        return self::ENC_PREFIX . base64_encode($encSecret) . "|" . base64_encode($iv);
    }

    /**
     * Decrypts a single extension instance secret.
     *
     * This method purposefully does not operate on an ExtensionInstance object,
     * to prevent unsealed extension instances from accidentally being persisted.
     *
     * This method is idempotent, so it can be safely called on already decrypted
     * values.
     *
     * @param string $secret
     * @return string
     * @throws ExtensionInstanceSealerException
     */
    public function unsealExtensionInstanceSecret(string $secret): string
    {
        if (!str_starts_with($secret, self::ENC_PREFIX)) {
            return $secret;
        }

        $encSecret = substr($secret, strlen(self::ENC_PREFIX));
        [$cipherText, $iv] = explode("|", $encSecret, 2);

        $decSecret = openssl_decrypt(base64_decode($cipherText), $this->cipherMethod, $this->key, OPENSSL_RAW_DATA, base64_decode($iv));
        if ($decSecret === false) {
            throw new ExtensionInstanceSealerException("error while unsealing extension instance secret: " . openssl_error_string());
        }

        return $decSecret;
    }
}