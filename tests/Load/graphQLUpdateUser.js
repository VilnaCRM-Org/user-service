import http from 'k6/http';
import {Utils} from "./utils.js";
import faker from "k6/x/faker";
import {check} from 'k6';

const utils = new Utils('GRAPHQL_UPDATE_USER')

export function setup() {
    return {
        users: utils.prepareUsers()
    }
}

export const options = utils.getOptions();

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
