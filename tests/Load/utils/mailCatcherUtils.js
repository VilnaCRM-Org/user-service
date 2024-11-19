import http from 'k6/http';

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
