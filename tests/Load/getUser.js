import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import {check} from 'k6';
import {Utils} from "./utils.js";
import {InsertUsersUtils} from "./insertUsersUtils.js";

const scenarioName = 'GET_USER';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(scenarioName);
const insertUsersUtils = new InsertUsersUtils(scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers(),
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    getUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function getUser(user) {
    const id = user.id;

    const res = http.get(
        utils.getBaseHttpUrl() + `/${id}`,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
