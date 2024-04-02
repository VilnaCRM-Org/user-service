import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import exec from 'k6/execution';

const scenarioName = 'replaceUser';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    updateUser(data.users[exec.instance.iterationsInterrupted + exec.instance.iterationsCompleted]);
}

function updateUser(user) {
    const id = user.id;
    const email = faker.person.email();
    const initials = faker.person.name();
    const password = user.password;

    const payload = JSON.stringify({
        email: email,
        newPassword: password,
        initials: initials,
        oldPassword: password,
    });

    const res = http.put(
        utils.getBaseHttpUrl() + `/${id}`,
        payload,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
