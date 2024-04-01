import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {Utils} from "./utils/utils.js";

const utils = new Utils();
const scenarioName = 'createUser';
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

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
