import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import { check } from 'k6';
import {Utils} from "./utils.js";
import {InsertUsersUtils} from "./insertUsersUtils.js";

const scenarioName = 'GET_USERS';
const scenarioUtils = new ScenarioUtils(scenarioName);
const utils = new Utils();
const insertUsersUtils = new InsertUsersUtils(scenarioName);

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
