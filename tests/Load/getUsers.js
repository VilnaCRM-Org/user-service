import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {Utils} from "./utils/utils.js";
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";

const scenarioName = 'getUsers';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

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
