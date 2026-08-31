import { describe, it, expect } from 'vitest';
import Errors from '../../resources/assets/js/plugins/error';

describe('Errors', () => {
    it('starts with no errors', () => {
        const errors = new Errors();

        expect(errors.any()).toBe(false);
        expect(errors.has('email')).toBe(false);
    });

    it('records a Laravel-style validation error bag', () => {
        const errors = new Errors();

        errors.record({ email: ['The email field is required.'] });

        expect(errors.any()).toBe(true);
        expect(errors.has('email')).toBe(true);
        expect(errors.get('email')).toBe('The email field is required.');
    });

    it('ignores a non-object payload', () => {
        const errors = new Errors();

        errors.record('not an object');

        expect(errors.any()).toBe(false);
    });

    it('clears a single field', () => {
        const errors = new Errors();
        errors.record({ email: ['bad'], password: ['bad'] });

        errors.clear('email');

        expect(errors.has('email')).toBe(false);
        expect(errors.has('password')).toBe(true);
    });

    it('clears all fields when called with no argument', () => {
        const errors = new Errors();
        errors.record({ email: ['bad'], password: ['bad'] });

        errors.clear();

        expect(errors.any()).toBe(false);
    });

    it('set() writes a single field directly', () => {
        const errors = new Errors();

        errors.set('email', ['Invalid email.']);

        expect(errors.get('email')).toBe('Invalid email.');
    });
});
