import http from 'k6/http';

export default class MailCatcherUtils {
    constructor(utils) {
        this.utils = utils;
        this.config = utils.getConfig();
        const host = this.config.apiHost;
        const mailCatcherPort = this.config.mailCatcherPort;
        this.mailCatcherUrl = `http://${host}:${mailCatcherPort}/messages`;
    }

    getMailCatcherUrl() {
        return this.mailCatcherUrl;
    }

    async getConfirmationToken(email) {
        const promises = [];

        Array.from({ length: this.config.gettingEmailMaxRetries }).forEach(() => {
            promises.push(this.retrieveTokenFromMailCatcher(email));
        });

        const results = await Promise.all(promises);

        return results.find(result => result);
    }

    async retrieveTokenFromMailCatcher(email) {
        const messages = await http.get(this.mailCatcherUrl);
        if (messages.status === 200) {
            const messageId = this.getMessageId(messages, email);

            const message = await http.get(`${this.mailCatcherUrl}/${messageId}.source`);

            return this.extractConfirmationToken(message.body);
        }

        return null;
    }

    getMessageId(messages, email) {
        const parsedMessages = JSON.parse(messages.body);
        const foundMessage = parsedMessages.find(message => message.recipients[0].includes(`<${email}>`));
        return foundMessage ? foundMessage.id : null;
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