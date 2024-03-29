import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import {check} from 'k6';
import {Utils} from "./utils.js";
import {InsertUsersUtils} from "./insertUsersUtils.js";

const scenarioName = 'DELETE_USER';
const scenarioUtils = new ScenarioUtils(scenarioName);
const utils = new Utils();
const insertUsersUtils = new InsertUsersUtils(scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options= scenarioUtils.getOptions();

export default function (data) {
    deleteUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function deleteUser(user) {
    const id = user.id;

    const res = http.del(
        utils.getBaseHttpUrl() + `/${id}`,
        null,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 204': (r) => r.status === 204,
    });
}
