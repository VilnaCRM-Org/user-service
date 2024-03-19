import http from 'k6/http';
import * as utils from "./utils.js";

let users;

export function setup() {
    users = utils.insertUsers(5000);
}

export const options = {
    setupTimeout: '300s',
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(),
};

export default function () {
    getUser();
}

function getUser() {
    const user = users[utils.getRandomNumber(0, users.length-1)];
    const id = user.id;

    http.get(
        utils.getBaseUrl() + `api/users/${id}`,
        utils.getJsonHeader()
    );
}
