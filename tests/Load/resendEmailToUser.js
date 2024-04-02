import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import exec from 'k6/execution';

const utils = new Utils();
const scenarioName = 'resendEmailToUser';
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    resendEmail(data.users[exec.instance.iterationsInterrupted + exec.instance.iterationsCompleted]);
}

function resendEmail(user) {
    const id = user.id;

    const res = http.post(
        utils.getBaseHttpUrl() + `/${id}/resend-confirmation-email`,
        JSON.stringify(null),
        utils.getJsonHeader(),
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
