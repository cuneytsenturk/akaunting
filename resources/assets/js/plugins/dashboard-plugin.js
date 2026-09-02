// Polyfills for js features used in the Dashboard but not supported in some browsers (mainly IE)
import './../polyfills';
// Notifications plugin. Used on Notifications page
import Notifications from './../components/NotificationPlugin';
// Validation plugin used to validate forms
import VeeValidate from 'vee-validate';
// A plugin file where you could register global components used across the app
import GlobalComponents from './globalComponents';
// A plugin file where you could register global directives
import GlobalDirectives from './globalDirectives';

// element ui language configuration
import lang from 'element-ui/lib/locale/lang/en';
// require(), not import — element-ui/lib/locale sets exports.__esModule=true
// with no exports.default (only named exports: use/t/i18n). Every ES import
// form (default/namespace/named) resolved inconsistently under Rollup, and
// even require() only reliably synthesizes `.use` on the namespace object
// for some entries and not others (observed: works for banking/common
// entries built alongside date-fns-heavy graphs, drops `.use` specifically
// for common/dashboards) — a Rollup module-namespace-synthesis quirk, not
// something under our control here. Guarded so a missing `.use` degrades to
// "element-ui's own untranslated strings" (e.g. Popconfirm's Yes/No button
// labels) instead of crashing the whole page's Vue mount.
const locale = require('element-ui/lib/locale');

if (typeof locale.use === 'function') {
    locale.use(lang);
}

// asset imports

export default {
    install(Vue) {
        Vue.use(GlobalComponents);
        Vue.use(GlobalDirectives);
        Vue.use(Notifications);
        Vue.use(VeeValidate, {
            fieldsBagName: 'veeFields',
            classes      : true,
            validity     : true,
            classNames   : {
                valid  : 'is-valid',
                invalid: 'is-invalid'
            }
        });
    }
};
