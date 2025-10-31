import * as openpgp from 'openpgp';

export const generatePgpKeys = async (name) => {
    const {privateKey, publicKey} = await openpgp.generateKey({
        type: 'ecc', // Type of the key, defaults to ECC
        curve: 'curve25519', // ECC curve name, defaults to curve25519
        userIDs: [{name: name}],
        format: 'armored' // output key format, defaults to 'armored' (other options: 'binary' or 'object')
    });
    return {privateKey, publicKey};
}
