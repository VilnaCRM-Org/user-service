import http from 'k6/http';
import { utils } from "./utils.js";

export function setup() {
    return {users: utils.insertUsers(10)}
}

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(
        utils.getFromEnv('GET_USER_SMOKE_THRESHOLD'),
        utils.getFromEnv('GET_USER_AVERAGE_THRESHOLD'),
        utils.getFromEnv('GET_USER_STRESS_THRESHOLD'),
        utils.getFromEnv('GET_USER_SPIKE_THRESHOLD')
    ),
};

export default function (data) {
    getUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function getUser(user) {
    const id = user.id;

    http.get(
        utils.getBaseHttpUrl() + `/${id}`,
        utils.getJsonHeader()
    );
}
