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
    this.retryDelaySeconds = 0.1;
  }

  clearMessages() {
    http.del(this.mailCatcherUrl);
  }

  getConfirmationToken(messageId) {
    const url = `${this.mailCatcherUrl}/${messageId}.source`;

    for (let attempt = 0; attempt < this.maxRetries; attempt++) {
      const response = http.get(url);

      if (response.status === 200 && response.body) {
        const token = this.extractConfirmationToken(response.body);
        if (token) {
          return token;
        }
      }

      sleep(this.retryDelaySeconds);
    }

    const finalResponse = http.get(url);
    return this.extractConfirmationToken(finalResponse.body || '');
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
