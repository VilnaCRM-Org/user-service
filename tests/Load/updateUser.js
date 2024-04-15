import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import counter from "k6/x/counter"

const scenarioName = 'updateUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function updateUser(data) {
    const user = data.users[counter.up()];
    utils.checkUserIsDefined(user);

    const {id} = user;
    const generatedUser = utils.generateUser();
    const password = user.password;

    const payload = JSON.stringify({
        email: generatedUser.email,
        newPassword: password,
        initials: generatedUser.initials,
        oldPassword: password,
    });

    const response = http.patch(
        `${utils.getBaseHttpUrl()}/${id}`,
        payload,
        utils.getMergePatchHeader()
    );

    utils.checkResponse(
        response,
        'is status 200',
        (res) => res.status === 200
    );
}
