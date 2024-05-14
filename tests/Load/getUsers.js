import http from 'k6/http';
import InsertUsersUtils from './utils/insertUsersUtils.js';
import ScenarioUtils from './utils/scenarioUtils.js';
import Utils from './utils/utils.js';

const scenarioName = 'getUsers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

const users = insertUsersUtils.getInsertedUsers()

export function setup() {
    return {
        users: users
    };
}

export const options = scenarioUtils.getOptions();

export default function getUsers() {
    let page = utils.getRandomNumber(1, 5);
    let itemsPerPage = utils.getRandomNumber(10, 40);

    const response = http.get(
       `${utils.getBaseHttpUrl()}?page=${page}&itemsPerPage=${itemsPerPage}`,
        utils.getJsonHeader()
    );

    utils.checkResponse(
        response,
        'is status 200',
        (res) => res.status === 200
    );
}
