import http from 'k6/http';
import {utils} from "./utils.js";
import { check } from 'k6';

export function setup() {
    utils.insertUsers(
        Number(
            utils.getFromEnv('LOAD_TEST_USERS_TO_INSERT')
        )
    )
}

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(
        utils.getFromEnv('LOAD_TEST_GET_USERS_SMOKE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_GET_USERS_AVERAGE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_GET_USERS_STRESS_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_GET_USERS_SPIKE_THRESHOLD')
    ),
};

export default function () {
    getUsers();
}

function getUsers() {
    let page = utils.getRandomNumber(1, 5);
    let itemsPerPage = utils.getRandomNumber(10, 40);

    const res = http.get(
        utils.getBaseHttpUrl() + `?page=${page}&itemsPerPage=${itemsPerPage}`,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
