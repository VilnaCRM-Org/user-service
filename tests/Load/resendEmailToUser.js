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
    scenarios: utils.getScenarios(
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SMOKE_RPS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SMOKE_VUS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SMOKE_DURATION'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_AVERAGE_RPS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_AVERAGE_VUS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_AVERAGE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_RPS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_VUS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SPIKE_RPS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SPIKE_VUS'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SPIKE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_RESEND_EMAIL_SPIKE_DURATION_FALL'),
    ),
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
