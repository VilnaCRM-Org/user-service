import http from 'k6/http';
import { utils } from "./utils.js";

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

    http.del(
        utils.getBaseHttpUrl() + `/${id}`,
        null,
        utils.getJsonHeader()
    );
}
