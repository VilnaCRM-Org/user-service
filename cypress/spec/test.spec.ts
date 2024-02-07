describe('test', () => {
  it('first test', () => {
    cy.visit(Cypress.env('WEBSITE_URL'));
    expect(true).to.equal(true);
  });
});

export {};
