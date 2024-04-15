import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
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

export default function getUser(data) {
    const user = data.users[counter.up()]
    utils.checkUserIsDefined(user);

    const {id} = user;

    const response = http.get(
        `${utils.getBaseHttpUrl()}/${id}`,
        utils.getJsonHeader()
    );

    utils.checkResponse(
        response,
        'is status 200',
        (res) => res.status === 200
    );
}
