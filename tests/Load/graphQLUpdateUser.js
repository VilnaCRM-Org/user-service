import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import exec from 'k6/execution';

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
    updateUser(data.users[exec.instance.iterationsInterrupted + exec.instance.iterationsCompleted]);
}

function updateUser(user) {
    const id = user.id;
    const email = faker.person.email();
    const initials = faker.person.name();
    const password = user.password;

    const mutation = `
     mutation {
        updateUser(
            input: {
            id: "/api/users/${id}"
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
        'is status 200': (r) => r.status === 200,
    });
}
