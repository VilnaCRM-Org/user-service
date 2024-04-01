import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {Utils} from "./utils/utils.js";
import {MailCatcherUtils} from "./utils/mailCatcherUtils.js";

const utils = new Utils();
const scenarioName = 'confirmUser';
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
export const options = scenarioUtils.getOptions();

export default function () {
    confirmUser();
}

async function confirmUser() {
    const email = faker.person.email();
    const userResponse = await createUser(email);

    let token = null;

    if (userResponse.status === 201) {
        token = await mailCatcherUtils.getConfirmationToken(email);
    }

    const payload = JSON.stringify({
        token: token,
    });

    const res = await http.patch(
        utils.getBaseHttpUrl() + '/confirm',
        payload,
        utils.getMergePatchHeader()
    )

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}

function createUser(email) {
    const initials = faker.person.name();
    const password = faker.internet.password(true, true, true, false, false, 60);

    const payload = JSON.stringify({
        email: email,
        password: password,
        initials: initials,
    });

    return http.post(
        utils.getBaseHttpUrl(),
        payload,
        utils.getJsonHeader()
    );
}