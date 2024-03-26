import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";
import { check } from 'k6';

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SMOKE_RPS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SMOKE_VUS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SMOKE_DURATION'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_AVERAGE_RPS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_AVERAGE_VUS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_AVERAGE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_RPS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_VUS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SPIKE_RPS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SPIKE_VUS'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SPIKE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SPIKE_DURATION_FALL'),
    ),
    thresholds: utils.getThresholds(
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SMOKE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_AVERAGE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_STRESS_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_CREATE_USER_SPIKE_THRESHOLD'),
    )
};

export default function () {
    createUser();
}

function createUser() {
    const email = faker.person.email();
    const initials = faker.person.name();
    const password = faker.internet.password(true, true, true, false, false, 60);

    const payload = JSON.stringify({
        email: email,
        password: password,
        initials: initials,
    });

    const res = http.post(
        utils.getBaseHttpUrl(),
        payload,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 201': (r) => r.status === 201,
    });
}
