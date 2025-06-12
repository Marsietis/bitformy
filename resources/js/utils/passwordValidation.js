import {MIN_PASSWORD_LENGTH} from '@/config/passwordValidationSettings.js';

export const validatePassword = (password, passwordConfirmation) => {
    if (password !== passwordConfirmation) {
        return 'The password confirmation does not match.';
    }

    if (password.length < MIN_PASSWORD_LENGTH) {
        return 'Password must be at least 12 characters.';
    }

    return null; // No errors
};
