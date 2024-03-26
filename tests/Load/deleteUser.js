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
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SMOKE_RPS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SMOKE_VUS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SMOKE_DURATION'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_AVERAGE_RPS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_AVERAGE_VUS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_AVERAGE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_RPS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_VUS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SPIKE_RPS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SPIKE_VUS'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SPIKE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SPIKE_DURATION_FALL'),
    ),
    thresholds: utils.getThresholds(
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SMOKE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_AVERAGE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_STRESS_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_DELETE_USER_SPIKE_THRESHOLD')
    ),
};

export default function (data) {
    deleteUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function deleteUser(user) {
    const id = user.id;

    const res = http.del(
        utils.getBaseHttpUrl() + `/${id}`,
        null,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 204': (r) => r.status === 204,
    });
}
