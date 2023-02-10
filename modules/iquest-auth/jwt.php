<?php

class Iquest_auth_jwt_exception extends RuntimeException {}

class Iquest_auth_jwt{

    /**
     * Generate JWT token
     *
     * @param string $alg       'HS256' or 'RS256'
     * @param array $payload
     * @param string $secret
     * @return string
     * @throws Iquest_auth_jwt_exception
     * @throws Exception
     */
    public static function generate_jwt(string $alg, array $payload, string $secret) : string{

        $headers = array('alg'=>$alg, 'typ'=>'JWT');
        $headers_encoded = self::base64url_encode(json_encode($headers));
        $payload_encoded = self::base64url_encode(json_encode($payload));

        $data = "$headers_encoded.$payload_encoded";

        switch($alg){
            case 'HS256':
                $signature = self::sign_sha256($data, $secret);
                break;
            case 'RS256':
                $signature = self::sign_rsa($data, $secret);
                break;
            default:
                throw new Exception("Signing algorithm '$alg' not implemented");
        }

        $signature_encoded = self::base64url_encode($signature);

        $jwt = "$headers_encoded.$payload_encoded.$signature_encoded";

        return $jwt;
    }

    private static function sign_sha256(string $payload, string $secret) : string{
        $signature = hash_hmac('SHA256', $payload, $secret, true);

        return $signature;
    }

    /**
     * Sign the $payload using rsa algorithm
     *
     * @param string $payload
     * @param string $privateKey
     * @return string
     * @throws Iquest_auth_jwt_exception
     */
    private static function sign_rsa(string $payload, string $privateKey) : string{
        // Generate keypair: https://gist.github.com/ygotthilf/baa58da5c3dd1f69fae9
        // https://stackoverflow.com/questions/66986631/php-jwt-json-web-token-with-rsa-signature-without-library

        if (!$privateKey) throw new Iquest_auth_jwt_exception("Cannot create signature for JWT token, private key is not configured");

        if (!openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256)){
            throw new Iquest_auth_jwt_exception("Creation of signature for JWT token failed with error: ".openssl_error_string());
        }

        return $signature;
    }

    private static function base64url_encode(string $str) : string{
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($str));
    }

    /**
     * Create JWK from public key
     *
     * @param string $public_key
     * @return string
     */
    public static function get_jwk_pub(string $public_key) : string{
        $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($public_key));

        $jsonData = [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'n' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['n'])), '='),
                    'e' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['e'])), '='),
                ],
            ],
        ];

        return json_encode($jsonData);
    }
}