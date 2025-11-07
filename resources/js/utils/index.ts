import type { Updater } from '@tanstack/vue-table';
import { type Ref, unref } from 'vue';

/**
 * A helper function for TanStack Table to update reactive state values
 * Handles both direct values and updater functions
 */
export function valueUpdater<T extends Updater<any>>(updaterOrValue: T, ref: Ref) {
    ref.value = typeof updaterOrValue === 'function' ? updaterOrValue(ref.value) : updaterOrValue;
}
