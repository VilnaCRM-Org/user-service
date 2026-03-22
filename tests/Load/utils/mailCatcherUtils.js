import http from 'k6/http';
import { sleep } from 'k6';

export default class MailCatcherUtils {
  constructor(utils) {
    this.utils = utils;
    this.config = utils.getConfig();
    const host = this.config.apiHost;
    const mailCatcherPort = this.config.mailCatcherPort;
    this.mailCatcherUrl = `http://${host}:${mailCatcherPort}/messages`;
  }

  clearMessages() {
    http.del(this.mailCatcherUrl);
  }

  getMessageCount() {
    const response = http.get(this.mailCatcherUrl);
    if (response.status === 200 && response.body) {
      try {
        const messages = JSON.parse(response.body);
        return messages.length;
      } catch (e) {
        return 0;
      }
    }
    return 0;
  }

  waitForEmails(expectedCount, maxWaitSeconds = 30) {
    const pollInterval = 0.5;
    const maxAttempts = maxWaitSeconds / pollInterval;

    for (let attempt = 0; attempt < maxAttempts; attempt++) {
      const count = this.getMessageCount();
      if (count >= expectedCount) {
        return true;
      }
      sleep(pollInterval);
    }
    return false;
  }

  async getConfirmationToken(messageId) {
    const message = await http.get(`${this.mailCatcherUrl}/${messageId}.source`);

    return this.extractConfirmationToken(message.body);
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
