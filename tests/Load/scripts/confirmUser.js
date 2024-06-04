import http from 'k6/http';
import counter from 'k6/x/counter';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from "../utils/insertUsersUtils.js";

const scenarioName = 'confirmUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
    return {
        users: users,
    };
}

export const options = scenarioUtils.getOptions();

export default async function confirmUser(data) {
    const num = counter.up();
    const user = data.users[num];

    const token = await mailCatcherUtils.getConfirmationToken(user.email);

    const payload = JSON.stringify({
        token
    });

    const response = await http.patch(
        `${utils.getBaseHttpUrl()}/confirm`,
        payload,
        utils.getMergePatchHeader()
    );

    utils.checkResponse(
        response,
        'is status 200',
        (res) => res.status === 200
    );
}
