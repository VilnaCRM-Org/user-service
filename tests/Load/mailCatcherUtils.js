import http from 'k6/http';
import {Env} from "./env.js";

export class MailCatcherUtils{
    constructor() {
        this.env = new Env();

        const host = this.env.get('LOAD_TEST_API_HOST');
        const mailCatcherPort = this.env.get('LOAD_TEST_MAILCATCHER_PORT');
        this.mailCatcherUrl = `http://${host}:${mailCatcherPort}/messages`;
    }

    getMailCatcherUrl() {
        return this.mailCatcherUrl;
    }

    async getConfirmationToken(email) {
        let token = null;
        for (let attempt = 0; attempt < this.env.get('LOAD_TEST_MAX_GETTING_EMAIL_RETRIES'); attempt++) {
            const result = await this.retrieveTokenFromMailCatcher(email);
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
            let messageId;
            for (const message of JSON.parse(messages.body)) {
                for (const recipient of message.recipients) {
                    if (recipient.includes(`<${email}>`)) {
                        messageId = message.id;
                        break;
                    }
                }
            }

            const message = await http.get(this.mailCatcherUrl + `/${messageId}.source`);

            return this.extractConfirmationToken(message.body)
        }

        return null;
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