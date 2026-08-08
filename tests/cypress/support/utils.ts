export const DEFAULT_LOCALE = 'fr';

export const ALT_USERNAME = 'test';
export const ALT_PASSWORD = 'password';

export const HTTP_OK = 200;

export function getLocale(): string {
    return Cypress.expose('locale') || DEFAULT_LOCALE;
}

/**
 * Prefix a path with /{locale} when the active locale is not the default.
 * Paths like /tests/*, /login_check, /logout must stay unprefixed — do not wrap those.
 */
export function pathWithLocale(path: string): string {
    const normalized = path.startsWith('/') ? path : `/${path}`;
    const locale = getLocale();

    if (locale === DEFAULT_LOCALE) {
        return normalized;
    }

    if (normalized === '/') {
        return `/${locale}/`;
    }

    return `/${locale}${normalized}`;
}

/** Strip origin and current locale prefix from a URL or path. */
export function pathWithoutLocale(urlOrPath: string): string {
    const pathname = new URL(urlOrPath, 'https://athorrent.local').pathname;
    const locale = getLocale();

    if (locale === DEFAULT_LOCALE) {
        return pathname;
    }

    const prefix = `/${locale}`;

    if (pathname === prefix || pathname === `${prefix}/`) {
        return '/';
    }

    if (pathname.startsWith(`${prefix}/`)) {
        return pathname.slice(prefix.length);
    }

    return pathname;
}

export function resetTestData() {
    cy.request('POST', '/tests/reset-data').then((response) => {
        expect(response.status).to.eq(HTTP_OK);
    });
}

export function createUser(username: string, password: string) {
    cy.visit(pathWithLocale('/administration/users/add'));

    cy.get('#add_user_username').type(username);
    cy.get('#add_user_plainPassword').type(password);
    cy.get('#add_user_role').select(0);
    cy.get('#add_user_add').click();

    cy.get('#user-2 > .user-name').should('have.text', username);
}

export function createAltUser() {
    createUser(ALT_USERNAME, ALT_PASSWORD);
}

export function checkIfFieldHasError(selector: string) {
    cy.get(selector).parents('.form-field').should('have.class', 'has-error');
}
