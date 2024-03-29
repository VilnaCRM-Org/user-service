import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {InsertUsersUtils} from "./insertUsersUtils.js";
import {Utils} from "./utils.js";

const scenarioName = 'GRAPHQL_UPDATE_USER';
const scenarioUtils = new ScenarioUtils(scenarioName);
const insertUsersUtils = new InsertUsersUtils(scenarioName);
const utils = new Utils();

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    updateUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
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
