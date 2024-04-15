import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {Utils} from "./utils/utils.js";
import {MailCatcherUtils} from "./utils/mailCatcherUtils.js";

const scenarioName = 'confirmUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default async function confirmUser() {
    const user = utils.generateUser();
    const userResponse = await utils.registerUser(user);

    let token = null;

    if (userResponse.status === 201) {
        token = await mailCatcherUtils.getConfirmationToken(user.email);
    }

    const payload = JSON.stringify({
        token
    });

    const response = await http.patch(
        `${utils.getBaseHttpUrl()}/confirm`,
        payload,
        utils.getMergePatchHeader()
    )

    utils.checkResponse(
        response,
        'is status 200',
        (res) => res.status === 200
    );
}
