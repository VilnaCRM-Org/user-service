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
        utils.getFromEnv('LOAD_TEST_GET_USER_SMOKE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_GET_USER_AVERAGE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_GET_USER_STRESS_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_GET_USER_SPIKE_THRESHOLD')
    ),
};

export default function (data) {
    getUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function getUser(user) {
    const id = user.id;

    const res = http.get(
        utils.getBaseHttpUrl() + `/${id}`,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
