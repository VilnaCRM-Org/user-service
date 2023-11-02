declare global {
  namespace Cypress {
    interface Chainable {
      checkAndDismissNotification: (matcher: RegExp | string) => void;
    }
  }
}

export {};
