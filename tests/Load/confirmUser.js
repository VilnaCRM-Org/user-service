import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";
import {check} from 'k6';

export const options= utils.getOptions('CONFIRM_USER');

export default function () {
    confirmUser();
}

async function confirmUser() {
    const email = faker.person.email();
    const userResponse = await createUser(email);

    let token = null;

    if (userResponse.status === 201) {
        token = await getToken(email);
    }

    const payload = JSON.stringify({
        token: token,
    });

    const res = await http.patch(
        utils.getBaseHttpUrl() + '/confirm',
        payload,
        utils.getMergePatchHeader()
    )

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}

function createUser(email) {
    const initials = faker.person.name();
    const password = faker.internet.password(true, true, true, false, false, 60);

    const payload = JSON.stringify({
        email: email,
        password: password,
        initials: initials,
    });

    return http.post(
        utils.getBaseHttpUrl(),
        payload,
        utils.getJsonHeader()
    );
}

async function getToken(email) {
    let token = null;
    for (let i = 0; i < utils.getFromEnv('LOAD_TEST_CONFIRM_USER_MAX_GETTING_EMAIL_RETRIES'); i++) {
        const result = await retrieveTokenFromMailCatcher(email);
        if (result) {
            token = result;
            break;
        }
    }
    return token;
}

async function retrieveTokenFromMailCatcher(email) {
    const messages = await http.get(utils.getMailCatcherUrl());
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

        const message = await http.get(utils.getMailCatcherUrl() + `/${messageId}.source`);

        return extractConfirmationToken(message.body)
    }

    return null;
}

function extractConfirmationToken(emailBody) {
    const tokenRegex = /token - ([a-f0-9]+(?:=\r?\n\s*[a-f0-9]+)*)/i;
    const hexPattern = /[a-f0-9]/gi;
    const match = emailBody.match(tokenRegex);
    if (match && match[1]) {
        const matches = match[1].match(hexPattern);
        return matches.join('');
    }
    return null;
}
