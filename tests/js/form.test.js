import { describe, it, expect, beforeEach } from 'vitest';
import Form from '../../resources/assets/js/plugins/form';

function mountForm(html) {
    document.body.innerHTML = html;
}

describe('Form', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('reads method and action from the form element', () => {
        mountForm(`
            <form id="testForm" method="post" action="/invoices">
                <input type="text" name="title" value="Invoice">
            </form>
        `);

        const form = new Form('testForm');

        expect(form.method).toBe('post');
        expect(form.action).toBe('/invoices');
    });

    it('picks up plain input/textarea values as form data', () => {
        mountForm(`
            <form id="testForm" method="post" action="/invoices">
                <input type="text" name="title" value="Invoice">
                <textarea name="notes">Hello</textarea>
            </form>
        `);

        const form = new Form('testForm');

        expect(form.title).toBe('Invoice');
        expect(form.notes).toBe('Hello');
    });

    // form.js reads the `value` HTML attribute directly off the <select> element
    // itself (not the selected <option>) — that's how Akaunting's Blade helpers
    // render these, but it means a plain `<option selected>` (standard HTML) is
    // invisible to form.js.
    it('reads a select field from the value attribute on the <select> tag itself', () => {
        mountForm(`
            <form id="testForm" method="post" action="/invoices">
                <select name="type" value="draft">
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                </select>
            </form>
        `);

        const form = new Form('testForm');

        expect(form.type).toBe('draft');
    });

    it('does not construct fields when the form element is missing', () => {
        mountForm('<div>no form here</div>');

        const form = new Form('missingForm');

        expect(form.method).toBeUndefined();
        expect(form.errors).toBeUndefined();
    });

    it('gives every form instance a fresh Errors bag', () => {
        mountForm('<form id="testForm" method="post" action="/x"></form>');

        const form = new Form('testForm');

        expect(form.errors.any()).toBe(false);
        expect(form.loading).toBe(false);
    });

    it('data() strips internal bookkeeping fields', () => {
        mountForm(`
            <form id="testForm" method="post" action="/invoices">
                <input type="text" name="title" value="Invoice">
            </form>
        `);

        const form = new Form('testForm');
        const data = form.data();

        expect(data.title).toBe('Invoice');
        expect(data.method).toBeUndefined();
        expect(data.action).toBeUndefined();
        expect(data.errors).toBeUndefined();
        expect(data.loading).toBeUndefined();
        expect(data.response).toBeUndefined();
    });
});
