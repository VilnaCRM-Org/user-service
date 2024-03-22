import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
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

    http.post(
        utils.getBaseHttpUrl(),
        payload,
        utils.getJsonHeader()
    );
}
