import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";
import { check } from 'k6';

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(
        utils.getFromEnv('LOAD_TEST_CONFIRM_USER_SMOKE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_CONFIRM_USER_AVERAGE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_CONFIRM_USER_STRESS_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_CONFIRM_USER_SPIKE_THRESHOLD'),
    )
};

export default function () {
    confirmUser();
}

async function confirmUser() {
    const email = faker.person.email();
    const userResponse = await createUser(email);

    let token;

    if(userResponse.status === 201){
        for(let i =0; i<utils.getFromEnv('LOAD_TEST_CONFIRM_USER_MAX_GETTING_EMAIL_RETRIES'); i++){
            const result = await retrieveToken(email);
            if(result){
                token = result;
                break;
            }
        }
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

async function retrieveToken(email){
    const messages = await http.get(utils.getMailCatcherUrl());
    if(messages.status === 200){
        let messageId;
        for (const message of JSON.parse(messages.body)) {
            for(const recipient of message.recipients){
                if (recipient.includes(`<${email}>`)) {
                    messageId = message.id;
                    break;
                }
            }
        }

        const message = await http.get(utils.getMailCatcherUrl() + `/${messageId}.source`);

        return extractConfirmationToken(message.body)
    }

    else return null;
}

function extractConfirmationToken(emailBody) {
    const tokenRegex = /token - ([a-f0-9]+(?:=\r?\n\s*[a-f0-9]+)*)/i;
    const hexPattern = /[a-f0-9]/gi;
    const match = emailBody.match(tokenRegex);
    if (match && match[1]) {
        const matches = match[1].match(hexPattern);
        return matches.join('');
    }
    else {
        return null;
    }
}
