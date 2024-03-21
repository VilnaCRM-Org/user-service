import http from 'k6/http';
import {utils} from "./utils.js";

export function setup() {
    utils.insertUsers(10);
}

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(
        utils.getFromEnv('GET_USERS_SMOKE_THRESHOLD'),
        utils.getFromEnv('GET_USERS_AVERAGE_THRESHOLD'),
        utils.getFromEnv('GET_USERS_STRESS_THRESHOLD'),
        utils.getFromEnv('GET_USERS_SPIKE_THRESHOLD')
    ),
};

export default function () {
    getUsers();
}

function getUsers() {
    let page = utils.getRandomNumber(1, 5);
    let itemsPerPage = utils.getRandomNumber(10, 40);

    http.get(
        utils.getBaseHttpUrl() + `?page=${page}&itemsPerPage=${itemsPerPage}`,
        utils.getJsonHeader()
    );
}
