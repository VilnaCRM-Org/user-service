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
        let token = null;
        const promises = [];

        for (let attempt = 0; attempt < this.config.gettingEmailMaxRetries; attempt++) {
            promises.push(this.retrieveTokenFromMailCatcher(email));
        }

        const results = await Promise.all(promises);

        for (const result of results) {
            if (result) {
                token = result;
                break;
            }
        }

        return token;
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

    getMessageId(messages, email){
        let messageId;
        for (const message of JSON.parse(messages.body)) {
            if (message.recipients[0].includes(`<${email}>`)) {
                messageId = message.id;
                break;
            }
        }

        return messageId;
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