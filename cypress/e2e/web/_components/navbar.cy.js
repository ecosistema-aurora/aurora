describe('Página de Home do ambiente web', () => {
    beforeEach(() => {
        cy.viewport(1920, 1080);
        cy.visit('/');
    });

    const entities = [
        { name: 'Oportunidades', path: '/web_opportunity_list' },
        { name: 'Eventos', path: '/web_event_list' },
        { name: 'Espaços', path: '/web_space_list' },
        { name: 'Agentes', path: '/web_agent_list' },
        { name: 'Organizações', path: '/web_organization_list' },
        { name: 'Iniciativas', path: '/admin_initiative_list' }
    ];

    it('Garante que o navbar existe', () => {
        const entityNames = entities.map(entity => entity.name);
        entityNames.forEach((entity) => {
            cy.get('a').contains(entity).should('be.visible');
        });
    });

    it('Clica no ícone de Oportunidades e garante o redirecionamento', () => {
        cy.get('a.nav-link.opportunity').should('be.visible').and('not.be.disabled').click({ force: true });
        cy.url().should('include', '/oportunidades');
    });

    it('Clica no ícone de Agentes e garante o redirecionamento', () => {
        cy.get('a.nav-link.agent').should('be.visible').and('not.be.disabled').click({ force: true });
        cy.url().should('include', '/agentes');
    });

    it('Clica no ícone de Organizações e garante o redirecionamento', () => {
        cy.get('a.nav-link.organization').should('be.visible').and('not.be.disabled').click({ force: true });
        cy.url().should('include', '/organizacoes');
    });

    it('Clica no ícone de Eventos e garante o redirecionamento', () => {
        cy.get('a.nav-link.event').should('be.visible').and('not.be.disabled').click({ force: true });
        cy.url().should('include', '/eventos');
    });

    it('Clica no ícone de Espaços e garante o redirecionamento', () => {
        cy.get('a.nav-link.space').should('be.visible').and('not.be.disabled').click({ force: true });
        cy.url().should('include', '/espacos');
    });

    it('Clica no ícone de Iniciativas e garante o redirecionamento', () => {
        cy.contains('a', 'Iniciativas').should('be.visible').click({ force: true });
        cy.url().should('include', '/iniciativas');
    });
});
