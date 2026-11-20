<?php
define('ENCRYPTION_KEY', 'your-32-byte-secret-key-1234567890'); // Must be exactly 32 bytes

/**
 * Pad plaintext to a fixed length (optional)
 */
function padPlaintext($data, $length = 64) {
    return str_pad($data, $length, "\0"); // Null padding
}

/**
 * Encrypt data and return hex-encoded string
 */
function encryptHex($plaintext, $key) {
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);

    $padded = padPlaintext($plaintext);
    $ciphertext = openssl_encrypt($padded, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    $combined = $iv . $ciphertext;
    return bin2hex($combined); // Alphanumeric and reversible
}

/**
 * Decrypt hex-encoded string
 */
function decryptHex($hex, $key) {
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);

    $combined = hex2bin($hex);
    $iv = substr($combined, 0, $ivlen);
    $ciphertext = substr($combined, $ivlen);

    $decrypted = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return rtrim($decrypted, "\0"); // Remove null padding
}

// // 🧪 Example usage
// $original = "Leave123";
// $encrypted = encryptHex($original, ENCRYPTION_KEY);
// $decrypted = decryptHex($encrypted, ENCRYPTION_KEY);

// echo "Original:   $original\n";
// echo "Encrypted:  $encrypted\n";
// echo "Decrypted:  $decrypted\n";
?>