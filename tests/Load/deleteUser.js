import http from 'k6/http';
import counter from 'k6/x/counter';

import InsertUsersUtils from './utils/insertUsersUtils.js';
import ScenarioUtils from './utils/scenarioUtils.js';
import Utils from './utils/utils.js';

const scenarioName = 'deleteUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    };
}

export const options = scenarioUtils.getOptions();

export default function deleteUser(data) {
    const user = data.users[counter.up()];
    utils.checkUserIsDefined(user);

    const {id} = user;

    const response = http.del(
        `${utils.getBaseHttpUrl()}/${id}`,
        null,
        utils.getJsonHeader()
    );

    utils.checkResponse(
        response,
        'is status 204',
        (res) => res.status === 204
    );
}
