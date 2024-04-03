import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {Utils} from "./utils/utils.js";
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import counter from "k6/x/counter"

const scenarioName = 'getUser';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers(),
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    getUser(data.users[counter.up()]);
}

function getUser(user) {
    utils.checkUserIsDefined(user);

    const id = user.id;

    const res = http.get(
        utils.getBaseHttpUrl() + `/${id}`,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
