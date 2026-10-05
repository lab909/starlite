// Every test fails if the page reports a Content Security Policy violation, a JavaScript error or a
// console error, whatever else it checks.
import { test as base, expect } from '@playwright/test';

export const test = base.extend({
    problems: [async ({ page }, use) => {
        const problems = [];
        await page.exposeFunction('reportCspViolation', (violation) => problems.push(`CSP: ${violation}`));
        await page.addInitScript(() => document.addEventListener('securitypolicyviolation', (e) => {
            window.reportCspViolation(`${e.effectiveDirective} blocked ${e.blockedURI || 'inline code'} on ${location.pathname}`);
        }));
        page.on('pageerror', (error) => problems.push(`JavaScript: ${error.message}`));
        page.on('console', (message) => message.type() === 'error' && problems.push(`Console: ${message.text()}`));
        await use(problems);
        expect(problems).toEqual([]);
    }, { auto: true }],
});

export { expect };
