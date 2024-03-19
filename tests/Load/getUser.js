import http from 'k6/http';
import * as utils from "./utils.js";

export function setup() {
    const users = utils.insertUsers(5000);
    return {users: users}
}

export const options = {
    setupTimeout: '300s',
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(),
};

export default function (data) {
    getUser(data.users);
}

function getUser(users) {
    const user = users[utils.getRandomNumber(0, users.length-1)];
    const id = user.id;

    http.get(
        utils.getBaseUrl() + `api/users/${id}`,
        utils.getJsonHeader()
    );
}
