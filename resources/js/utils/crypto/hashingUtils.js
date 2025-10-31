import {argon2id} from 'hash-wasm';
import {argon2Settings, SHA_HASHING_ALGORITHM} from '@/config/hashSettings.js';

export const argon2idHash = async (password, salt) => {
    return argon2id({
        password,
        ...argon2Settings,
        salt: salt,
    });
};

export const shaHash = async (data) => {
    const msgUint8 = new TextEncoder().encode(data); // encode as (utf-8) Uint8Array
    const hashBuffer = await window.crypto.subtle.digest(SHA_HASHING_ALGORITHM, msgUint8); // hash the message
    const hashArray = Array.from(new Uint8Array(hashBuffer)); // convert buffer to byte array
    // convert bytes to hex string
    return hashArray
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('');
};
