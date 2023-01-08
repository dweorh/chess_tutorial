<?php

namespace dweorh\Utils;

class Encryption {
    public static function generate_keys()
    {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $private_key);

        $public_key = openssl_pkey_get_details($res);
        $public_key = $public_key["key"];

        return [
            'private' => $private_key,
            'public' => $public_key
        ];
    }

    public static function encrypt(string $data, string $public_key): string
    {
        openssl_public_encrypt($data, $encrypted, $public_key);
        return base64_encode($encrypted);
    }

    public static function decrypt(string $data, string $private_key): string
    {
        openssl_private_decrypt(base64_decode($data), $decrypted, $private_key);
        return $decrypted;
    }
}