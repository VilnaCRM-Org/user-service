import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import counter from "k6/x/counter"

const utils = new Utils();
const scenarioName = 'graphqlUpdateUser';
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    updateUser(data.users[counter.up()]);
}

function updateUser(user) {
    utils.checkUserIsDefined(user);

    const id = utils.getGraphQLIdPrefix() + user.id;
    const mutationName = 'updateUser';
    const email = faker.person.email();
    const initials = faker.person.name();
    const password = user.password;

    const mutation = `
     mutation {
        ${mutationName}(
            input: {
            id: "${id}"
            email: "${email}"
            newPassword: "${password}"
            initials: "${initials}"
            password: "${password}"
        }
    ) {
            user {
                id
            }
        }
    }`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: mutation}),
        utils.getJsonHeader(),
    );

    check(res, {
        'updated user returned': (r) =>
            JSON.parse(r.body).data[mutationName].user.id === `${id}`,
    });
}
