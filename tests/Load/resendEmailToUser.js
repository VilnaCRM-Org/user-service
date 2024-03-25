import http from 'k6/http';
import { utils } from "./utils.js";
import { check } from 'k6';

export function setup() {
    return {
        users: utils.insertUsers(
            Number(
                utils.getFromEnv('LOAD_TEST_USERS_TO_INSERT')
            )
        )
    }
}

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SMOKE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_AVERAGE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SPIKE_THRESHOLD')
    ),
};

export default function (data) {
    deleteUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function deleteUser(user) {
    const id = user.id;

    const res = http.put(
        utils.getBaseHttpUrl() + `/${id}/resend-confirmation-email`,
        null,
        utils.getJsonHeader(),
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
