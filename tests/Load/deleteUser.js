import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {Utils} from "./utils/utils.js";
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import exec from 'k6/execution';

const scenarioName = 'deleteUser';
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
    deleteUser(data.users[exec.instance.iterationsInterrupted + exec.instance.iterationsCompleted]);
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
