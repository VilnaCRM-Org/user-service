import http from 'k6/http';
import { sleep } from 'k6';

export default class MailCatcherUtils {
  constructor(utils) {
    this.utils = utils;
    this.config = utils.getConfig();
    const host = this.config.apiHost;
    const mailCatcherPort = this.config.mailCatcherPort;
    this.mailCatcherUrl = `http://${host}:${mailCatcherPort}/messages`;
    this.maxRetries = this.config.gettingEmailMaxRetries || 300;
    this.retryDelayMs = 100;
  }

  clearMessages() {
    http.del(this.mailCatcherUrl);
  }

  async getConfirmationToken(messageId) {
    for (let attempt = 0; attempt < this.maxRetries; attempt++) {
      const message = http.get(`${this.mailCatcherUrl}/${messageId}.source`);

      if (message.status === 200 && message.body) {
        const token = this.extractConfirmationToken(message.body);
        if (token) {
          return token;
        }
      }

      sleep(this.retryDelayMs / 1000);
    }

    const finalMessage = http.get(`${this.mailCatcherUrl}/${messageId}.source`);
    return this.extractConfirmationToken(finalMessage.body);
  }

  extractConfirmationToken(emailBody) {
    const tokenRegex = /token - ([a-f0-9]+(?:=\r?\n\s*[a-f0-9]+)*)/i;
    const hexPattern = /[a-f0-9]/gi;
    const match = emailBody.match(tokenRegex);
    if (match && match[1]) {
      const matches = match[1].match(hexPattern);
      return matches.join('');
    }
    return null;
  }
}
