import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";

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
    await createUser(email)

    const messages = http.get('http://localhost:1080/messages');

    let messageId;
    for (const message of JSON.parse(messages.body)) {
        if (message.recipients.includes(`<${email}>`)) {
            messageId = message.id;
            break;
        }
    }

    const message = http.get(`http://localhost:1080/messages/${messageId}.source`);
    const token = extractConfirmationToken(message.body)

    const payload = JSON.stringify({
        token: token,
    });

    const res = http.patch(
        utils.getBaseHttpUrl() + '/confirm',
        payload,
        utils.getMergePatchHeader()
    )
}

function extractConfirmationToken(emailBody) {
    const tokenRegex = /confirmation token - ([a-f0-9]+)/i;
    const match = emailBody.match(tokenRegex);
    if (match && match[1]) {
        return match[1];
    }
    return null;
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
