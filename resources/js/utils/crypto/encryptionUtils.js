// Encrypts data with AES-256-GCM using the provided hash as the key
export const encryptWithAes = async (data, hash) => {
    // Convert hash to ArrayBuffer (assume hash is a hex string)
    const keyBuffer = Uint8Array.from(hash.match(/.{1,2}/g).map(byte => parseInt(byte, 16)));
    // Import the key
    const key = await crypto.subtle.importKey(
        'raw',
        keyBuffer,
        { name: 'AES-GCM' },
        false,
        ['encrypt']
    );
    // Generate a random IV
    const iv = crypto.getRandomValues(new Uint8Array(12));
    // Encode data to ArrayBuffer
    const encoder = new TextEncoder();
    const dataBuffer = encoder.encode(data);
    // Encrypt
    const ciphertext = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        key,
        dataBuffer
    );
    // Return base64-encoded IV and ciphertext
    return {
        iv: btoa(String.fromCharCode(...iv)),
        ciphertext: btoa(String.fromCharCode(...new Uint8Array(ciphertext)))
    };
};

export function generateSalt() {
    const array = new Uint8Array(32); // 256-bit salt
    crypto.getRandomValues(array);
    return Array.from(array, (byte) => byte.toString(16).padStart(2, '0')).join('');
}
export const toBase64 = (data) => {
    const uint8Array = new TextEncoder().encode(data);
    return btoa(String.fromCharCode(...uint8Array));
}

const decodeBase64 = (base64) => {
    const binaryString = atob(base64);
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

const decryptWithAes = async (encryptedData, hash) => {
    // Convert hash to ArrayBuffer (assume hash is a hex string)
    const keyBuffer = Uint8Array.from(hash.match(/.{1,2}/g).map(byte => parseInt(byte, 16)));
    // Import the key
    const key = await crypto.subtle.importKey(
        'raw',
        keyBuffer,
        { name: 'AES-GCM' },
        false,
        ['decrypt']
    );
    // Decode base64 IV and ciphertext
    const iv = decodeBase64(encryptedData.iv);
    const ciphertext = decodeBase64(encryptedData.ciphertext);
    // Decrypt
    const decryptedData = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv },
        key,
        ciphertext
    );
    // Convert decrypted data to string
    return new TextDecoder().decode(decryptedData);
}
